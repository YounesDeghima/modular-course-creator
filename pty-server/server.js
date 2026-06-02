/**
 * pty-server/server.js  -  WINDOWS-NATIVE VERSION (TTY WARNING FILTER INTEGRATED)
 */

import { WebSocketServer }           from 'ws';
import { spawn }                     from 'child_process';
import { writeFileSync, mkdirSync,
    rmSync, existsSync }        from 'fs';
import { join }                      from 'path';
import { tmpdir }                    from 'os';
import { randomBytes, createHmac,
    timingSafeEqual }           from 'crypto';
import { createServer }              from 'http';

// ─────────────────────────────────────────────────────────────────────────────
//  CONFIG  -  all values overridable via environment variables
// ─────────────────────────────────────────────────────────────────────────────
const CFG = {
    PORT:             Number(process.env.PTY_PORT)         || 4000,
    HOST:             process.env.PTY_HOST                 || '0.0.0.0',
    SECRET: (() => {
        const s = process.env.PTY_SECRET;
        if (!s) {
            console.error('\n[ERROR] PTY_SECRET env var is not set. Refusing to start.');
            console.error('    Generate one:');
            console.error("      node -e \"console.log(require('crypto').randomBytes(32).toString('hex'))\"");
            console.error('    Then set it in your terminal before running:');
            console.error('      Windows CMD:  set PTY_SECRET=your_secret_here');
            console.error('      Windows PS:   $env:PTY_SECRET=\"your_secret_here\" \n');
            process.exit(1);
        }
        return s;
    })(),

    CONTAINER_BIN:    process.env.CONTAINER_BIN            || 'podman',
    IMAGE:            process.env.RUNNER_IMAGE             || 'localhost/code-runner:latest',
    MEMORY:           process.env.MEMORY_LIMIT             || '128m',
    CPUS:             process.env.CPU_LIMIT                || '0.5',
    PIDS:             Number(process.env.PIDS_LIMIT)       || 64,

    RUN_TIMEOUT_MS:   Number(process.env.RUN_TIMEOUT_MS)   || 200_000,
    MAX_OUTPUT_BYTES: Number(process.env.MAX_OUTPUT_BYTES)  || 524_288,
    MAX_CODE_BYTES:   Number(process.env.MAX_CODE_BYTES)    || 65_536,

    RATE_RUNS:        Number(process.env.RATE_LIMIT_RUNS)   || 20,
    RATE_WIN_MS:      Number(process.env.RATE_LIMIT_WIN_MS) || 60_000,
    MAX_CONCURRENT:   Number(process.env.MAX_CONCURRENT)    || 10,
};

// ─────────────────────────────────────────────────────────────────────────────
//  LANGUAGE MAP  -  Execution configurations relying on native TTY behaviors
// ─────────────────────────────────────────────────────────────────────────────
const LANGUAGES = {
    python: { ext: 'py', cmd: ['python3', '-u', '/code/code.py'] },
    javascript: { ext: 'js', cmd: ['node', '/code/code.js'] },
    typescript: { ext: 'ts', cmd: ['ts-node', '--transpile-only', '/code/code.ts'] },
    ruby: { ext: 'rb', cmd: ['ruby', '/code/code.rb'] },
    php: { ext: 'php', cmd: ['php', '/code/code.php'] },
    lua: { ext: 'lua', cmd: ['lua', '/code/code.lua'] },
    perl: { ext: 'pl', cmd: ['perl', '/code/code.pl'] },
    bash: { ext: 'sh', cmd: ['bash', '/code/code.sh'] },

    go: {
        ext: 'go',
        cmd: ['sh', '-c', 'export GOCACHE=/tmp/go-cache GOPATH=/tmp/go-path && go run /code/code.go']
    },
    c: {
        ext: 'c',
        cmd: ['sh', '-c', 'gcc /code/code.c -o /tmp/prog -lm && /tmp/prog']
    },
    cpp: {
        ext: 'cpp',
        cmd: ['sh', '-c', 'g++ -std=c++17 /code/code.cpp -o /tmp/prog && /tmp/prog']
    },
    'c++': {
        ext: 'cpp',
        cmd: ['sh', '-c', 'g++ -std=c++17 /code/code.cpp -o /tmp/prog && /tmp/prog']
    },

    java: { ext: 'java', cmd: ['sh', '-c', 'cp /code/code.java /tmp/Main.java && javac /tmp/Main.java -d /tmp && java -cp /tmp Main'] },
    rust: { ext: 'rs', cmd: ['sh', '-c', 'rustc /code/code.rs -o /tmp/prog 2>&1 && /tmp/prog'] },
    kotlin: { ext: 'kt', cmd: ['sh', '-c', 'kotlinc /code/code.kt -include-runtime -d /tmp/prog.jar 2>&1 && java -jar /tmp/prog.jar'] }
};

let activeSandboxes = 0;
const liveContainers = new Set();

function safeEqual(a, b) {
    const k  = randomBytes(32);
    const ha = createHmac('sha256', k).update(String(a)).digest();
    const hb = createHmac('sha256', k).update(String(b)).digest();
    return timingSafeEqual(ha, hb);
}

function extractToken(req) {
    try {
        const url = new URL(req.url, `http://${req.headers.host || 'localhost'}`);
        return url.searchParams.get('token') ?? '';
    } catch { return ''; }
}

function send(ws, type, payload = {}) {
    if (ws.readyState === 1) {
        try { ws.send(JSON.stringify({ type, ...payload })); } catch {}
    }
}

function buildPodmanArgs(containerName, lang, srcFile) {
    const normalizedSrcFile = srcFile.replace(/\\/g, '/');
    return [
        'run', '--rm',
        '-i', '-t',
        '--name', containerName,
        '--network', 'none',
        '--memory', CFG.MEMORY,
        '--cpus', CFG.CPUS,
        '--pids-limit', String(CFG.PIDS),
        '-v', `${normalizedSrcFile}:/code/code.${lang.ext}:ro`,
        CFG.IMAGE,
        ...lang.cmd
    ];
}

function forceRemoveContainer(name) {
    liveContainers.delete(name);
    spawn(CFG.CONTAINER_BIN, ['rm', '-f', name], { stdio: 'ignore' });
}

// ─────────────────────────────────────────────────────────────────────────────
//  HTTP + WEBSOCKET SERVER
// ─────────────────────────────────────────────────────────────────────────────
const httpServer = createServer((req, res) => {
    if (req.method === 'GET' && req.url === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ ok: true, active: activeSandboxes, maxConcurrent: CFG.MAX_CONCURRENT }));
        return;
    }
    res.writeHead(404); res.end('Not found');
});

const wss = new WebSocketServer({
    server: httpServer,
    verifyClient({ req }, done) {
        const token = extractToken(req);
        if (!token || !safeEqual(token, CFG.SECRET)) {
            console.log('[ERROR] Connection rejected: Token mismatch.');
            done(false, 401, 'Unauthorized');
            return;
        }
        done(true);
    },
});

wss.on('connection', (ws) => {
    let containerName = null;
    let containerProc = null;
    let sessionDir    = null;
    let killTimer     = null;
    let outputBytes   = 0;

    let runsThisWindow = 0;
    const rateInterval = setInterval(() => { runsThisWindow = 0; }, CFG.RATE_WIN_MS);

    function cleanup(containerAlreadyGone = false) {
        clearTimeout(killTimer);
        killTimer = null;
        if (!containerAlreadyGone && containerName) {
            forceRemoveContainer(containerName);
        } else if (containerName) {
            liveContainers.delete(containerName);
        }
        containerName = null;
        containerProc = null;
        outputBytes   = 0;

        if (sessionDir && existsSync(sessionDir)) {
            try { rmSync(sessionDir, { recursive: true, force: true }); } catch {}
            sessionDir = null;
        }
        if (activeSandboxes > 0) activeSandboxes--;
    }

    ws.on('message', async (raw) => {
        let msg;
        try { msg = JSON.parse(raw); } catch { return; }

        if (msg.type === 'run') {
            if (runsThisWindow >= CFG.RATE_RUNS) {
                send(ws, 'stderr', { data: `\r\n[WARNING] Rate limit hit. Max ${CFG.RATE_RUNS} runs per minute.\r\n` });
                send(ws, 'exit',   { code: 429 });
                return;
            }
            runsThisWindow++;

            if (activeSandboxes >= CFG.MAX_CONCURRENT) {
                send(ws, 'stderr', { data: '\r\n[WARNING] Server busy - too many concurrent executions.\r\n' });
                send(ws, 'exit',   { code: 503 });
                return;
            }

            if (containerName) cleanup();

            const langKey = (msg.language ?? '').toLowerCase().trim();
            const lang    = LANGUAGES[langKey];
            if (!lang) {
                send(ws, 'stderr', { data: `Unsupported language: '${msg.language}'.\r\n` });
                send(ws, 'exit',   { code: 1 });
                return;
            }

            const code = String(msg.code ?? '');

            if (Buffer.byteLength(code, 'utf8') > CFG.MAX_CODE_BYTES) {
                send(ws, 'stderr', { data: `Code too large. Maximum is ${CFG.MAX_CODE_BYTES / 1024} KB.\r\n` });
                send(ws, 'exit',   { code: 1 });
                return;
            }

            const runId   = randomBytes(8).toString('hex');
            containerName = `ce_${runId}`;
            sessionDir    = join(tmpdir(), `ce_src_${runId}`);

            try {
                mkdirSync(sessionDir, { recursive: true });
                const srcFile = join(sessionDir, `code.${lang.ext}`);
                writeFileSync(srcFile, code, { encoding: 'utf8' });

                activeSandboxes++;
                liveContainers.add(containerName);

                send(ws, 'system', { data: `[Sandboxed - ${langKey} | mem:${CFG.MEMORY} cpu:${CFG.CPUS} timeout:${CFG.RUN_TIMEOUT_MS/1000}s]\r\n` });

                const fullPodmanArgs = buildPodmanArgs(containerName, lang, srcFile);
                containerProc = spawn(CFG.CONTAINER_BIN, fullPodmanArgs, {
                    stdio: ['pipe', 'pipe', 'pipe'],
                    env: { ...process.env }
                });

                containerProc.stdout.on('data', (chunk) => {
                    outputBytes += chunk.length;
                    if (outputBytes > CFG.MAX_OUTPUT_BYTES) {
                        send(ws, 'stderr', { data: `\r\n[ERROR] Output cap exceeded - process killed.\r\n` });
                        cleanup();
                        send(ws, 'exit', { code: -2 });
                        return;
                    }
                    send(ws, 'stdout', { data: chunk.toString('utf8') });
                });

                containerProc.stderr.on('data', (chunk) => {
                    let str = chunk.toString('utf8');

                    // Filter out the native Podman TTY input device warning lines entirely
                    if (str.includes('The input device is not a TTY')) {
                        str = str.replace(/^.*The input device is not a TTY.*$/gm, '');
                    }

                    // Avoid transferring messages if the line block has been fully cleared
                    if (str.trim() === '') return;

                    outputBytes += Buffer.byteLength(str, 'utf8');
                    send(ws, 'stderr', { data: str });
                });

                containerProc.on('close', (exitCode) => {
                    const stillTracked = !!containerName;
                    cleanup(true);
                    if (stillTracked) {
                        send(ws, 'exit', { code: exitCode ?? 0 });
                    }
                });

                containerProc.on('error', (err) => {
                    send(ws, 'stderr', { data: `\r\n[Server] Could not start Podman: ${err.message}\r\n` });
                    cleanup(true);
                    send(ws, 'exit', { code: 1 });
                });

                killTimer = setTimeout(() => {
                    send(ws, 'stderr', { data: `\r\n[TIMEOUT] Run time limit exceeded - container killed.\r\n` });
                    cleanup();
                    send(ws, 'exit', { code: -1 });
                }, CFG.RUN_TIMEOUT_MS);

            } catch (err) {
                send(ws, 'stderr', { data: `\r\n[Server] Internal error: ${err.message}\r\n` });
                cleanup(true);
                send(ws, 'exit', { code: 1 });
            }
        }

        if (msg.type === 'stdin') {
            if (!containerProc?.stdin?.writable) return;
            try { containerProc.stdin.write(String(msg.data ?? '').slice(0, 4096)); } catch {}
        }

        if (msg.type === 'kill') {
            if (!containerName) return;
            cleanup();
            send(ws, 'system', { data: '\r\n[Killed by user]\r\n' });
            send(ws, 'exit',   { code: -1 });
        }
    });

    ws.on('close', () => { clearInterval(rateInterval); cleanup(); });
    ws.on('error', () => { clearInterval(rateInterval); cleanup(); });
});

function shutdown(signal) {
    console.log(`\n${signal} - removing ${liveContainers.size} live container(s)...`);
    for (const name of liveContainers) forceRemoveContainer(name);
    setTimeout(() => process.exit(0), 1000);
}
process.on('SIGINT',  () => shutdown('SIGINT'));
process.on('SIGTERM', () => shutdown('SIGTERM'));
process.on('uncaughtException', (err) => { shutdown('uncaughtException'); });

httpServer.listen(CFG.PORT, CFG.HOST, () => {
    console.log(`\n[SUCCESS] PTY server listening on ws://${CFG.HOST}:${CFG.PORT}`);
});
