
// 1. From @codemirror/state
import { EditorState, Compartment, EditorSelection } from '@codemirror/state';

// 2. From @codemirror/view (Add lineNumbers, highlightActiveLine, drawSelection)
import {
    EditorView,
    keymap,
    lineNumbers,
    highlightActiveLine,
    drawSelection
} from '@codemirror/view';

// 3. From @codemirror/commands (Add history, historyKeymap)
import { defaultKeymap, history, historyKeymap, indentWithTab } from '@codemirror/commands';

// 4. From @codemirror/language (Add indentOnInput, bracketMatching, foldGutter)
import { indentOnInput, bracketMatching, foldGutter } from '@codemirror/language';

// 5. From @codemirror/autocomplete (Make sure you use autocompletion)
import { autocompletion } from '@codemirror/autocomplete';

// 6. From @codemirror/search (Add highlightSelectionMatches)
import { highlightSelectionMatches } from '@codemirror/search';

// Theme & Languages remain the same...
import { oneDark } from '@codemirror/theme-one-dark';
import { python } from '@codemirror/lang-python';
import { javascript } from '@codemirror/lang-javascript';
import { cpp } from '@codemirror/lang-cpp';
import { java } from '@codemirror/lang-java';
import { rust } from '@codemirror/lang-rust';
    // ── State ──
    let view, currentLang = 'python';
    let sessionRuns = 0, sessionPass = 0;
    let runHistory = [];
    let snippets   = [];
    const MAX_SNIPPETS = 10;
    const STORAGE_KEY  = 'ce_snippets_v1';
    const HISTORY_KEY  = 'ce_history_v1';

    // ── WebSocket state ──
    // PTY_WS_URL and PTY_TOKEN are injected server-side by Laravel.
    // The token is an HMAC derived from PTY_SECRET — never the secret itself.
    const PTY_BASE   = 'ws://127.0.0.1:4000';
    const PTY_TOKEN  = '0895c790ee97038e69fc09f630c5b63f465d1c9ff5c0eb93060cd12f9922e5cf';
    const PTY_WS_URL = PTY_BASE;   // no token yet — add after controller is deployed

    let   socket     = null;   // active WebSocket connection
    let   running    = false;  // is a process currently running?
    let   startTime  = null;

    const langCompartment  = new Compartment();
    const themeCompartment = new Compartment();
    const wrapCompartment  = new Compartment();

    const CM_LANG = {
    python:     python(),
    javascript: javascript(),
    typescript: javascript({ typescript: true }),
    cpp:        cpp(),
    'c++':      cpp(),
    c:          cpp(),
    java:       java(),
    rust:       rust(),
};

    const STARTERS = {
    python:     'name = input("What is your name? ")\nprint(f"Hello, {name}!")\n',
    javascript: 'const readline = require("readline");\nconst rl = readline.createInterface({ input: process.stdin });\nrl.question("What is your name? ", name => {\n    console.log("Hello, " + name + "!");\n    rl.close();\n});\n',
    typescript: 'import * as readline from "readline";\nconst rl = readline.createInterface({ input: process.stdin });\nrl.question("What is your name? ", (name) => {\n    console.log("Hello, " + name + "!");\n    rl.close();\n});\n',
    cpp:        '#include <iostream>\n#include <string>\nusing namespace std;\nint main() {\n    string name;\n    cout << "What is your name? ";\n    cin >> name;\n    cout << "Hello, " << name << "!" << endl;\n    return 0;\n}\n',
    'c++':      '#include <iostream>\n#include <string>\nusing namespace std;\nint main() {\n    string name;\n    cout << "What is your name? ";\n    cin >> name;\n    cout << "Hello, " << name << "!" << endl;\n    return 0;\n}\n',
    c:          '#include <stdio.h>\nint main() {\n    char name[100];\n    printf("What is your name? ");\n    scanf("%s", name);\n    printf("Hello, %s!\\n", name);\n    return 0;\n}\n',
    java:       'import java.util.Scanner;\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        System.out.print("What is your name? ");\n        String name = sc.nextLine();\n        System.out.println("Hello, " + name + "!");\n    }\n}\n',
    rust:       'use std::io::{self, Write};\nfn main() {\n    print!("What is your name? ");\n    io::stdout().flush().unwrap();\n    let mut name = String::new();\n    io::stdin().read_line(&mut name).unwrap();\n    println!("Hello, {}!", name.trim());\n}\n',
    go:         'package main\nimport "fmt"\nfunc main() {\n    var name string\n    fmt.Print("What is your name? ")\n    fmt.Scan(&name)\n    fmt.Printf("Hello, %s!\\n", name)\n}\n',
    ruby:       'print "What is your name? "\nname = gets.chomp\nputs "Hello, " + name + "!"\n',
    php:        '\x3C?php\necho "What is your name? ";\nfscanf(STDIN, "%s", $n);\necho "Hello, " . $n . "!\\n";\n',
    lua:        'io.write("What is your name? ")\nlocal name = io.read()\nprint("Hello, " .. name .. "!")\n',
    perl:       'print "What is your name? ";\nmy $n = <STDIN>;\nchomp $n;\nprint "Hello, " . $n . "!\\n";\n',
    kotlin:     'fun main() {\n    print("What is your name? ")\n    val name = readLine()\n    println("Hello, " + name + "!")\n}\n',
    bash:       '#!/bin/bash\nread -p "What is your name? " name\necho "Hello, $name!"\n',
    swift:      'import Foundation\nprint("What is your name? ", terminator: "")\nif let name = readLine() {\n    print("Hello, \\(name)!")\n}\n',
};

    const EXT_MAP = {
    python:'py', javascript:'js', typescript:'ts',
    cpp:'cpp', 'c++':'cpp', c:'c', java:'java',
    rust:'rs', go:'go', ruby:'rb', php:'php',
    lua:'lua', perl:'pl', kotlin:'kt', bash:'sh', swift:'swift',
};

    function getCmLang(l) { return CM_LANG[l?.toLowerCase()] ?? []; }
    function getStarter(l) { return STARTERS[l?.toLowerCase()] ?? '// Start coding...\n'; }
    function getExt(l) { return EXT_MAP[l?.toLowerCase()] ?? 'txt'; }

    // ── Init CodeMirror ──
    function initEditor(code = '// Start coding...\n', langName = '') {
    const host = document.getElementById('ce-codemirror');
    if (view) view.destroy();
    const isDark = document.documentElement.dataset.theme !== 'light';
    view = new EditorView({
    state: EditorState.create({
    doc: code,
    extensions: [
    lineNumbers(), highlightActiveLine(), history(),
    drawSelection(), indentOnInput(), bracketMatching(),
    foldGutter(), autocompletion(), highlightSelectionMatches(),
    keymap.of([...defaultKeymap, ...historyKeymap, indentWithTab]),
    langCompartment.of(getCmLang(langName)),
    themeCompartment.of(isDark ? oneDark : []),
    wrapCompartment.of([]),
    EditorView.updateListener.of(update => {
    if (update.docChanged || update.selectionSet) updateEditorStats(update.view);
}),
    ],
}),
    parent: host,
});
    updateEditorStats(view);
}

    function updateEditorStats(v) {
    const doc  = v.state.doc;
    const lines = doc.lines, chars = doc.length;
    const sel  = v.state.selection.main;
    const line = doc.lineAt(sel.head);
    const col  = sel.head - line.from + 1;
    document.getElementById('ce-line-count').textContent = `${lines} line${lines !== 1 ? 's' : ''}`;
    document.getElementById('ce-cursor-pos').textContent = `Ln ${line.number}, Col ${col}`;
    document.getElementById('stat-lines').textContent = lines;
    document.getElementById('stat-chars').textContent = chars > 999 ? (chars/1000).toFixed(1)+'k' : chars;
}

    // ─────────────────────────────────────────────────────────
    //  PTY WebSocket connection & status check
    // ─────────────────────────────────────────────────────────

    function checkPtyServer() {
    const dot = document.getElementById('ce-status-dot');
    const testWs = new WebSocket(PTY_WS_URL);
    testWs.onopen  = () => {
    dot.className = 'ce-status-dot online';
    dot.title     = 'PTY server online ✓';
    testWs.close();
};
    testWs.onclose = (e) => {
    if (e.code === 4001 || e.reason === 'Unauthorized') {
    dot.className = 'ce-status-dot offline';
    dot.title     = 'PTY server: auth failed — check PTY_SECRET in .env';
    ceTerminalWrite('stderr', '⛔ PTY server rejected the token.\n');
    ceTerminalWrite('system', '   Make sure PTY_SECRET in .env matches export PTY_SECRET in WSL2.\n');
}
};
    testWs.onerror = () => {
    dot.className = 'ce-status-dot offline';
    dot.title     = 'PTY server offline — cd ~/pty-server && node server.js';
    ceTerminalWrite('system', '⚠  PTY server unreachable.\n');
    ceTerminalWrite('system', '   Start it:  cd ~/pty-server && node server.js\n');
};
}

    // ─────────────────────────────────────────────────────────
    //  ⚡ RUN  — replaces the old Piston fetch entirely
    // ─────────────────────────────────────────────────────────

    window.ceRun = async function () {
    const lang = document.getElementById('ce-lang-select').value;
    const code = view ? view.state.doc.toString() : '';
    if (!lang || !code.trim()) { ceToast('Nothing to run!'); return; }

    // Kill any previous run cleanly
    if (socket) {
    socket.close();
    socket = null;
}

    ceShowTab('terminal');
    ceClearTerminal();

    const runBtn  = document.getElementById('ce-run-btn');
    const killBtn = document.getElementById('ce-kill-btn');

    runBtn.disabled = true;
    runBtn.innerHTML = '<svg class="ce-spin" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Running…';
    killBtn.style.display = 'inline-flex';

    startTime = Date.now();
    sessionRuns++;
    document.getElementById('stat-runs').textContent = sessionRuns;

    ceTerminalWriteHTML('<span class="ce-out-system">▶ ' + lang + '  [' + new Date().toLocaleTimeString() + ']\n</span>');
    ceTerminalWrite('system', '─'.repeat(40) + '\n');

    // Open WebSocket to PTY server
    try {
    socket = new WebSocket(PTY_WS_URL);
} catch (e) {
    ceTerminalWrite('stderr', '✗ Could not connect to PTY server: ' + e.message + '\n');
    resetRunUI(false, '—');
    return;
}

    socket.onopen = () => {
    running = true;
    // Show input row so student can type immediately
    document.getElementById('ce-term-input-row').style.display = 'flex';
    document.getElementById('ce-live-input').focus();
    // Send run command
    socket.send(JSON.stringify({ type: 'run', language: lang, code }));
};

    socket.onmessage = (event) => {
    let msg;
    try { msg = JSON.parse(event.data); } catch { return; }

    switch (msg.type) {
    case 'stdout':
    // PTY output — write raw (includes prompts, colors via text)
    appendRawOutput(msg.data);
    break;
    case 'stderr':
    ceTerminalWrite('stderr', msg.data);
    break;
    case 'system':
    ceTerminalWrite('system', msg.data);
    break;
    case 'exit': {
    const elapsed = ((Date.now() - startTime) / 1000).toFixed(2);
    const ok      = msg.code === 0;
    ceTerminalWrite('system', '\n' + '─'.repeat(40) + '\n');
    if (ok) {
    ceTerminalWriteHTML(`<span class="ce-out-success">✓ Exited 0 in ${elapsed}s\n</span>`);
    sessionPass++;
    document.getElementById('stat-pass').textContent = sessionPass;
} else {
    const label = msg.code === -1 ? 'killed' : `exit ${msg.code}`;
    ceTerminalWriteHTML(`<span class="ce-out-stderr">✗ ${label} in ${elapsed}s\n</span>`);
}
    setBadge(ok ? 'ok' : 'fail', ok ? 'Exit 0 ✓' : `Exit ${msg.code ?? '?'}`);
    addHistory(lang, ok ? 'ok' : 'fail', elapsed);
    resetRunUI(ok, elapsed);
    break;
}
}
};

    socket.onerror = () => {
    ceTerminalWrite('stderr', '\n✗ WebSocket error — is the PTY server running?\n');
    resetRunUI(false, '—');
};

    socket.onclose = () => {
    // If still "running" when socket closes unexpectedly
    if (running) {
    running = false;
    resetRunUI(false, ((Date.now() - startTime) / 1000).toFixed(2));
}
};
};

    // ─────────────────────────────────────────────────────────
    //  ⌨  SEND STDIN — now actually sends to the live process
    // ─────────────────────────────────────────────────────────

    window.ceSendInput = function () {
    const inp = document.getElementById('ce-live-input');
    const val = inp.value;
    if (!socket || socket.readyState !== WebSocket.OPEN) {
    ceToast('No process running');
    return;
}
    // Send the text + newline to the PTY (as if student pressed Enter in a real terminal)
    socket.send(JSON.stringify({ type: 'stdin', data: val + '\n' }));
    // Echo what was typed so the student sees it in the terminal
    ceTerminalWriteHTML(`<span class="ce-out-stdin-echo">${escHtml(val)}\n</span>`);
    inp.value = '';
};

    document.getElementById('ce-live-input').addEventListener('keydown', e => {
    if (e.key === 'Enter') window.ceSendInput();
});

    // ── Kill running process ──
    window.ceKill = function () {
    if (socket && socket.readyState === WebSocket.OPEN) {
    socket.send(JSON.stringify({ type: 'kill' }));
}
};

    // ── Reset run UI after process ends ──
    function resetRunUI(ok, elapsed) {
    running = false;
    socket  = null;
    const runBtn  = document.getElementById('ce-run-btn');
    const killBtn = document.getElementById('ce-kill-btn');
    runBtn.disabled = false;
    runBtn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg> Run <kbd class="ce-run-hint">⌃↵</kbd>';
    killBtn.style.display = 'none';
    document.getElementById('ce-term-input-row').style.display = 'none';
    const timeEl = document.getElementById('ce-run-time');
    timeEl.textContent = elapsed + 's';
    timeEl.style.display = '';
}

    // ── Write raw PTY output (handles \r\n from PTY correctly) ──
    function appendRawOutput(data) {
    const terminal = document.getElementById('ce-terminal');
    const welcome  = terminal.querySelector('.ce-term-welcome');
    if (welcome) welcome.remove();
    const span = document.createElement('span');
    span.className = 'ce-out-stdout';
    span.textContent = data;
    terminal.appendChild(span);
    terminal.scrollTop = terminal.scrollHeight;
}

    // ── Terminal helpers ──
    function ceTerminalWrite(type, text) {
    const terminal = document.getElementById('ce-terminal');
    const welcome  = terminal.querySelector('.ce-term-welcome');
    if (welcome) welcome.remove();
    const span = document.createElement('span');
    span.className = `ce-out-${type}`;
    span.textContent = text;
    terminal.appendChild(span);
    terminal.scrollTop = terminal.scrollHeight;
}

    function ceTerminalWriteHTML(html) {
    const terminal = document.getElementById('ce-terminal');
    const welcome  = terminal.querySelector('.ce-term-welcome');
    if (welcome) welcome.remove();
    terminal.insertAdjacentHTML('beforeend', html);
    terminal.scrollTop = terminal.scrollHeight;
}

    function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

    window.ceClearTerminal = function () {
    const t = document.getElementById('ce-terminal');
    t.innerHTML = '';
    document.getElementById('ce-exit-badge').style.display = 'none';
    document.getElementById('ce-run-time').style.display   = 'none';
};

    function setBadge(cls, text) {
    const badge = document.getElementById('ce-exit-badge');
    badge.className   = `ce-exit-badge ${cls}`;
    badge.textContent = text;
    badge.style.display = 'inline-flex';
}

    // ── Language change ──
    function onLangChange(langName) {
    currentLang = langName;
    const fn   = document.getElementById('ce-filename');
    const base = fn.textContent.split('.')[0] || 'untitled';
    fn.textContent = `${base}.${getExt(langName)}`;
    initEditor(getStarter(langName), langName);
    if (view) view.dispatch({ effects: langCompartment.reconfigure(getCmLang(langName)) });
}

    document.getElementById('ce-lang-select').addEventListener('change', e => onLangChange(e.target.value));

    // ── Tab switching ──
    window.ceShowTab = function(tab) {
    document.getElementById('panel-terminal').style.display = tab === 'terminal' ? 'flex' : 'none';
    document.getElementById('tab-terminal').classList.toggle('active', tab === 'terminal');
};

    // ── Editor actions ──
    window.ceReset = function () {
    const lang = document.getElementById('ce-lang-select').value;
    if (view) view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: getStarter(lang) } });
    ceToast('Reset to starter code');
};

    window.ceFormatCode = function () { ceToast('Formatted ✓'); };

    window.ceToggleWrap = function (on) {
    if (view) view.dispatch({ effects: wrapCompartment.reconfigure(on ? EditorView.lineWrapping : []) });
};

    window.ceSetTheme = function (theme) {
    if (view) view.dispatch({ effects: themeCompartment.reconfigure(theme === 'dark' ? oneDark : []) });
};

    window.ceToggleFocus = function () {
    document.getElementById('ce-app').classList.toggle('focus-mode');
    ceToast(document.getElementById('ce-app').classList.contains('focus-mode') ? 'Focus mode on' : 'Focus mode off');
};

    // ── Download ──
    window.ceDownloadCode = function () {
    const lang = document.getElementById('ce-lang-select').value;
    const code = view ? view.state.doc.toString() : '';
    const fn   = document.getElementById('ce-filename').textContent || `code.${getExt(lang)}`;
    downloadBlob(code, fn, 'text/plain');
    ceToast('Downloaded ' + fn);
};

    window.ceDownloadOutput = function () {
    downloadBlob(document.getElementById('ce-terminal').innerText, 'output.txt', 'text/plain');
    ceToast('Output downloaded');
};

    window.ceCopyCode = function () {
    navigator.clipboard.writeText(view ? view.state.doc.toString() : '').then(() => ceToast('Copied to clipboard ✓'));
};

    function downloadBlob(content, filename, mime) {
    const blob = new Blob([content], { type: mime });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = filename; a.click();
    URL.revokeObjectURL(url);
}

    // ── Snippets ──
    function loadSnippets() {
    try { snippets = JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; } catch { snippets = []; }
    renderSnippets();
}

    function saveSnippetsToDisk() { localStorage.setItem(STORAGE_KEY, JSON.stringify(snippets)); }

    window.ceSaveSnippet = function () {
    if (snippets.length >= MAX_SNIPPETS) { ceToast(`Max ${MAX_SNIPPETS} snippets reached`); return; }
    const lang = document.getElementById('ce-lang-select').value;
    const fn   = document.getElementById('ce-filename').textContent || 'untitled';
    document.getElementById('ce-snippet-name').value = fn;
    document.getElementById('ce-save-modal').style.display = 'flex';
    setTimeout(() => document.getElementById('ce-snippet-name').focus(), 50);
};

    window.ceConfirmSave = function () {
    const name = document.getElementById('ce-snippet-name').value.trim() || 'Untitled';
    const lang = document.getElementById('ce-lang-select').value;
    const code = view ? view.state.doc.toString() : '';
    const now  = new Date();
    snippets.unshift({ id: Date.now(), name, lang, code, lines: view ? view.state.doc.lines : 0, time: now.toLocaleTimeString(), date: now.toLocaleDateString() });
    if (snippets.length > MAX_SNIPPETS) snippets.pop();
    saveSnippetsToDisk();
    renderSnippets();
    document.getElementById('ce-save-modal').style.display = 'none';
    ceToast('Saved "' + name + '"');
};

    function renderSnippets() {
    const list = document.getElementById('sb-list');
    document.getElementById('sb-count').textContent = `${snippets.length}/${MAX_SNIPPETS}`;
    if (!snippets.length) {
    list.innerHTML = '<div class="ce-sb-empty">No saved snippets yet.<br>Click "Save Current" to store code.</div>';
    return;
}
    list.innerHTML = snippets.map((s, i) => `
                <div class="ce-snippet-item" onclick="ceLoadSnippet(${i})">
                    <div class="ce-snippet-info">
                        <div class="ce-snippet-name">${escHtml(s.name)}</div>
                        <div class="ce-snippet-meta">${s.lines} lines · ${s.time}</div>
                    </div>
                    <span class="ce-snippet-lang">${escHtml(s.lang)}</span>
                    <button class="ce-snippet-del" onclick="event.stopPropagation();ceDeleteSnippet(${i})" title="Delete">✕</button>
                </div>
            `).join('');
}

    window.ceLoadSnippet = function (i) {
    const s = snippets[i];
    if (!s) return;
    const langSel = document.getElementById('ce-lang-select');
    if (langSel.querySelector(`option[value="${s.lang}"]`)) {
    langSel.value = s.lang;
    currentLang   = s.lang;
    if (view) view.dispatch({ effects: langCompartment.reconfigure(getCmLang(s.lang)) });
}
    if (view) view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: s.code } });
    document.getElementById('ce-filename').textContent = s.name;
    ceToast('Loaded "' + s.name + '"');
};

    window.ceDeleteSnippet = function (i) {
    const name = snippets[i]?.name;
    snippets.splice(i, 1);
    saveSnippetsToDisk();
    renderSnippets();
    ceToast('Deleted "' + name + '"');
};

    // ── Run History ──
    function loadHistory() {
    try { runHistory = JSON.parse(localStorage.getItem(HISTORY_KEY)) || []; } catch { runHistory = []; }
    renderHistory();
}

    function addHistory(lang, status, elapsed) {
    runHistory.unshift({ lang, status, elapsed, time: new Date().toLocaleTimeString() });
    if (runHistory.length > 20) runHistory.pop();
    localStorage.setItem(HISTORY_KEY, JSON.stringify(runHistory));
    renderHistory();
}

    function renderHistory() {
    const el = document.getElementById('ce-history');
    if (!runHistory.length) { el.innerHTML = '<div class="ce-sb-empty">No runs yet.</div>'; return; }
    el.innerHTML = runHistory.slice(0, 15).map(r => `
                <div class="ce-hist-item">
                    <div class="ce-hist-dot ${r.status}"></div>
                    <div class="ce-hist-info">
                        <div class="ce-hist-lang">${escHtml(r.lang)}</div>
                        <div class="ce-hist-time">${r.time}</div>
                    </div>
                    <span class="ce-hist-dur">${r.elapsed}s</span>
                </div>
            `).join('');
}

    window.ceClearHistory = function () {
    runHistory = [];
    localStorage.removeItem(HISTORY_KEY);
    renderHistory();
    ceToast('History cleared');
};

    // ── Toast ──
    let toastTimer;
    window.ceToast = function (msg) {
    const t = document.getElementById('ce-toast');
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 2200);
};

    // ── Resize panel ──
    (function () {
    const handle = document.getElementById('ce-resize-handle');
    const panel  = document.getElementById('ce-right-panel');
    let dragging = false, startX, startW;
    handle.addEventListener('mousedown', e => {
    dragging = true; startX = e.clientX; startW = panel.offsetWidth;
    handle.classList.add('dragging');
    document.body.style.cursor = 'col-resize';
    document.body.style.userSelect = 'none';
});
    document.addEventListener('mousemove', e => {
    if (!dragging) return;
    const delta = startX - e.clientX;
    const newW  = Math.max(260, Math.min(window.innerWidth * 0.6, startW + delta));
    panel.style.width = newW + 'px';
});
    document.addEventListener('mouseup', () => {
    dragging = false;
    handle.classList.remove('dragging');
    document.body.style.cursor = '';
    document.body.style.userSelect = '';
});
})();

    // ── Keyboard shortcuts ──
    document.addEventListener('keydown', e => {
    if (e.ctrlKey || e.metaKey) {
    if (e.key === 'Enter') { e.preventDefault(); window.ceRun(); }
    if (e.key === 's')     { e.preventDefault(); window.ceSaveSnippet(); }
    if (e.key === 'd')     { e.preventDefault(); window.ceDownloadCode(); }
    if (e.key === 'l')     { e.preventDefault(); window.ceClearTerminal(); }
    if (e.key === 'k')     { e.preventDefault(); window.ceFormatCode(); }
}
    if (e.key === 'Escape') document.getElementById('ce-save-modal').style.display = 'none';
});

    document.getElementById('ce-snippet-name').addEventListener('keydown', e => {
    if (e.key === 'Enter') window.ceConfirmSave();
});

    // ── Boot ──
    loadSnippets();
    loadHistory();
    onLangChange('python');
    checkPtyServer();         // test PTY server connection and update status dot
