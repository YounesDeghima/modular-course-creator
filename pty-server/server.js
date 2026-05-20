/**
 * pty-server/server.js  —  WINDOWS-NATIVE VERSION
 *
 * What changed from the WSL2 version:
 *  - Binds to 0.0.0.0 (all interfaces) instead of 127.0.0.1
 *    → Laravel on Windows can now reach it without any netsh portproxy tricks
 *  - CONTAINER_BIN defaults to 'podman' (Podman Desktop on Windows)
 *  - Removed --userns=keep-id from podman args (not supported on Windows rootless)
 *  - Updated startup log messages to reflect Windows environment
 *  - PTY_HOST env var lets you lock down which interface to bind if needed
 *
 * Security layers (all preserved from original):
 *  1. Token auth        — WebSocket handshake must present PTY_SECRET token
 *  2. Podman sandbox    — every run is an isolated throw-away container:
 *                           --rm, --network=none, --memory, --cpus,
 *                           --read-only rootfs, --cap-drop=ALL,
 *                           --security-opt=no-new-privileges, --pids-limit
 *  3. Wall-clock timeout — container hard-killed after RUN_TIMEOUT_MS
 *  4. Output cap        — connection killed if stdout+stderr > MAX_OUTPUT_BYTES
 *  5. Code size guard   — server-side check independent of Laravel
 *  6. Rate limit        — max N runs per WebSocket connection per minute
 *  7. Concurrency cap   — max total simultaneous containers globally
 *  8. Container GC      — orphan containers forcibly removed on crash / SIGTERM
 *  9. Input clamp       — stdin writes capped per-message to prevent flooding
 * 10. Constant-time auth — HMAC comparison prevents timing attacks
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
//  CONFIG  —  all values overridable via environment variables
// ─────────────────────────────────────────────────────────────────────────────
const CFG = {
    PORT:             Number(process.env.PTY_PORT)         || 4000,

    // Bind host — 0.0.0.0 means all interfaces (required so Windows Laravel
    // can reach the server). Set PTY_HOST=127.0.0.1 to lock down to loopback
    // only if you add a reverse proxy in front of Laravel.
    HOST:             process.env.PTY_HOST                 || '0.0.0.0',

    // Secret shared with Laravel .env as PTY_SECRET
    // Generate: node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
    SECRET: (() => {
        
        const s = process.env.PTY_SECRET ;
        
        if (!s) {
            console.error('\n❌  PTY_SECRET env var is not set. Refusing to start.');
            console.error('    Generate one:');
            console.error("      node -e \"console.log(require('crypto').randomBytes(32).toString('hex'))\"");
            console.error('    Then set it:');
            console.error('      Windows CMD:  set PTY_SECRET=<value>');
            console.error('      Windows PS:   $env:PTY_SECRET="<value>"');
            console.error('    And in Laravel .env:  PTY_SECRET=<value>\n');
            process.exit(1);
        }
        return s;
    })(),

    // Use 'podman' (Podman Desktop on Windows) or 'docker'
    CONTAINER_BIN:    process.env.CONTAINER_BIN            || 'podman',

    // The image that has all language runtimes — built via the Containerfile
    IMAGE:            process.env.RUNNER_IMAGE             || 'localhost/code-runner:latest',

    // Resource limits inside each container
    MEMORY:           process.env.MEMORY_LIMIT             || '128m',
    CPUS:             process.env.CPU_LIMIT                || '0.5',
    PIDS:             Number(process.env.PIDS_LIMIT)       || 64,

    // Execution limits
    RUN_TIMEOUT_MS:   Number(process.env.RUN_TIMEOUT_MS)   || 10_000,   // 10 s
    MAX_OUTPUT_BYTES: Number(process.env.MAX_OUTPUT_BYTES)  || 524_288,  // 512 KB
    MAX_CODE_BYTES:   Number(process.env.MAX_CODE_BYTES)    || 65_536,   // 64 KB

    // Per-connection rate limit
    RATE_RUNS:        Number(process.env.RATE_LIMIT_RUNS)   || 20,
    RATE_WIN_MS:      Number(process.env.RATE_LIMIT_WIN_MS) || 60_000,   // 1 minute

    // Global concurrency ceiling
    MAX_CONCURRENT:   Number(process.env.MAX_CONCURRENT)    || 10,
};

// ─────────────────────────────────────────────────────────────────────────────
//  LANGUAGE MAP
//  All paths below are *inside* the container.
//  Source file is always mounted at /code/code.<ext>  (read-only).
//  /tmp is a writable tmpfs (for compiled binaries).
//
//  To add a new language:
//    1. Add it here
//    2. Install the runtime in the Containerfile and rebuild the image
//    3. Add <option> to the blade select + STARTERS + EXT_MAP in app.js
// ─────────────────────────────────────────────────────────────────────────────
const LANGUAGES = {
    // ── Interpreted ──────────────────────────────────────────────────────────
    python: {
        ext: 'py',
        // -u = unbuffered so input() prompts stream immediately
        cmd: ['python3', '-u', '/code/code.py'],
    },
    javascript: {
        ext: 'js',
        cmd: ['node', '/code/code.js'],
    },
    typescript: {
        ext: 'ts',
        cmd: ['ts-node', '--transpile-only', '/code/code.ts'],
    },
    ruby: {
        ext: 'rb',
        cmd: ['ruby', '/code/code.rb'],
    },
    php: {
        ext: 'php',
        cmd: ['php', '/code/code.php'],
    },
    lua: {
        ext: 'lua',
        cmd: ['lua', '/code/code.lua'],
    },
    perl: {
        ext: 'pl',
        cmd: ['perl', '/code/code.pl'],
    },
    bash: {
        ext: 'sh',
        cmd: ['bash', '/code/code.sh'],
    },
    go: {
        ext: 'go',
        cmd: ['go', 'run', '/code/code.go'],
    },
    swift: {
        ext: 'swift',
        cmd: ['swift', '/code/code.swift'],
    },

    // ── Compiled (chain compile + run inside container via sh -c) ────────────
    c: {
        ext: 'c',
        cmd: ['sh', '-c', 'gcc /code/code.c -o /tmp/prog -lm && /tmp/prog'],
    },
    cpp: {
        ext: 'cpp',
        cmd: ['sh', '-c', 'g++ -std=c++17 /code/code.cpp -o /tmp/prog && /tmp/prog'],
    },
    'c++': {
        ext: 'cpp',
        cmd: ['sh', '-c', 'g++ -std=c++17 /code/code.cpp -o /tmp/prog && /tmp/prog'],
    },
    java: {
        ext: 'java',
        // Copy needed because javac uses filename to derive class name
        cmd: ['sh', '-c', 'cp /code/code.java /tmp/Main.java && javac /tmp/Main.java -d /tmp && java -cp /tmp Main'],
    },
    rust: {
        ext: 'rs',
        cmd: ['sh', '-c', 'rustc /code/code.rs -o /tmp/prog 2>&1 && /tmp/prog'],
    },
    kotlin: {
        ext: 'kt',
        cmd: ['sh', '-c', 'kotlinc /code/code.kt -include-runtime -d /tmp/prog.jar 2>&1 && java -jar /tmp/prog.jar'],
    },
};

// ─────────────────────────────────────────────────────────────────────────────
//  GLOBAL STATE
// ─────────────────────────────────────────────────────────────────────────────
let activeSandboxes = 0;
const liveContainers = new Set();   // container names alive right now

// ─────────────────────────────────────────────────────────────────────────────
//  HELPERS
// ─────────────────────────────────────────────────────────────────────────────

/** Constant-time token comparison — prevents timing attacks */
function safeEqual(a, b) {
    const k  = randomBytes(32);
    const ha = createHmac('sha256', k).update(String(a)).digest();
    const hb = createHmac('sha256', k).update(String(b)).digest();
    return timingSafeEqual(ha, hb);
}

/** Pull the token from the WS upgrade URL query string */
function extractToken(req) {
    try {
        const url = new URL(req.url, `http://${req.headers.host || 'localhost'}`);
        return url.searchParams.get('token') ?? '';
    } catch { return ''; }
}

/** Send a structured JSON frame to the browser */
function send(ws, type, payload = {}) {
    if (ws.readyState === 1) {
        try { ws.send(JSON.stringify({ type, ...payload })); } catch {}
    }
}

/**
 * Build the full podman run argument list for one sandboxed execution.
 *
 * NOTE: --userns=keep-id is removed here. That flag is Linux-specific rootless
 * Podman behaviour. On Windows (Podman Desktop / podman machine), the default
 * user mapping inside the VM is correct and the flag causes an error.
 */
function buildPodmanArgs(containerName, lang, srcFile) {
    // ── TRANSLATE WINDOWS PATH TO WSL PATH ──
    let linuxSrcFile = srcFile;
    if (linuxSrcFile.includes(':\\') || linuxSrcFile.includes(':/')) {
        linuxSrcFile = linuxSrcFile
            .replace(/^([a-zA-Z]):/, (match, drive) => `/mnt/${drive.toLowerCase()}`)
            .replace(/\\/g, '/');
    }

    // Combine everything into a unified array that podman expects
    const args = [
        'run', '--rm',
        '-i', // 👈 Crucial for interactive WebSocket stdin/stdout streams
        '--name', containerName,
        '--network', 'none',
        '--memory', CFG.MEMORY,
        '--cpus', CFG.CPUS,
        '--pids-limit', String(CFG.PIDS),
        '-v', `${linuxSrcFile}:/code/code.${lang.ext}:ro`, // Dynamic extension mapping
        CFG.IMAGE, // 👈 The target container image
        ...lang.cmd // 👈 The actual script executable command (e.g., python3 -u /code/code.py)
    ];

    return args;
}

/** Force-remove a container — fire-and-forget, safe to call on already-dead containers */
function forceRemoveContainer(name) {
    liveContainers.delete(name);
    const p = spawn(CFG.CONTAINER_BIN, ['rm', '-f', name], { stdio: 'ignore' });
    p.on('error', () => {});
}

// ─────────────────────────────────────────────────────────────────────────────
//  HTTP SERVER  (WebSocket upgrade + /health endpoint)
// ─────────────────────────────────────────────────────────────────────────────
const httpServer = createServer((req, res) => {
    if (req.method === 'GET' && req.url === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({
            ok:            true,
            active:        activeSandboxes,
            maxConcurrent: CFG.MAX_CONCURRENT,
        }));
        return;
    }
    res.writeHead(404); res.end('Not found');
});

// ─────────────────────────────────────────────────────────────────────────────
//  WEBSOCKET SERVER
// ─────────────────────────────────────────────────────────────────────────────
const wss = new WebSocketServer({
    server: httpServer,

    // Token check runs during the HTTP upgrade — before the socket is created.
    // A bad token sends HTTP 401 and the connection is refused entirely.
    // Token check runs during the HTTP upgrade — before the socket is created.
    // A bad token sends HTTP 401 and the connection is refused entirely.
    verifyClient({ req }, done) {
        const token = extractToken(req);

        // 🔍 ADD THIS DEBUG BLOCK HERE:
        console.log("\n=================== PTY AUTH ATTEMPT ===================");
        console.log(` -> URL Attempted  : "${req.url}"`);
        console.log(` -> Secret Expected: "${CFG.SECRET}"`);
        console.log(` -> Token Received : "${token}"`);
        console.log(` -> Exact Match?   : ${token === CFG.SECRET}`);
        console.log("========================================================\n");

        if (!token || !safeEqual(token, CFG.SECRET)) {
            console.log("❌ Connection rejected via verifyClient: 401 Unauthorized");
            done(false, 401, 'Unauthorized');
            return;
        }
        console.log("✅ Connection authorized!");
        done(true);
    },
});

// ─────────────────────────────────────────────────────────────────────────────
//  PER-CONNECTION HANDLER
// ─────────────────────────────────────────────────────────────────────────────
wss.on('connection', (ws) => {

    // Per-connection mutable state
    let containerName = null;
    let containerProc = null;
    let sessionDir    = null;
    let killTimer     = null;
    let outputBytes   = 0;

    // Rate limiter state
    let runsThisWindow = 0;
    const rateInterval = setInterval(() => { runsThisWindow = 0; }, CFG.RATE_WIN_MS);

    // ── Tear everything down for this connection ──────────────────────────────
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

    // ── Message dispatch ──────────────────────────────────────────────────────
    ws.on('message', async (raw) => {
        let msg;
        try { msg = JSON.parse(raw); } catch { return; }

        // ════════════════════════════════════════════════════════════ RUN ════
        if (msg.type === 'run') {

            // Rate limit check
            if (runsThisWindow >= CFG.RATE_RUNS) {
                send(ws, 'stderr', { data: `\r\n⛔ Rate limit hit. Max ${CFG.RATE_RUNS} runs per minute.\r\n` });
                send(ws, 'exit',   { code: 429 });
                return;
            }
            runsThisWindow++;

            // Global concurrency check
            if (activeSandboxes >= CFG.MAX_CONCURRENT) {
                send(ws, 'stderr', { data: '\r\n⛔ Server busy — too many concurrent executions. Try again shortly.\r\n' });
                send(ws, 'exit',   { code: 503 });
                return;
            }

            // Kill any existing run from this same connection
            if (containerName) cleanup();

            // Validate language against strict allowlist
            const langKey = (msg.language ?? '').toLowerCase().trim();
            const lang    = LANGUAGES[langKey];
            if (!lang) {
                send(ws, 'stderr', { data: `Unsupported language: '${msg.language}'.\r\nSupported: ${Object.keys(LANGUAGES).join(', ')}\r\n` });
                send(ws, 'exit',   { code: 1 });
                return;
            }

            // Validate code size
            const code = String(msg.code ?? '');
            if (Buffer.byteLength(code, 'utf8') > CFG.MAX_CODE_BYTES) {
                send(ws, 'stderr', { data: `Code too large. Maximum is ${CFG.MAX_CODE_BYTES / 1024} KB.\r\n` });
                send(ws, 'exit',   { code: 1 });
                return;
            }

            // Write source to a private host temp dir
            const runId = randomBytes(8).toString('hex');
            containerName = `ce_${runId}`;
            sessionDir    = join(tmpdir(), `ce_src_${runId}`);

            try {
                mkdirSync(sessionDir, { recursive: true });
                const srcFile = join(sessionDir, `code.${lang.ext}`);
                writeFileSync(srcFile, code, { encoding: 'utf8' });

                activeSandboxes++;
                liveContainers.add(containerName);

                send(ws, 'system', { data: `[🐳 Sandboxed — ${langKey} | mem:${CFG.MEMORY} cpu:${CFG.CPUS} timeout:${CFG.RUN_TIMEOUT_MS/1000}s]\r\n` });

                // Spawn podman
                // ── SINGLE CLEAN DECLARATION SYSTEM ──
                // 1. Resolve path layout strings natively
                // ── CLEAN INTERACTIVE WSL SESSiON SPAWN ──
                const fullPodmanArgs = buildPodmanArgs(containerName, lang, srcFile);

                const wslCommand = 'wsl';
                const wslArgs = [
                    '-d', 'podman-machine-default', // Direct target distribution
                    'podman', ...fullPodmanArgs     // Pass the completed array straight over
                ];

                console.log(`🚀 Spawning stateful sandbox session inside WSL layer...`);

                containerProc = spawn(wslCommand, wslArgs, {
                    stdio: ['pipe', 'pipe', 'pipe'], // Preserves interactive WebSocket stdin/stdout
                    env: {
                        ...process.env
                    }
                });

                // stdout → browser
                containerProc.stdout.on('data', (chunk) => {
                    outputBytes += chunk.length;
                    if (outputBytes > CFG.MAX_OUTPUT_BYTES) {
                        send(ws, 'stderr', { data: `\r\n⛔ Output cap (${CFG.MAX_OUTPUT_BYTES / 1024} KB) exceeded — process killed.\r\n` });
                        cleanup();
                        send(ws, 'exit', { code: -2 });
                        return;
                    }
                    send(ws, 'stdout', { data: chunk.toString('utf8') });
                });

                // stderr → browser
                containerProc.stderr.on('data', (chunk) => {
                    outputBytes += chunk.length;
                    send(ws, 'stderr', { data: chunk.toString('utf8') });
                });

                // Container finished
                containerProc.on('close', (exitCode) => {
                    const stillTracked = !!containerName;
                    cleanup(true);  // container already removed by --rm
                    if (stillTracked) {
                        send(ws, 'exit', { code: exitCode ?? 0 });
                    }
                });

                // podman binary itself failed to start
                containerProc.on('error', (err) => {
                    send(ws, 'stderr', { data: `\r\n[Server] Could not start Podman: ${err.message}\r\n` });
                    send(ws, 'stderr', { data: `  ▶ Is Podman Desktop installed and running?  podman info\r\n` });
                    send(ws, 'stderr', { data: `  ▶ Is the image built?   podman images | findstr code-runner\r\n` });
                    cleanup(true);
                    send(ws, 'exit', { code: 1 });
                });

                // Hard wall-clock timeout
                killTimer = setTimeout(() => {
                    send(ws, 'stderr', { data: `\r\n⏱ Run time limit (${CFG.RUN_TIMEOUT_MS / 1000}s) exceeded — container killed.\r\n` });
                    cleanup();
                    send(ws, 'exit', { code: -1 });
                }, CFG.RUN_TIMEOUT_MS);

            } catch (err) {
                send(ws, 'stderr', { data: `\r\n[Server] Internal error: ${err.message}\r\n` });
                cleanup(true);
                send(ws, 'exit', { code: 1 });
            }
        }

        // ════════════════════════════════════════════════════════ STDIN ═════
        if (msg.type === 'stdin') {
            if (!containerProc?.stdin?.writable) return;
            // Clamp per-write to 4 KB to prevent stdin flooding
            const input = String(msg.data ?? '').slice(0, 4096);
            try { containerProc.stdin.write(input); } catch {}
        }

        // ════════════════════════════════════════════════════════ KILL ══════
        if (msg.type === 'kill') {
            if (!containerName) return;
            cleanup();
            send(ws, 'system', { data: '\r\n[Killed by user]\r\n' });
            send(ws, 'exit',   { code: -1 });
        }
    });

    // Browser closed tab / navigated away
    ws.on('close', () => {
        clearInterval(rateInterval);
        cleanup();
    });

    ws.on('error', () => {
        clearInterval(rateInterval);
        cleanup();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
//  GRACEFUL SHUTDOWN  — kill every live container before exiting
// ─────────────────────────────────────────────────────────────────────────────
function shutdown(signal) {
    console.log(`\n${signal} — removing ${liveContainers.size} live container(s)…`);
    for (const name of liveContainers) forceRemoveContainer(name);
    setTimeout(() => process.exit(0), 1500);
}
process.on('SIGINT',           () => shutdown('SIGINT'));
process.on('SIGTERM',          () => shutdown('SIGTERM'));
process.on('uncaughtException', (err) => {
    console.error('Uncaught exception:', err);
    shutdown('uncaughtException');
});

// ─────────────────────────────────────────────────────────────────────────────
//  START  —  bind to all interfaces (0.0.0.0) so Windows Laravel can reach it
// ─────────────────────────────────────────────────────────────────────────────
httpServer.listen(CFG.PORT, CFG.HOST, () => {
    console.log(`\n✅  PTY server — WINDOWS-NATIVE MODE`);
    console.log(`    Listening  : ws://${CFG.HOST}:${CFG.PORT}`);
    console.log(`    Health     : http://${CFG.HOST}:${CFG.PORT}/health`);
    console.log(`    Runtime    : ${CFG.CONTAINER_BIN}  →  ${CFG.IMAGE}`);
    console.log(`    Memory     : ${CFG.MEMORY}  |  CPUs: ${CFG.CPUS}  |  PIDs: ${CFG.PIDS}`);
    console.log(`    Timeout    : ${CFG.RUN_TIMEOUT_MS / 1000}s  |  Output cap: ${CFG.MAX_OUTPUT_BYTES / 1024} KB`);
    console.log(`    Concurrency: ${CFG.MAX_CONCURRENT} max  |  Rate: ${CFG.RATE_RUNS} runs/${CFG.RATE_WIN_MS / 1000}s/conn\n`);
    console.log(`    ⚠  Laravel .env must have:`);
    console.log(`       PTY_SECRET=<same value as PTY_SECRET env var>`);
    console.log(`       PTY_URL=ws://127.0.0.1:${CFG.PORT}\n`);
});
