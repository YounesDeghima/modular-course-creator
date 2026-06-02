/**
 * devpanel-server.mjs  —  modular-course-creator dev panel backend
 * Usage:  node devpanel-server.mjs
 * Opens browser at http://localhost:7700 automatically.
 * Pure Node.js built-ins only — no npm install needed.
 */

import { createServer }              from 'http';
import { exec, spawn }               from 'child_process';
import { readFileSync, existsSync,
         writeFileSync, mkdirSync }  from 'fs';
import { join, dirname }             from 'path';
import { fileURLToPath }             from 'url';
import { platform }                  from 'os';
import { createHash }                from 'crypto';

const __dir  = dirname(fileURLToPath(import.meta.url));
const IS_WIN = platform() === 'win32';
const IS_MAC = platform() === 'darwin';
const OS     = IS_WIN ? 'windows' : IS_MAC ? 'mac' : 'linux';
const PORT   = 7700;

// ─── Core helpers ─────────────────────────────────────────────────────────────
function sh(cmd, opts = {}) {
  return new Promise(resolve => {
    exec(cmd, { timeout: 60000, shell: IS_WIN ? 'cmd.exe' : '/bin/bash', ...opts },
      (err, stdout, stderr) => resolve({
        ok: !err,
        stdout: (stdout || '').trim(),
        stderr: (stderr || '').trim(),
        code: err?.code,
      }));
  });
}

function sseStream(res) {
  res.writeHead(200, {
    'Content-Type':  'text/event-stream',
    'Cache-Control': 'no-cache',
    'Connection':    'keep-alive',
    'Access-Control-Allow-Origin': '*',
  });
  const send = (line, type = 'out') => {
    if (!res.writableEnded)
      res.write(`data: ${JSON.stringify({ line: String(line), type })}\n\n`);
  };
  return {
    out: (msg)        => send(msg, 'out'),
    ok:  (msg)        => send(msg, 'ok'),
    err: (msg)        => send(msg, 'err'),
    warn:(msg)        => send(msg, 'warn'),
    sys: (msg)        => send(msg, 'sys'),
    info:(msg)        => send(msg, 'info'),
    line:(msg, type)  => send(msg, type || 'out'),
    done:(code = 0)   => { send(`[EXIT:${code}]`, 'exit'); if (!res.writableEnded) res.end(); },
  };
}

function spawnStream(sse, cmd, args, opts = {}) {
  return new Promise(resolve => {
    const proc = spawn(cmd, args, { shell: true, ...opts });
    const emit = (data, type) =>
      data.toString().split('\n').map(l => l.trim()).filter(Boolean)
          .forEach(l => sse.line(l, type));
    proc.stdout.on('data', d => emit(d, 'out'));
    proc.stderr.on('data', d => emit(d, 'err'));
    proc.on('close', code => resolve(code ?? 0));
    proc.on('error', err  => { sse.err(`[error] ${err.message}`); resolve(1); });
  });
}

function jsonRes(res, data, status = 200) {
  res.writeHead(status, {
    'Content-Type': 'application/json',
    'Access-Control-Allow-Origin': '*',
  });
  res.end(JSON.stringify(data));
}

// ─── .env helpers ─────────────────────────────────────────────────────────────
function readEnv() {
  const p = join(__dir, '.env');
  if (!existsSync(p)) return {};
  const env = {};
  for (const raw of readFileSync(p, 'utf8').split('\n')) {
    const line = raw.trim();
    if (!line || line.startsWith('#')) continue;
    const eq = line.indexOf('=');
    if (eq < 0) continue;
    let val = line.slice(eq + 1).trim();
    if ((val.startsWith('"') && val.endsWith('"')) ||
        (val.startsWith("'") && val.endsWith("'"))) val = val.slice(1, -1);
    env[line.slice(0, eq).trim()] = val;
  }
  return env;
}

function setEnvKey(key, value) {
  const p = join(__dir, '.env');
  if (!existsSync(p)) writeFileSync(p, '');
  let c = readFileSync(p, 'utf8');
  const rx = new RegExp(`^${key}=.*$`, 'm');
  if (rx.test(c)) c = c.replace(rx, `${key}=${value}`);
  else c = c.trimEnd() + `\n${key}=${value}\n`;
  writeFileSync(p, c);
}

function ensureEnvFile() {
  const p = join(__dir, '.env');
  if (!existsSync(p)) {
    const ex = join(__dir, '.env.example');
    writeFileSync(p, existsSync(ex) ? readFileSync(ex)
      : 'APP_ENV=local\nAPP_DEBUG=true\nDB_CONNECTION=sqlite\nDB_DATABASE=database/database.sqlite\nQUEUE_CONNECTION=database\n');
    return true;
  }
  return false;
}

function ensureDbFile() {
  const dir  = join(__dir, 'database');
  const file = join(dir, 'database.sqlite');
  if (!existsSync(dir))  mkdirSync(dir, { recursive: true });
  if (!existsSync(file)) { writeFileSync(file, ''); return true; }
  return false;
}

// ─── VERSION PARSING ──────────────────────────────────────────────────────────
function parseVer(str) {
  const m = String(str).match(/(\d+)\.(\d+)\.?(\d*)/);
  if (!m) return null;
  return { major: +m[1], minor: +m[2], patch: +(m[3] || 0), str: `${m[1]}.${m[2]}.${m[3]||0}` };
}

function verGte(v, min) { // v >= [major,minor]
  if (!v) return false;
  if (v.major !== min[0]) return v.major > min[0];
  return v.minor >= min[1];
}
function verLt(v, max) {  // v < [major,minor]
  if (!v) return false;
  if (v.major !== max[0]) return v.major < max[0];
  return v.minor < max[1];
}

// ─── PYTHON COMPATIBILITY ENGINE ──────────────────────────────────────────────
// MinerU: >=3.10, <3.14  (verified from PyPI metadata)
const PY_MIN = [3, 10];
const PY_MAX = [3, 14]; // exclusive

function pyCompatible(v) {
  if (!v || v.major !== 3) return false;
  return verGte(v, PY_MIN) && verLt(v, PY_MAX);
}

async function probePython(cmd) {
  const r = await sh(`${cmd} --version 2>&1`);
  if (!r.ok && !r.stdout && !r.stderr) return null;
  const v = parseVer(r.stdout + ' ' + r.stderr);
  if (!v || v.major !== 3) return null;
  return { bin: cmd, version: v, compatible: pyCompatible(v) };
}

async function findCompatiblePython() {
  const candidates = IS_WIN
    ? ['py -3.13','py -3.12','py -3.11','py -3.10',
       'python3.13','python3.12','python3.11','python3.10','python3','python']
    : ['python3.13','python3.12','python3.11','python3.10','python3','python'];

  for (const c of candidates) {
    const p = await probePython(c);
    if (p?.compatible) return p;
  }

  // Windows: probe common install dirs
  if (IS_WIN) {
    const roots = ['C:\\Python', 'C:\\Program Files\\Python',
                   `${process.env.LOCALAPPDATA || ''}\\Programs\\Python\\Python`];
    for (const minor of [13, 12, 11, 10]) {
      for (const root of roots) {
        const exe = `${root}${3}${minor < 10 ? '0' : ''}${minor}\\python.exe`;
        const p = await probePython(`"${exe}"`);
        if (p?.compatible) return p;
      }
    }
  }
  return null;
}

async function installCompatiblePythonForVenv(sse) {
  // Returns a compatible bin string or null
  sse.warn('[panel] No compatible Python (3.10–3.13) found. Attempting to install Python 3.12...');

  if (IS_WIN) {
    for (const minor of [12, 11, 10]) {
      sse.sys(`[panel] winget install Python.Python.3.${minor}...`);
      const code = await spawnStream(sse,
        'winget', ['install', '-e', '--id', `Python.Python.3.${minor}`,
                   '--accept-package-agreements', '--accept-source-agreements', '--silent'], {});
      if (code === 0) {
        // py launcher needs a moment
        await new Promise(r => setTimeout(r, 2000));
        const p = await probePython(`py -3.${minor}`);
        if (p?.compatible) { sse.ok(`[panel] Python 3.${minor} installed via winget.`); return `py -3.${minor}`; }
      }
    }
    sse.err('[panel] winget install failed. Please install Python 3.12 from https://www.python.org/downloads/');
    sse.warn('[panel] Check "Add to PATH" during install, then re-run this step.');
    return null;
  }

  if (IS_MAC) {
    const brewOk = (await sh('which brew 2>&1')).ok;
    if (!brewOk) { sse.err('[panel] Homebrew not found. Install from https://brew.sh then run: brew install python@3.12'); return null; }
    sse.sys('[panel] brew install python@3.12...');
    const code = await spawnStream(sse, 'brew', ['install', 'python@3.12'], {});
    if (code === 0) {
      const p = await probePython('python3.12');
      if (p?.compatible) return 'python3.12';
    }
    return null;
  }

  // Linux
  sse.sys('[panel] apt-get: adding deadsnakes PPA for Python 3.12...');
  await spawnStream(sse, 'apt-get', ['-qq', 'install', '-y', 'software-properties-common'], {});
  await spawnStream(sse, 'add-apt-repository', ['-y', 'ppa:deadsnakes/ppa'], {});
  await spawnStream(sse, 'apt-get', ['update', '-qq'], {});

  for (const minor of [12, 11, 10]) {
    sse.sys(`[panel] apt-get install python3.${minor} python3.${minor}-venv...`);
    const code = await spawnStream(sse,
      'apt-get', ['-qq', 'install', '-y', `python3.${minor}`, `python3.${minor}-venv`], {});
    if (code === 0) {
      const p = await probePython(`python3.${minor}`);
      if (p?.compatible) { sse.ok(`[panel] Python 3.${minor} installed.`); return `python3.${minor}`; }
    }
  }
  sse.err('[panel] Could not install a compatible Python automatically.');
  return null;
}

async function setupMineruVenv(sse) {
  // The authoritative function for Python+MinerU setup. Returns exit code.
  const scriptsDir = join(__dir, 'scripts');
  const venvDir    = join(scriptsDir, '.venv');
  const venvPy     = IS_WIN
    ? join(venvDir, 'Scripts', 'python.exe')
    : join(venvDir, 'bin', 'python3');
  const venvPip    = IS_WIN
    ? join(venvDir, 'Scripts', 'pip.exe')
    : join(venvDir, 'bin', 'pip');

  if (!existsSync(scriptsDir)) mkdirSync(scriptsDir, { recursive: true });

  // 1. If venv exists, check its Python version
  if (existsSync(venvPy)) {
    const venvProbe = await probePython(`"${venvPy}"`);
    if (venvProbe && !venvProbe.compatible) {
      sse.err(`[panel] Existing venv uses Python ${venvProbe.version.str} which is INCOMPATIBLE with MinerU (needs 3.10–3.13).`);
      sse.warn('[panel] Deleting bad venv and rebuilding with a compatible Python...');
      const rmCmd = IS_WIN ? `rmdir /s /q "${venvDir}"` : `rm -rf "${venvDir}"`;
      await sh(rmCmd);
    } else if (venvProbe?.compatible) {
      sse.sys(`[panel] Existing venv: Python ${venvProbe.version.str} (compatible). Checking MinerU...`);
      const check = await sh(`"${venvPy}" -c "import mineru; print('ok')" 2>&1`);
      if (check.ok && check.stdout.includes('ok')) {
        sse.ok('[panel] MinerU already installed and working.');
        return 0;
      }
      sse.sys('[panel] MinerU missing from venv. Installing...');
      const code = await spawnStream(sse, venvPip, ['install', 'mineru'], { cwd: scriptsDir });
      if (code === 0) { sse.ok('[panel] MinerU installed.'); return 0; }
      sse.err('[panel] Install failed — will rebuild venv.');
      await sh(IS_WIN ? `rmdir /s /q "${venvDir}"` : `rm -rf "${venvDir}"`);
    }
  }

  // 2. Find compatible Python
  sse.sys('[panel] Scanning for compatible Python (3.10–3.13)...');
  let pyEntry = await findCompatiblePython();

  if (!pyEntry) {
    // Show what we found that's incompatible
    const sysProbe = await probePython(IS_WIN ? 'python' : 'python3');
    if (sysProbe) {
      sse.err(`[panel] Found Python ${sysProbe.version.str} but MinerU requires <3.14.`);
    } else {
      sse.err('[panel] No Python found at all.');
    }
    const installed = await installCompatiblePythonForVenv(sse);
    if (!installed) return 1;
    pyEntry = await probePython(installed);
    if (!pyEntry?.compatible) { sse.err('[panel] Newly installed Python still incompatible.'); return 1; }
  }

  sse.ok(`[panel] Using Python ${pyEntry.version.str} at: ${pyEntry.bin}`);

  // 3. Ensure venv module available (Linux)
  if (!IS_WIN && !IS_MAC) {
    // Extract base command e.g. python3.12 from "python3.12"
    const base = pyEntry.bin.replace(/\s.*/, '');
    await spawnStream(sse, 'apt-get', ['-qq', 'install', '-y', `${base}-venv`], {});
  }

  // 4. Create venv
  sse.sys(`[panel] Creating venv: ${pyEntry.bin} -m venv ${venvDir}`);
  const mkCode = await spawnStream(sse, pyEntry.bin, ['-m', 'venv', `"${venvDir}"`],
    { cwd: scriptsDir, shell: true });
  if (mkCode !== 0) {
    sse.err('[panel] venv creation failed.');
    if (!IS_WIN && !IS_MAC) sse.warn('[panel] Try: apt-get install python3.12-venv');
    return 1;
  }

  // 5. Verify venv Python version
  const createdProbe = await probePython(`"${venvPy}"`);
  if (!createdProbe) {
    sse.err('[panel] Could not probe venv python after creation.');
    return 1;
  }
  sse.ok(`[panel] venv created with Python ${createdProbe.version.str}`);
  if (!createdProbe.compatible) {
    sse.err(`[panel] venv Python ${createdProbe.version.str} is incompatible — aborting.`);
    return 1;
  }

  // 6. Upgrade pip inside venv
  sse.sys('[panel] Upgrading pip...');
  await spawnStream(sse, `"${venvPip}"`, ['install', '--upgrade', 'pip'], { cwd: scriptsDir, shell: true });

  // 7. Install MinerU
  sse.sys('[panel] Installing MinerU...');
  const installCode = await spawnStream(sse, `"${venvPip}"`, ['install', 'mineru'],
    { cwd: scriptsDir, shell: true });
  if (installCode !== 0) {
    sse.err('[panel] MinerU install failed. See output above for details.');
    return 1;
  }

  // 8. Verify import
  const verify = await sh(`"${venvPy}" -c "import mineru; print('ok')" 2>&1`);
  if (verify.ok && verify.stdout.includes('ok')) {
    sse.ok('[panel] MinerU import verified successfully.');
    return 0;
  }
  sse.err(`[panel] MinerU import verification failed: ${verify.stdout} ${verify.stderr}`);
  return 1;
}

// ─── CHECKS ───────────────────────────────────────────────────────────────────
async function checkPhp() {
  const r = await sh('php -r "echo PHP_VERSION;" 2>&1');
  if (!r.ok || !r.stdout) return { status: 'err', version: null, detail: 'php not found in PATH' };
  const v = parseVer(r.stdout);
  if (!v) return { status: 'err', version: null, detail: `Could not parse PHP version from: ${r.stdout}` };
  if (!verGte(v, [8, 2])) return { status: 'warn', version: v.str, detail: `PHP ${v.str} found but 8.2+ required` };
  const ext = await sh('php -m 2>&1');
  const missing = ['mbstring','xml','sqlite3','curl'].filter(e => !ext.stdout.toLowerCase().includes(e));
  if (missing.length) return { status: 'warn', version: v.str, detail: `PHP ${v.str} — missing extensions: ${missing.join(', ')}` };
  return { status: 'ok', version: v.str, detail: `PHP ${v.str} with required extensions` };
}

async function checkComposer() {
  const r = await sh('composer --version --no-ansi 2>&1');
  if (!r.ok || !r.stdout) return { status: 'err', version: null, detail: 'composer not found in PATH' };
  const m = r.stdout.match(/Composer version ([\d.]+)/);
  const v = parseVer(m ? m[1] : r.stdout);
  if (!v) return { status: 'warn', version: '?', detail: r.stdout.split('\n')[0] };
  if (!verGte(v, [2, 0])) return { status: 'warn', version: v.str, detail: `Composer ${v.str} — version 2+ recommended` };
  return { status: 'ok', version: v.str, detail: `Composer ${v.str}` };
}

async function checkNode() {
  const r = await sh('node --version 2>&1');
  if (!r.ok || !r.stdout) return { status: 'err', version: null, detail: 'node not found in PATH' };
  const v = parseVer(r.stdout);
  if (!v) return { status: 'err', version: null, detail: `Could not parse Node version: ${r.stdout}` };
  if (!verGte(v, [18, 0])) return { status: 'warn', version: v.str, detail: `Node ${v.str} found — 18+ required` };
  return { status: 'ok', version: v.str, detail: `Node.js v${v.str}` };
}

async function checkPodman() {
  const pr = await sh('podman --version 2>&1');
  if (pr.ok && pr.stdout) {
    const v = parseVer(pr.stdout);
    // Linux: podman is rootless, no machine needed
    if (!IS_WIN && !IS_MAC) return { status: 'ok', version: v?.str, detail: `Podman ${v?.str}`, bin: 'podman', sub: 'ok' };
    // Windows/Mac: need a running machine
    const ml = await sh('podman machine list --format json 2>&1');
    if (ml.ok) {
      try {
        const ms = JSON.parse(ml.stdout || '[]');
        const running = ms.some(m => m.Running || m.State === 'running');
        if (!running && ms.length > 0) return { status: 'warn', version: v?.str, detail: `Podman ${v?.str} installed — machine exists but not running`, bin: 'podman', sub: 'machine_stopped' };
        if (ms.length === 0)           return { status: 'warn', version: v?.str, detail: `Podman ${v?.str} installed — no machine created yet`, bin: 'podman', sub: 'no_machine' };
      } catch {}
    }
    return { status: 'ok', version: v?.str, detail: `Podman ${v?.str} running`, bin: 'podman', sub: 'ok' };
  }
  const dr = await sh('docker --version 2>&1');
  if (dr.ok && dr.stdout) {
    const v = parseVer(dr.stdout);
    const di = await sh('docker info 2>&1');
    if (!di.ok) return { status: 'warn', version: v?.str, detail: `Docker ${v?.str} installed — daemon not running`, bin: 'docker', sub: 'daemon_stopped' };
    return { status: 'ok', version: v?.str, detail: `Docker ${v?.str} running`, bin: 'docker', sub: 'ok' };
  }
  return { status: 'err', version: null, detail: 'Neither podman nor docker found in PATH', sub: 'not_installed' };
}

async function checkCodeImage() {
  for (const bin of ['podman', 'docker']) {
    const r = await sh(`${bin} images --format json localhost/code-runner:latest 2>&1`);
    if (r.ok && r.stdout.includes('code-runner')) {
      try {
        const imgs = JSON.parse(r.stdout);
        if (imgs.length > 0) {
          const sz = imgs[0].Size ? Math.round(imgs[0].Size / 1024 / 1024) + 'MB' : '?';
          return { status: 'ok', version: sz, detail: `code-runner image ${sz} via ${bin}` };
        }
      } catch {}
    }
    // Fallback format
    const r2 = await sh(`${bin} images --format "{{.Repository}}:{{.Tag}}" 2>&1`);
    if (r2.ok && r2.stdout.includes('code-runner')) {
      return { status: 'ok', version: 'built', detail: `code-runner image found (${bin})` };
    }
  }
  return { status: 'err', version: null, detail: 'Image not built yet — run the build action' };
}

async function checkLaravelDeps() {
  const missing = [];
  if (!existsSync(join(__dir, 'vendor')))                       missing.push('vendor/');
  if (!existsSync(join(__dir, 'node_modules')))                 missing.push('node_modules/');
  if (!existsSync(join(__dir, '.env')))                         missing.push('.env');
  if (!existsSync(join(__dir, 'database', 'database.sqlite')))  missing.push('database.sqlite');
  if (missing.length) return { status: missing.length >= 3 ? 'err' : 'warn', version: null, detail: 'Missing: ' + missing.join(', ') };
  const env = readEnv();
  if (!env.APP_KEY) return { status: 'warn', version: 'partial', detail: 'APP_KEY not set — run: php artisan key:generate' };
  return { status: 'ok', version: 'ready', detail: 'vendor, node_modules, .env, APP_KEY, sqlite all present' };
}

async function checkOllama() {
  const w = await sh(IS_WIN ? 'where ollama 2>&1' : 'which ollama 2>&1');
  if (!w.ok) return { status: 'err', version: null, detail: 'ollama not found in PATH', sub: 'not_installed' };
  const api = await sh('curl -s --max-time 3 http://localhost:11434/api/tags 2>&1');
  if (!api.ok || !api.stdout.trim())
    return { status: 'warn', version: 'installed', detail: 'Ollama installed but not running on :11434', sub: 'not_running' };
  try {
    const data = JSON.parse(api.stdout);
    const models = (data.models || []).map(m => m.name || m.model).join(', ') || 'none pulled yet';
    const hasPhi4 = models.includes('phi4');
    return { status: 'ok', version: 'running', detail: `Ollama running — models: ${models}`, sub: hasPhi4 ? 'ok' : 'no_model', models };
  } catch {
    return { status: 'ok', version: 'running', detail: 'Ollama API responding on :11434', sub: 'ok' };
  }
}

async function checkPythonMineru() {
  const venvPy = IS_WIN
    ? join(__dir, 'scripts', '.venv', 'Scripts', 'python.exe')
    : join(__dir, 'scripts', '.venv', 'bin', 'python3');

  if (!existsSync(venvPy)) {
    // Report what Python is available and whether it's compatible
    const found = await findCompatiblePython();
    const sys   = await probePython(IS_WIN ? 'python' : 'python3');
    if (found) return { status: 'warn', version: found.version.str, detail: `Python ${found.version.str} available but scripts/.venv not created yet` };
    if (sys && !sys.compatible) return { status: 'err', version: sys.version.str, detail: `Python ${sys.version.str} found but incompatible with MinerU (needs 3.10–3.13). Use the install action.` };
    return { status: 'err', version: null, detail: 'Python not found. Use the install action to set up a compatible version.' };
  }

  const probe = await probePython(`"${venvPy}"`);
  if (probe && !probe.compatible) {
    return { status: 'err', version: probe.version.str, detail: `Venv uses Python ${probe.version.str} — incompatible with MinerU. Re-run the install action to rebuild.` };
  }

  const check = await sh(`"${venvPy}" -c "import mineru; print('ok')" 2>&1`);
  if (!check.ok || !check.stdout.includes('ok'))
    return { status: 'warn', version: probe?.version.str, detail: `venv exists (Python ${probe?.version.str}) but MinerU not installed` };
  return { status: 'ok', version: probe?.version.str, detail: `Python ${probe?.version.str} + MinerU at scripts/.venv` };
}

// ─── INSTALL ACTIONS ──────────────────────────────────────────────────────────
async function installPhp(res) {
  const sse = sseStream(res);
  sse.sys('[panel] Installing PHP 8.2+...');
  if (IS_WIN) {
    sse.warn('[panel] Windows: trying winget install of PHP...');
    // PHP on Windows via winget (XAMPP or standalone)
    const code = await spawnStream(sse,
      'winget', ['install', '-e', '--id', 'XAMPP.XAMPP',
                 '--accept-package-agreements', '--accept-source-agreements'], {});
    if (code !== 0) {
      sse.warn('[panel] XAMPP winget failed. Alternative:');
      sse.info('[panel] Download PHP from https://windows.php.net/download/');
      sse.info('[panel] Extract to C:\\php, add C:\\php to your PATH, then re-check.');
    }
    sse.done(code); return;
  }
  if (IS_MAC) {
    sse.sys('[panel] Installing PHP via Homebrew...');
    const code = await spawnStream(sse, 'brew', ['install', 'php'], {});
    sse.done(code); return;
  }
  // Linux: use ondrej/php PPA for latest
  sse.sys('[panel] Adding ondrej/php PPA...');
  await spawnStream(sse, 'apt-get', ['-qq', 'install', '-y', 'software-properties-common'], {});
  await spawnStream(sse, 'add-apt-repository', ['-y', 'ppa:ondrej/php'], {});
  await spawnStream(sse, 'apt-get', ['update', '-qq'], {});
  const code = await spawnStream(sse,
    'apt-get', ['-qq', 'install', '-y',
      'php8.2', 'php8.2-cli', 'php8.2-mbstring',
      'php8.2-xml', 'php8.2-sqlite3', 'php8.2-curl', 'php8.2-zip'], {});
  sse.line(code === 0 ? '[panel] PHP 8.2 installed.' : '[panel] PHP install failed.', code === 0 ? 'ok' : 'err');
  sse.done(code);
}

async function installComposer(res) {
  const sse = sseStream(res);
  sse.sys('[panel] Installing Composer 2...');
  if (IS_WIN) {
    sse.warn('[panel] Windows: download Composer-Setup.exe from https://getcomposer.org/download/');
    sse.info('[panel] The installer adds composer to PATH automatically.');
    sse.done(0); return;
  }
  const dl = await sh('curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php');
  if (!dl.ok) { sse.err(`[panel] Download failed: ${dl.stderr}`); sse.done(1); return; }
  const install = await sh('php /tmp/composer-setup.php --install-dir=/tmp --filename=composer');
  if (!install.ok) { sse.err(`[panel] Installer failed: ${install.stderr}`); sse.done(1); return; }
  const mv = await sh('mv /tmp/composer /usr/local/bin/composer && chmod +x /usr/local/bin/composer');
  if (!mv.ok) {
    sse.warn('[panel] Could not write to /usr/local/bin — installing to ~/.local/bin...');
    await sh('mkdir -p ~/.local/bin && mv /tmp/composer ~/.local/bin/composer && chmod +x ~/.local/bin/composer');
    sse.warn('[panel] Add ~/.local/bin to your PATH if not already present.');
  } else {
    sse.ok('[panel] Composer installed at /usr/local/bin/composer');
  }
  sse.done(0);
}

async function installNode(res) {
  const sse = sseStream(res);
  sse.sys('[panel] Installing Node.js LTS...');
  if (IS_WIN) {
    sse.warn('[panel] Windows: trying winget...');
    const code = await spawnStream(sse,
      'winget', ['install', '-e', '--id', 'OpenJS.NodeJS.LTS',
                 '--accept-package-agreements', '--accept-source-agreements'], {});
    if (code !== 0) sse.warn('[panel] winget failed. Download from https://nodejs.org/en/download/');
    sse.done(code); return;
  }
  if (IS_MAC) {
    const code = await spawnStream(sse, 'brew', ['install', 'node'], {});
    sse.done(code); return;
  }
  sse.sys('[panel] Installing via NodeSource LTS...');
  const setup = await sh('curl -fsSL https://deb.nodesource.com/setup_lts.x | bash -');
  if (!setup.ok) {
    sse.warn('[panel] NodeSource setup failed — trying snap...');
    const code = await spawnStream(sse, 'snap', ['install', 'node', '--classic', '--channel=lts/stable'], {});
    sse.done(code); return;
  }
  const code = await spawnStream(sse, 'apt-get', ['-qq', 'install', '-y', 'nodejs'], {});
  sse.done(code);
}

async function installPodman(res) {
  const sse = sseStream(res);
  sse.sys('[panel] Installing Podman...');
  if (IS_WIN) {
    const code = await spawnStream(sse,
      'winget', ['install', '-e', '--id', 'RedHat.Podman',
                 '--accept-package-agreements', '--accept-source-agreements'], {});
    if (code === 0) {
      sse.ok('[panel] Podman installed.');
      sse.info('[panel] Run: podman machine init && podman machine start');
    } else {
      sse.warn('[panel] winget failed. Download from https://podman-desktop.io/downloads');
    }
    sse.done(code); return;
  }
  if (IS_MAC) {
    let c = await spawnStream(sse, 'brew', ['install', 'podman'], {});
    if (c !== 0) { sse.done(c); return; }
    await spawnStream(sse, 'podman', ['machine', 'init'], {});
    c = await spawnStream(sse, 'podman', ['machine', 'start'], {});
    sse.done(c); return;
  }
  const code = await spawnStream(sse, 'apt-get', ['-qq', 'install', '-y', 'podman'], {});
  sse.line(code === 0 ? '[panel] Podman installed.' : '[panel] Podman install failed.', code === 0 ? 'ok' : 'err');
  sse.done(code);
}

async function installOllama(res) {
  const sse = sseStream(res);
  sse.sys('[panel] Installing Ollama...');
  if (IS_WIN) {
    sse.warn('[panel] Windows: download OllamaSetup.exe from https://ollama.com/download/windows');
    sse.info('[panel] After install, Ollama starts automatically. Then: ollama pull phi4');
    sse.done(0); return;
  }
  const code = await spawnStream(sse, '/bin/bash', ['-c', 'curl -fsSL https://ollama.com/install.sh | sh'], {});
  if (code !== 0) { sse.done(code); return; }
  sse.ok('[panel] Ollama installed. Starting service...');
  spawn('ollama', ['serve'], { detached: true, stdio: 'ignore' }).unref();
  await new Promise(r => setTimeout(r, 2500));
  sse.sys('[panel] Pulling phi4 model (may take several minutes)...');
  const pullCode = await spawnStream(sse, 'ollama', ['pull', 'phi4'], {});
  sse.done(pullCode);
}

async function installPythonMineru(res) {
  const sse = sseStream(res);
  sse.sys('[panel] Setting up Python + MinerU...');
  const code = await setupMineruVenv(sse);
  sse.done(code);
}

async function podmanMachineStart(res) {
  const sse = sseStream(res);
  sse.sys('[panel] Starting Podman machine...');
  const code = await spawnStream(sse, 'podman', ['machine', 'start'], {});
  if (code === 0) {
    sse.ok('[panel] Podman machine started.');
    // Verify
    const check = await checkPodman();
    sse.line(`[panel] Status: ${check.detail}`, check.status === 'ok' ? 'ok' : 'warn');
  } else {
    sse.err('[panel] podman machine start failed.');
    sse.warn('[panel] Try initializing first: click "Init machine"');
  }
  sse.done(code);
}

async function podmanMachineInit(res) {
  const sse = sseStream(res);
  sse.sys('[panel] Initializing Podman machine...');
  const initCode = await spawnStream(sse, 'podman', ['machine', 'init'], {});
  if (initCode !== 0) { sse.err('[panel] Init failed.'); sse.done(initCode); return; }
  sse.sys('[panel] Starting Podman machine...');
  const startCode = await spawnStream(sse, 'podman', ['machine', 'start'], {});
  sse.line(startCode === 0 ? '[panel] Podman machine initialized and started.' : '[panel] Start failed after init.', startCode === 0 ? 'ok' : 'err');
  sse.done(startCode);
}

async function ollamaStart(res) {
  const sse = sseStream(res);
  sse.sys('[panel] Starting Ollama...');
  // Check if already running
  const api = await sh('curl -s --max-time 2 http://localhost:11434/api/tags 2>&1');
  if (api.ok && api.stdout.trim()) { sse.ok('[panel] Ollama is already running on :11434.'); sse.done(0); return; }
  spawn('ollama', ['serve'], { detached: true, stdio: 'ignore' }).unref();
  sse.line('[panel] ollama serve launched in background. Waiting for API...');
  for (let i = 0; i < 10; i++) {
    await new Promise(r => setTimeout(r, 1000));
    const ping = await sh('curl -s --max-time 1 http://localhost:11434/api/tags 2>&1');
    if (ping.ok && ping.stdout.trim()) { sse.ok(`[panel] Ollama API up after ${i+1}s.`); sse.done(0); return; }
    sse.line(`[panel] Waiting... (${i+1}/10)`, 'info');
  }
  sse.warn('[panel] Ollama may still be starting. Re-check in a moment.');
  sse.done(0);
}

async function fixPodman(res) {
  // Smart fix: detect what's wrong and fix it
  const sse = sseStream(res);
  sse.sys('[panel] Running Podman fix...');
  const check = await checkPodman();
  sse.line(`[panel] Current state: ${check.detail}`, 'info');
  if (check.sub === 'not_installed') {
    sse.sys('[panel] Podman not installed — installing...');
    await installPodman(res); return;
  }
  if (check.sub === 'no_machine') {
    sse.sys('[panel] No machine found — running podman machine init + start...');
    await spawnStream(sse, 'podman', ['machine', 'init'], {});
    const c = await spawnStream(sse, 'podman', ['machine', 'start'], {});
    sse.line(c === 0 ? '[panel] Machine initialized and started.' : '[panel] Failed.', c === 0 ? 'ok' : 'err');
    sse.done(c); return;
  }
  if (check.sub === 'machine_stopped') {
    sse.sys('[panel] Machine exists but stopped — running podman machine start...');
    const c = await spawnStream(sse, 'podman', ['machine', 'start'], {});
    sse.line(c === 0 ? '[panel] Machine started.' : '[panel] Start failed — try podman machine init', c === 0 ? 'ok' : 'err');
    sse.done(c); return;
  }
  if (check.sub === 'daemon_stopped') {
    sse.warn('[panel] Docker daemon not running.');
    sse.info('[panel] On Windows/Mac: open Docker Desktop. On Linux: sudo systemctl start docker');
    sse.done(0); return;
  }
  sse.ok('[panel] Podman appears to be working fine.');
  sse.done(0);
}

async function buildCodeImage(res) {
  const sse = sseStream(res);
  sse.sys('[panel] Building code-runner container image...');
  sse.warn('[panel] First build: 5-15 min (downloads Rust, Go, Java, etc.)');
  const pr = await sh('podman --version 2>&1');
  const bin = pr.ok ? 'podman' : 'docker';
  const ok  = pr.ok || (await sh('docker --version 2>&1')).ok;
  if (!ok) { sse.err('[panel] Neither podman nor docker found. Install one first.'); sse.done(1); return; }
  sse.sys(`[panel] Using: ${bin}`);
  const ptyDir = join(__dir, 'pty-server');
  const code = await spawnStream(sse, bin,
    ['build', '-t', 'localhost/code-runner:latest', '-f', 'Containerfile', '.'],
    { cwd: ptyDir });
  if (code === 0) {
    sse.ok('[panel] Image built.');
    const test = await sh(`${bin} run --rm localhost/code-runner:latest python3 -c "print('smoke OK')" 2>&1`);
    sse.line(`[panel] Smoke test: ${test.ok ? test.stdout : test.stderr}`, test.ok ? 'ok' : 'warn');
  }
  sse.done(code);
}

async function runProjectSetup(res) {
  const sse = sseStream(res);
  sse.sys('[panel] Running project setup (composer + npm + key + migrate)...');
  const created = ensureEnvFile();
  if (created) sse.ok('[panel] Created .env from example.');
  if (ensureDbFile()) sse.ok('[panel] Created database/database.sqlite.');

  if (!existsSync(join(__dir, 'vendor'))) {
    sse.sys('[panel] composer install...');
    const c = await spawnStream(sse, 'composer', ['install', '--no-interaction', '--prefer-dist'], { cwd: __dir });
    if (c !== 0) { sse.err('[panel] composer install failed.'); sse.done(c); return; }
  } else sse.ok('[panel] vendor/ already exists — skip.');

  const env = readEnv();
  if (!env.APP_KEY) {
    sse.sys('[panel] php artisan key:generate...');
    await spawnStream(sse, 'php', ['artisan', 'key:generate'], { cwd: __dir });
  } else sse.ok('[panel] APP_KEY already set — skip.');

  sse.sys('[panel] php artisan migrate --force...');
  await spawnStream(sse, 'php', ['artisan', 'migrate', '--force'], { cwd: __dir });

  if (!existsSync(join(__dir, 'node_modules'))) {
    sse.sys('[panel] npm install...');
    await spawnStream(sse, 'npm', ['install', '--silent'], { cwd: __dir });
  } else sse.ok('[panel] node_modules/ already exists — skip.');

  // PTY_SECRET
  const envNow = readEnv();
  if (!envNow.PTY_SECRET) {
    const s = createHash('sha256').update(Math.random().toString() + Date.now()).digest('hex');
    setEnvKey('PTY_SECRET', s);
    setEnvKey('PTY_URL', 'ws://127.0.0.1:4000');
    sse.ok('[panel] Generated PTY_SECRET → .env');
  } else sse.ok('[panel] PTY_SECRET already set — skip.');

  sse.done(0);
}

async function runKeyGenerate(res) {
  const sse = sseStream(res);
  ensureEnvFile();
  sse.sys('[panel] php artisan key:generate...');
  const code = await spawnStream(sse, 'php', ['artisan', 'key:generate'], { cwd: __dir });
  sse.done(code);
}

async function runMigrate(res) {
  const sse = sseStream(res);
  ensureDbFile();
  sse.sys('[panel] php artisan migrate --force...');
  const code = await spawnStream(sse, 'php', ['artisan', 'migrate', '--force'], { cwd: __dir });
  sse.done(code);
}

async function generatePtySecret(res) {
  ensureEnvFile();
  const s = createHash('sha256').update(Math.random().toString() + Date.now()).digest('hex');
  setEnvKey('PTY_SECRET', s);
  if (!readEnv().PTY_URL) setEnvKey('PTY_URL', 'ws://127.0.0.1:4000');
  jsonRes(res, { ok: true, secret: s, message: 'PTY_SECRET written to .env' });
}

async function pullModel(model, res) {
  const sse = sseStream(res);
  sse.sys(`[panel] ollama pull ${model}...`);
  const code = await spawnStream(sse, 'ollama', ['pull', model], { cwd: __dir });
  sse.done(code);
}

// ─── FULL AUTO-SETUP ──────────────────────────────────────────────────────────
async function autoSetup(res) {
  const sse = sseStream(res);
  sse.sys('━━━ AUTO-SETUP: modular-course-creator ━━━');
  sse.info('[panel] Checking and installing everything needed to run the project.');
  sse.info('[panel] Already-installed steps are skipped automatically.');

  const step = async (label, fn) => {
    sse.sys(`\n── ${label}`);
    return fn();
  };

  // ── 1. PHP ────────────────────────────────────────────────────────────────
  let phpOk = (await checkPhp()).status === 'ok';
  if (!phpOk) {
    phpOk = await step('PHP 8.2+', async () => {
      if (IS_WIN) {
        const c = await spawnStream(sse, 'winget',
          ['install', '-e', '--id', 'XAMPP.XAMPP',
           '--accept-package-agreements', '--accept-source-agreements'], {});
        if (c !== 0) { sse.warn('[panel] winget failed. Install PHP manually from https://windows.php.net/download/'); return false; }
        return (await checkPhp()).status === 'ok';
      }
      if (IS_MAC) {
        await spawnStream(sse, 'brew', ['install', 'php'], {});
        return (await checkPhp()).status === 'ok';
      }
      await spawnStream(sse, 'apt-get', ['-qq', 'install', '-y', 'software-properties-common'], {});
      await spawnStream(sse, 'add-apt-repository', ['-y', 'ppa:ondrej/php'], {});
      await spawnStream(sse, 'apt-get', ['update', '-qq'], {});
      await spawnStream(sse, 'apt-get', ['-qq', 'install', '-y',
        'php8.2', 'php8.2-cli', 'php8.2-mbstring', 'php8.2-xml',
        'php8.2-sqlite3', 'php8.2-curl', 'php8.2-zip'], {});
      return (await checkPhp()).status === 'ok';
    });
  } else sse.ok('── PHP: installed — skip');

  // ── 2. Composer ───────────────────────────────────────────────────────────
  let composerOk = (await checkComposer()).status === 'ok';
  if (!composerOk) {
    composerOk = await step('Composer 2', async () => {
      if (IS_WIN) { sse.warn('[panel] Download from https://getcomposer.org/download/'); return false; }
      await sh('curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php');
      await sh('php /tmp/composer-setup.php --install-dir=/tmp --filename=composer');
      const mv = await sh('mv /tmp/composer /usr/local/bin/composer && chmod +x /usr/local/bin/composer');
      if (!mv.ok) await sh('mkdir -p ~/.local/bin && mv /tmp/composer ~/.local/bin/composer && chmod +x ~/.local/bin/composer');
      return (await checkComposer()).status === 'ok';
    });
  } else sse.ok('── Composer: installed — skip');

  // ── 3. Node ───────────────────────────────────────────────────────────────
  let nodeOk = (await checkNode()).status === 'ok';
  if (!nodeOk) {
    nodeOk = await step('Node.js LTS', async () => {
      if (IS_WIN) {
        const c = await spawnStream(sse, 'winget',
          ['install', '-e', '--id', 'OpenJS.NodeJS.LTS',
           '--accept-package-agreements', '--accept-source-agreements'], {});
        return c === 0;
      }
      if (IS_MAC) { await spawnStream(sse, 'brew', ['install', 'node'], {}); return (await checkNode()).status === 'ok'; }
      await sh('curl -fsSL https://deb.nodesource.com/setup_lts.x | bash -');
      await spawnStream(sse, 'apt-get', ['-qq', 'install', '-y', 'nodejs'], {});
      return (await checkNode()).status === 'ok';
    });
  } else sse.ok('── Node.js: installed — skip');

  // ── 4. Podman ─────────────────────────────────────────────────────────────
  let podmanOk = (await checkPodman()).status === 'ok';
  if (!podmanOk) {
    podmanOk = await step('Podman', async () => {
      if (IS_WIN) {
        const c = await spawnStream(sse, 'winget',
          ['install', '-e', '--id', 'RedHat.Podman',
           '--accept-package-agreements', '--accept-source-agreements'], {});
        return c === 0;
      }
      if (IS_MAC) {
        await spawnStream(sse, 'brew', ['install', 'podman'], {});
        await spawnStream(sse, 'podman', ['machine', 'init'], {});
        await spawnStream(sse, 'podman', ['machine', 'start'], {});
        return (await checkPodman()).status === 'ok';
      }
      await spawnStream(sse, 'apt-get', ['-qq', 'install', '-y', 'podman'], {});
      return (await checkPodman()).status === 'ok';
    });
  } else sse.ok('── Podman: installed — skip');

  // ── 5. Project files ──────────────────────────────────────────────────────
  await step('Project files (.env, vendor, key, DB, PTY_SECRET)', async () => {
    ensureEnvFile();
    ensureDbFile();

    if (phpOk && composerOk) {
      if (!existsSync(join(__dir, 'vendor'))) {
        sse.sys('[panel] composer install...');
        await spawnStream(sse, 'composer', ['install', '--no-interaction', '--prefer-dist'], { cwd: __dir });
      } else sse.ok('[panel] vendor/ exists — skip');

      const env = readEnv();
      if (!env.APP_KEY) {
        sse.sys('[panel] php artisan key:generate...');
        await spawnStream(sse, 'php', ['artisan', 'key:generate'], { cwd: __dir });
      } else sse.ok('[panel] APP_KEY exists — skip');

      sse.sys('[panel] php artisan migrate --force...');
      await spawnStream(sse, 'php', ['artisan', 'migrate', '--force'], { cwd: __dir });
    } else sse.warn('[panel] PHP/Composer not ready — skipping composer steps');

    if (nodeOk) {
      if (!existsSync(join(__dir, 'node_modules'))) {
        sse.sys('[panel] npm install...');
        await spawnStream(sse, 'npm', ['install', '--silent'], { cwd: __dir });
      } else sse.ok('[panel] node_modules/ exists — skip');
    } else sse.warn('[panel] Node not ready — skipping npm install');

    const envNow = readEnv();
    if (!envNow.PTY_SECRET) {
      const s = createHash('sha256').update(Math.random().toString() + Date.now()).digest('hex');
      setEnvKey('PTY_SECRET', s);
      setEnvKey('PTY_URL', 'ws://127.0.0.1:4000');
      sse.ok('[panel] PTY_SECRET generated → .env');
    } else sse.ok('[panel] PTY_SECRET exists — skip');
  });

  // ── 6. code-runner image ──────────────────────────────────────────────────
  if ((await checkCodeImage()).status !== 'ok') {
    if (podmanOk) {
      await step('code-runner container image (5-15 min)', async () => {
        const bin = (await sh('podman --version 2>&1')).ok ? 'podman' : 'docker';
        const c = await spawnStream(sse, bin,
          ['build', '-t', 'localhost/code-runner:latest', '-f', 'Containerfile', '.'],
          { cwd: join(__dir, 'pty-server') });
        sse.line(c === 0 ? '[panel] Image built.' : '[panel] Image build failed.', c === 0 ? 'ok' : 'err');
      });
    } else sse.warn('── code-runner image: skipped (Podman not ready)');
  } else sse.ok('── code-runner image: exists — skip');

  // ── 7. Python + MinerU (smart, version-aware) ─────────────────────────────
  if ((await checkPythonMineru()).status !== 'ok') {
    await step('Python 3.10–3.13 + MinerU (optional, for AI PDF pipeline)', async () => {
      const code = await setupMineruVenv(sse);
      return code === 0;
    });
  } else sse.ok('── Python + MinerU: installed — skip');

  // ── 8. npm build ─────────────────────────────────────────────────────────
  if (nodeOk && existsSync(join(__dir, 'node_modules'))) {
    await step('Frontend assets (npm run build)', async () => {
      const c = await spawnStream(sse, 'npm', ['run', 'build'], { cwd: __dir });
      sse.line(c === 0 ? '[panel] Assets built.' : '[panel] Build failed — run: npm run dev in a terminal during development.', c === 0 ? 'ok' : 'warn');
    });
  }

  sse.sys('\n━━━ AUTO-SETUP COMPLETE ━━━');
  sse.done(0);
}

// ─── PORT CHECK + SERVICE START/STOP ─────────────────────────────────────────
async function isPortOpen(port) {
  const cmd = IS_WIN
    ? `netstat -ano | findstr ":${port} " | findstr "LISTENING"`
    : `ss -tlnp 2>/dev/null | grep :${port} || lsof -i :${port} -sTCP:LISTEN 2>/dev/null`;
  const r = await sh(cmd);
  return r.ok && r.stdout.length > 0;
}

async function startAll(res) {
  if (IS_WIN) {
    spawn('cmd', ['/c', join(__dir, 'scripts', 'serve.bat')], { detached: true, stdio: 'ignore' }).unref();
    jsonRes(res, { ok: true, message: 'serve.bat launched — terminal windows will open for each service.' });
    return;
  }
  const env = readEnv();
  const procs = [
    { name: 'laravel', cmd: 'php', args: ['artisan', 'serve'], env: {} },
    { name: 'queue',   cmd: 'php', args: ['artisan', 'queue:work', '--tries=1'], env: {} },
    { name: 'vite',    cmd: 'npm', args: ['run', 'dev'], env: {} },
    { name: 'pty',     cmd: 'node', args: ['server.js'],
      cwd: join(__dir, 'pty-server'),
      env: { PTY_SECRET: env.PTY_SECRET || '' } },
  ];
  const ollamaOk = (await sh('which ollama 2>&1')).ok;
  if (ollamaOk) procs.push({ name: 'ollama', cmd: 'ollama', args: ['serve'], env: {} });

  const pids = {};
  for (const p of procs) {
    try {
      const proc = spawn(p.cmd, p.args, {
        cwd: p.cwd || __dir, detached: true, stdio: 'ignore',
        env: { ...process.env, ...p.env },
      });
      proc.unref();
      pids[p.name] = proc.pid;
    } catch (e) {
      pids[p.name] = `error: ${e.message}`;
    }
  }
  writeFileSync(join(__dir, '.devpanel-pids.json'), JSON.stringify(pids));
  jsonRes(res, { ok: true, message: `Started ${Object.keys(pids).length} services.`, pids });
}

async function stopAll(res) {
  if (IS_WIN) {
    spawn('cmd', ['/c', join(__dir, 'scripts', 'unserve.bat')], { detached: true, stdio: 'ignore' }).unref();
    jsonRes(res, { ok: true, message: 'unserve.bat launched.' }); return;
  }
  const pidFile = join(__dir, '.devpanel-pids.json');
  const killed = [];
  if (existsSync(pidFile)) {
    try {
      const pids = JSON.parse(readFileSync(pidFile, 'utf8'));
      for (const [name, pid] of Object.entries(pids)) {
        if (typeof pid === 'number') {
          try { process.kill(pid, 'SIGTERM'); killed.push(`${name}(${pid})`); } catch {}
        }
      }
    } catch {}
  }
  await sh('pkill -f "artisan serve" 2>/dev/null; pkill -f "artisan queue" 2>/dev/null; pkill -f "pty-server/server.js" 2>/dev/null; pkill -f "vite" 2>/dev/null; true');
  jsonRes(res, { ok: true, message: killed.length ? `Stopped: ${killed.join(', ')}` : 'SIGTERM sent to known processes.' });
}

// ─── HTTP SERVER ──────────────────────────────────────────────────────────────
const server = createServer(async (req, res) => {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET,POST,OPTIONS');
  if (req.method === 'OPTIONS') { res.writeHead(204); res.end(); return; }

  const url  = new URL(req.url, `http://localhost:${PORT}`);
  const path = url.pathname;

  if (path === '/' || path === '/index.html') {
    const hp = join(__dir, 'devpanel.html');
    if (!existsSync(hp)) { res.writeHead(404); res.end('devpanel.html not found next to devpanel-server.mjs'); return; }
    res.writeHead(200, { 'Content-Type': 'text/html' });
    res.end(readFileSync(hp)); return;
  }

  if (path === '/api/os') { jsonRes(res, { os: OS, platform: platform() }); return; }

  if (path === '/api/check/all') {
    const [php, comp, node, podman, img, laravel, ollama, python] = await Promise.all([
      checkPhp(), checkComposer(), checkNode(), checkPodman(),
      checkCodeImage(), checkLaravelDeps(), checkOllama(), checkPythonMineru(),
    ]);
    jsonRes(res, { php, composer: comp, node, podman, 'code-image': img, 'laravel-deps': laravel, ollama, 'python-mineru': python });
    return;
  }

  if (path.startsWith('/api/check/')) {
    const id = path.slice('/api/check/'.length);
    const map = {
      php: checkPhp, composer: checkComposer, node: checkNode, podman: checkPodman,
      'code-image': checkCodeImage, 'laravel-deps': checkLaravelDeps,
      ollama: checkOllama, 'python-mineru': checkPythonMineru,
    };
    if (map[id]) { jsonRes(res, await map[id]()); }
    else { jsonRes(res, { status: 'err', detail: 'unknown component' }, 404); }
    return;
  }

  if (path === '/api/services') {
    const [a, b, c, d, q] = await Promise.all([
      isPortOpen(8000), isPortOpen(5173), isPortOpen(4000), isPortOpen(11434),
      sh(IS_WIN ? 'tasklist | findstr php.exe' : 'pgrep -f "artisan queue" 2>/dev/null'),
    ]);
    jsonRes(res, { laravel: a, vite: b, pty: c, ollama: d, queue: q.ok && q.stdout.length > 0 });
    return;
  }

  if (path === '/api/env') {
    const env = readEnv();
    jsonRes(res, {
      APP_KEY:          !!(env.APP_KEY && env.APP_KEY.length > 10),
      PTY_SECRET:       !!(env.PTY_SECRET && env.PTY_SECRET.length > 10),
      PTY_URL:          env.PTY_URL || '',
      APP_ENV:          env.APP_ENV || '',
      DB_CONNECTION:    env.DB_CONNECTION || '',
      QUEUE_CONNECTION: env.QUEUE_CONNECTION || '',
    });
    return;
  }

  if (path === '/api/action/podman-machine-start') { await podmanMachineStart(res); return; }
  if (path === '/api/action/podman-machine-init')  { await podmanMachineInit(res);  return; }
  if (path === '/api/action/podman-fix')           { await fixPodman(res);          return; }
  if (path === '/api/action/ollama-start')         { await ollamaStart(res);        return; }

  // Install actions
  if (path === '/api/install/php')         { await installPhp(res);           return; }
  if (path === '/api/install/composer')    { await installComposer(res);      return; }
  if (path === '/api/install/node')        { await installNode(res);           return; }
  if (path === '/api/install/podman')      { await installPodman(res);         return; }
  if (path === '/api/install/ollama')      { await installOllama(res);         return; }
  if (path === '/api/install/python')      { await installPythonMineru(res);   return; }
  if (path === '/api/install/auto-setup')  { await autoSetup(res);             return; }

  // Project actions
  if (path === '/api/action/build-image')          { await buildCodeImage(res);   return; }
  if (path === '/api/action/project-setup')        { await runProjectSetup(res);  return; }
  if (path === '/api/action/composer-setup')       { await runProjectSetup(res);  return; }
  if (path === '/api/action/migrate')              { await runMigrate(res);       return; }
  if (path === '/api/action/key-generate')         { await runKeyGenerate(res);   return; }
  if (path === '/api/action/setup-mineru')         { await installPythonMineru(res); return; }
  if (path === '/api/action/generate-pty-secret')  { await generatePtySecret(res); return; }

  if (path.startsWith('/api/action/pull-model/')) {
    await pullModel(decodeURIComponent(path.replace('/api/action/pull-model/', '')), res);
    return;
  }

  if (path === '/api/action/start-all') { await startAll(res); return; }
  if (path === '/api/action/stop-all')  { await stopAll(res);  return; }

  res.writeHead(404); res.end('Not found');
});

server.listen(PORT, '127.0.0.1', () => {
  const url = `http://localhost:${PORT}`;
  console.log(`\n  modular-course-creator dev panel`);
  console.log(`  → ${url}`);
  console.log(`  OS: ${OS}  |  Project root: ${__dir}\n`);
  exec(IS_WIN ? `start ${url}` : IS_MAC ? `open ${url}` : `xdg-open ${url} 2>/dev/null || true`);
});

process.on('SIGINT', () => { console.log('\nDev panel stopped.'); process.exit(0); });
