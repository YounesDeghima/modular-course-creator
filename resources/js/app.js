// 1. From @codemirror/state
import { EditorState, Compartment } from '@codemirror/state';

// 2. From @codemirror/view
import {
    EditorView,
    keymap,
    lineNumbers,
    highlightActiveLine,
    drawSelection
} from '@codemirror/view';

// 3. From @codemirror/commands
import { defaultKeymap, history, historyKeymap, indentWithTab } from '@codemirror/commands';

// 4. From @codemirror/language
import { indentOnInput, bracketMatching, foldGutter, syntaxHighlighting, defaultHighlightStyle } from '@codemirror/language';

// 5. From @codemirror/autocomplete
import { autocompletion } from '@codemirror/autocomplete';

// 6. From @codemirror/search
import { highlightSelectionMatches } from '@codemirror/search';

// Theme & Languages
import { oneDark } from '@codemirror/theme-one-dark';
import { python } from '@codemirror/lang-python';
import { javascript } from '@codemirror/lang-javascript';
import { cpp } from '@codemirror/lang-cpp';
// import { java } from '@codemirror/lang-java';
// import { rust } from '@codemirror/lang-rust';
// import { go } from '@codemirror/lang-go';
// import { php } from '@codemirror/lang-php';


// ═══════════════════════════════════════════════════════════════
//  FILE SYSTEM
//  Everything is stored in localStorage under FILES_KEY.
//  Each file object:
//  {
//    id:        string  — unique stable ID (Date.now() on creation)
//    name:      string  — display name, e.g. "hello.py"
//    lang:      string  — language key
//    code:      string  — full source text
//    savedAt:   string  — human-readable last-save timestamp
//  }
//
//  activeFileId — ID of the file currently open in the editor,
//                 OR null when the buffer is a fresh unsaved file.
//
//  isDirty — true when editor content differs from what's on disk
//            (or when working on an unsaved new buffer).
// ═══════════════════════════════════════════════════════════════

const FILES_KEY = 'ce_files_v2';

let files        = [];        // array of saved file objects
let activeFileId = null;      // null = unsaved new buffer
let isDirty      = false;     // editor has unsaved changes

// ── WebSocket state ──
const PTY_BASE   = window.__PTY_BASE__  ?? 'ws://127.0.0.1:4000';
const PTY_TOKEN  = window.__PTY_TOKEN__ ?? '';
const PTY_WS_URL = `${PTY_BASE}?token=${PTY_TOKEN}`;

let socket    = null;
let running   = false;
let startTime = null;

// Session counters (in-memory only, reset on page reload)
let sessionRuns = 0, sessionPass = 0;

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
    // java:       java(),
    // rust:       rust(),
    // go:         go(),
    // php:        php(),
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
function getExt(l)     { return EXT_MAP[l?.toLowerCase()] ?? 'txt'; }


// ═══════════════════════════════════════════════════════════════
//  CODEMIRROR
// ═══════════════════════════════════════════════════════════════

let view; // the active EditorView instance

function initEditor(code = '// Start coding...\n', langName = 'python') {
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
                    if (update.docChanged) {
                        markDirty();
                        updateEditorStats(update.view);
                    } else if (update.selectionSet) {
                        updateEditorStats(update.view);
                    }
                }),
            ],
        }),
        parent: host,
    });
    updateEditorStats(view);
    window.ceEditor = view; // keep in sync for pre-fill handler
}

function updateEditorStats(v) {
    const doc   = v.state.doc;
    const lines = doc.lines;
    const chars = doc.length;
    const sel   = v.state.selection.main;
    const line  = doc.lineAt(sel.head);
    const col   = sel.head - line.from + 1;
    document.getElementById('ce-line-count').textContent = `${lines} line${lines !== 1 ? 's' : ''}`;
    document.getElementById('ce-cursor-pos').textContent = `Ln ${line.number}, Col ${col}`;
}


// ═══════════════════════════════════════════════════════════════
//  DIRTY / CLEAN STATE
// ═══════════════════════════════════════════════════════════════

function markDirty() {
    if (!isDirty) {
        isDirty = true;
        document.getElementById('ce-dirty-dot').style.display = '';
    }
}

function markClean() {
    isDirty = false;
    document.getElementById('ce-dirty-dot').style.display = 'none';
}


// ═══════════════════════════════════════════════════════════════
//  FILE STORAGE  (localStorage)
// ═══════════════════════════════════════════════════════════════

function loadFilesFromStorage() {
    try { files = JSON.parse(localStorage.getItem(FILES_KEY)) || []; }
    catch { files = []; }
}

function persistFiles() {
    localStorage.setItem(FILES_KEY, JSON.stringify(files));
}

/** Return the file object for activeFileId, or null if unsaved buffer. */
function getActiveFile() {
    return files.find(f => f.id === activeFileId) ?? null;
}


// ═══════════════════════════════════════════════════════════════
//  OPEN A FILE INTO THE EDITOR
// ═══════════════════════════════════════════════════════════════

function openFile(fileId) {
    const f = files.find(f => f.id === fileId);
    if (!f) return;

    activeFileId = f.id;

    // Set language selector
    const langSel = document.getElementById('ce-lang-select');
    if (langSel.querySelector(`option[value="${f.lang}"]`)) langSel.value = f.lang;

    // Reinitialise editor with saved content
    initEditor(f.code, f.lang);
    markClean();

    // Update topbar filename
    document.getElementById('ce-filename').textContent = f.name;

    renderFileList();
    ceToast(`Opened "${f.name}"`);
}

// Exposed so the sidebar HTML onclick can call it
window.ceOpenFile = openFile;


// ═══════════════════════════════════════════════════════════════
//  NEW FILE  — clears the editor to a fresh unsaved buffer
// ═══════════════════════════════════════════════════════════════

window.ceNewFile = function () {
    const lang = document.getElementById('ce-lang-select').value || 'python';
    activeFileId = null;              // no file on disk yet
    initEditor(getStarter(lang), lang);
    document.getElementById('ce-filename').textContent = `untitled.${getExt(lang)}`;
    markDirty();                      // new buffer always starts dirty
    renderFileList();
    ceToast('New file');
};


// ═══════════════════════════════════════════════════════════════
//  SAVE  (Ctrl+S / "Save" button)
//
//  Logic:
//    • activeFileId === null  →  buffer is unsaved/new → open modal to name it
//    • activeFileId !== null  →  overwrite that file silently, no modal
// ═══════════════════════════════════════════════════════════════

window.ceFileSave = function () {
    if (activeFileId === null) {
        // New buffer — need a name first
        openSaveModal({ mode: 'new' });
    } else {
        // Known file — silently overwrite
        overwriteActiveFile();
    }
};


// ═══════════════════════════════════════════════════════════════
//  SAVE AS  — always opens modal, always creates a brand-new file
//  (even if a file is already active)
// ═══════════════════════════════════════════════════════════════

window.ceFileSaveAs = function () {
    const currentName = document.getElementById('ce-filename').textContent || '';
    openSaveModal({ mode: 'saveas', suggestedName: currentName });
};


// ═══════════════════════════════════════════════════════════════
//  OVERWRITE  — writes editor content into the currently active file
// ═══════════════════════════════════════════════════════════════

function overwriteActiveFile() {
    const f = getActiveFile();
    if (!f) return;

    f.code    = view ? view.state.doc.toString() : '';
    f.lang    = document.getElementById('ce-lang-select').value;
    f.savedAt = new Date().toLocaleTimeString();

    persistFiles();
    markClean();
    renderFileList();
    ceToast(`Saved "${f.name}"`);
}


// ═══════════════════════════════════════════════════════════════
//  SAVE MODAL
//  mode: 'new'    — naming a brand-new buffer for the first time
//  mode: 'saveas' — cloning current buffer into a new named file
// ═══════════════════════════════════════════════════════════════

let _saveModalMode = 'new';

function openSaveModal({ mode, suggestedName = '' }) {
    _saveModalMode = mode;

    const lang        = document.getElementById('ce-lang-select').value;
    const defaultName = suggestedName || `untitled.${getExt(lang)}`;

    const titleEl   = document.getElementById('ce-modal-title');
    const nameInput = document.getElementById('ce-file-name-input');
    const hintEl    = document.getElementById('ce-modal-overwrite-hint');

    titleEl.textContent   = mode === 'saveas' ? 'Save As New File' : 'Save File';
    nameInput.value       = defaultName;
    hintEl.style.display  = 'none';

    // Live validation — warn if a file with that name already exists
    nameInput.oninput = () => {
        const name = nameInput.value.trim();
        const clash = files.some(f => f.name === name && f.id !== activeFileId);
        hintEl.style.display = clash ? 'flex' : 'none';
    };

    document.getElementById('ce-save-modal').style.display = 'flex';
    setTimeout(() => { nameInput.focus(); nameInput.select(); }, 40);
}

window.ceConfirmFileSave = function () {
    const nameInput = document.getElementById('ce-file-name-input');
    const name = nameInput.value.trim();
    if (!name) { nameInput.focus(); return; }

    const lang = document.getElementById('ce-lang-select').value;
    const code = view ? view.state.doc.toString() : '';
    const now  = new Date().toLocaleTimeString();

    if (_saveModalMode === 'new') {
        // Check if a file with that name already exists — if so, overwrite it
        const existing = files.find(f => f.name === name);
        if (existing) {
            existing.code    = code;
            existing.lang    = lang;
            existing.savedAt = now;
            activeFileId     = existing.id;
        } else {
            // Brand-new file record
            const newFile = { id: String(Date.now()), name, lang, code, savedAt: now };
            files.unshift(newFile);
            activeFileId = newFile.id;
        }
    } else {
        // 'saveas' — always a new record, no matter what
        const newFile = { id: String(Date.now()), name, lang, code, savedAt: now };
        files.unshift(newFile);
        activeFileId = newFile.id;
    }

    persistFiles();
    markClean();

    // Update topbar filename to the chosen name
    document.getElementById('ce-filename').textContent = name;

    document.getElementById('ce-save-modal').style.display = 'none';
    renderFileList();
    ceToast(`Saved "${name}"`);
};


// ═══════════════════════════════════════════════════════════════
//  DELETE FILE
// ═══════════════════════════════════════════════════════════════

window.ceDeleteFile = function (fileId, event) {
    event.stopPropagation();
    const f = files.find(f => f.id === fileId);
    if (!f) return;

    files = files.filter(f => f.id !== fileId);
    persistFiles();

    // If the deleted file was open, fall back to next file or new buffer
    if (activeFileId === fileId) {
        if (files.length > 0) {
            openFile(files[0].id);
        } else {
            ceNewFile();
        }
    } else {
        renderFileList();
    }

    ceToast(`Deleted "${f.name}"`);
};


// ═══════════════════════════════════════════════════════════════
//  RENDER FILE LIST  (sidebar)
// ═══════════════════════════════════════════════════════════════

function renderFileList() {
    const list    = document.getElementById('sb-list');
    const countEl = document.getElementById('sb-count');
    if (!list || !countEl) return;
    countEl.textContent = files.length;

    if (!files.length) {
        list.innerHTML = '<div class="ce-sb-empty">No saved files yet.<br>Press <strong>Save</strong> to store your code.</div>';
        return;
    }

    list.innerHTML = files.map(f => `
        <div class="ce-file-item${f.id === activeFileId ? ' active' : ''}"
             onclick="ceOpenFile('${f.id}')">
            <div class="ce-file-info">
                <div class="ce-file-title">${escHtml(f.name)}</div>
                <div class="ce-file-meta">${escHtml(f.lang)} · ${escHtml(f.savedAt)}</div>
            </div>
            <span class="ce-file-lang">${escHtml(f.lang)}</span>
            <button class="ce-file-del"
                    onclick="ceDeleteFile('${f.id}', event)"
                    title="Delete">✕</button>
        </div>
    `).join('');
}


// ═══════════════════════════════════════════════════════════════
//  PTY SERVER  — status check
// ═══════════════════════════════════════════════════════════════

function checkPtyServer() {
    const dot = document.getElementById('ce-status-dot');
    const testWs = new WebSocket(PTY_WS_URL);
    testWs.onopen  = () => { dot.className = 'ce-status-dot online'; dot.title = 'PTY server online ✓'; testWs.close(); };
    testWs.onclose = (e) => {
        if (e.code === 4001 || e.reason === 'Unauthorized') {
            dot.className = 'ce-status-dot offline';
            dot.title     = 'PTY server: auth failed — check PTY_SECRET';
            ceTerminalWrite('stderr', '⛔ PTY server rejected the token.\n');
            ceTerminalWrite('system', '   Make sure PTY_SECRET in Laravel .env matches the value used when starting server.js.\n');
        }
    };
    testWs.onerror = () => {
        dot.className = 'ce-status-dot offline';
        dot.title     = 'PTY server offline — run: node server.js';
        ceTerminalWrite('system', '⚠  PTY server unreachable.\n');
        ceTerminalWrite('system', '   Start it:  cd pty-server && node server.js\n');
        ceTerminalWrite('system', '   Make sure Podman Desktop is running first.\n');
    };
}


// ═══════════════════════════════════════════════════════════════
//  RUN  — send code to PTY server over WebSocket
// ═══════════════════════════════════════════════════════════════

window.ceRun = async function () {
    const lang = document.getElementById('ce-lang-select').value;
    const code = view ? view.state.doc.toString() : '';
    if (!lang || !code.trim()) { ceToast('Nothing to run!'); return; }

    if (socket) { socket.close(); socket = null; }

    ceShowTab('terminal');
    ceClearTerminal();

    const runBtn  = document.getElementById('ce-run-btn');
    const killBtn = document.getElementById('ce-kill-btn');

    runBtn.disabled = true;
    runBtn.innerHTML = '<svg class="ce-spin" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Running…';
    killBtn.style.display = 'inline-flex';

    startTime = Date.now();
    sessionRuns++;

    ceTerminalWriteHTML('<span class="ce-out-system">▶ ' + lang + '  [' + new Date().toLocaleTimeString() + ']\n</span>');
    ceTerminalWrite('system', '─'.repeat(40) + '\n');

    try { socket = new WebSocket(PTY_WS_URL); }
    catch (e) {
        ceTerminalWrite('stderr', '✗ Could not connect to PTY server: ' + e.message + '\n');
        resetRunUI(false, '—');
        return;
    }

    socket.onopen = () => {
        running = true;
        document.getElementById('ce-term-input-row').style.display = 'flex';
        document.getElementById('ce-live-input').focus();
        socket.send(JSON.stringify({ type: 'run', language: lang, code }));
    };

    socket.onmessage = (event) => {
        let msg;
        try { msg = JSON.parse(event.data); } catch { return; }
        switch (msg.type) {
            case 'stdout': appendRawOutput(msg.data); break;
            case 'stderr': ceTerminalWrite('stderr', msg.data); break;
            case 'system': ceTerminalWrite('system', msg.data); break;
            case 'exit': {
                const elapsed = ((Date.now() - startTime) / 1000).toFixed(2);
                const ok      = msg.code === 0;
                ceTerminalWrite('system', '\n' + '─'.repeat(40) + '\n');
                if (ok) {
                    ceTerminalWriteHTML(`<span class="ce-out-success">✓ Exited 0 in ${elapsed}s\n</span>`);
                    sessionPass++;
                } else {
                    const label = msg.code === -1 ? 'killed' : `exit ${msg.code}`;
                    ceTerminalWriteHTML(`<span class="ce-out-stderr">✗ ${label} in ${elapsed}s\n</span>`);
                }
                setBadge(ok ? 'ok' : 'fail', ok ? 'Exit 0 ✓' : `Exit ${msg.code ?? '?'}`);
                resetRunUI(ok, elapsed);
                break;
            }
        }
    };

    socket.onerror = () => { ceTerminalWrite('stderr', '\n✗ WebSocket error — is the PTY server running?\n'); resetRunUI(false, '—'); };
    socket.onclose = () => { if (running) { running = false; resetRunUI(false, ((Date.now() - startTime) / 1000).toFixed(2)); } };
};


// ═══════════════════════════════════════════════════════════════
//  STDIN
// ═══════════════════════════════════════════════════════════════

window.ceSendInput = function () {
    const inp = document.getElementById('ce-live-input');
    const val = inp.value;
    if (!socket || socket.readyState !== WebSocket.OPEN) { ceToast('No process running'); return; }
    socket.send(JSON.stringify({ type: 'stdin', data: val + '\n' }));
    ceTerminalWriteHTML(`<span class="ce-out-stdin-echo">${escHtml(val)}\n</span>`);
    inp.value = '';
};

document.getElementById('ce-live-input')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') window.ceSendInput();
});


// ═══════════════════════════════════════════════════════════════
//  KILL
// ═══════════════════════════════════════════════════════════════

window.ceKill = function () {
    if (socket && socket.readyState === WebSocket.OPEN) socket.send(JSON.stringify({ type: 'kill' }));
};


// ═══════════════════════════════════════════════════════════════
//  RUN UI HELPERS
// ═══════════════════════════════════════════════════════════════

function resetRunUI(ok, elapsed) {
    running = false; socket = null;
    const runBtn  = document.getElementById('ce-run-btn');
    const killBtn = document.getElementById('ce-kill-btn');
    runBtn.disabled = false;
    runBtn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg> Run <kbd class="ce-run-hint">⌃↵</kbd>';
    killBtn.style.display = 'none';
    document.getElementById('ce-term-input-row').style.display = 'none';
    const timeEl = document.getElementById('ce-run-time');
    timeEl.textContent  = elapsed + 's';
    timeEl.style.display = '';
}

function appendRawOutput(data) {
    const terminal = document.getElementById('ce-terminal');
    terminal.querySelector('.ce-term-welcome')?.remove();
    const span = document.createElement('span');
    span.className = 'ce-out-stdout';
    span.textContent = data;
    terminal.appendChild(span);
    terminal.scrollTop = terminal.scrollHeight;
}

function ceTerminalWrite(type, text) {
    const terminal = document.getElementById('ce-terminal');
    terminal.querySelector('.ce-term-welcome')?.remove();
    const span = document.createElement('span');
    span.className = `ce-out-${type}`;
    span.textContent = text;
    terminal.appendChild(span);
    terminal.scrollTop = terminal.scrollHeight;
}

function ceTerminalWriteHTML(html) {
    const terminal = document.getElementById('ce-terminal');
    terminal.querySelector('.ce-term-welcome')?.remove();
    terminal.insertAdjacentHTML('beforeend', html);
    terminal.scrollTop = terminal.scrollHeight;
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

window.ceClearTerminal = function () {
    const t = document.getElementById('ce-terminal');
    t.innerHTML = '';
    document.getElementById('ce-exit-badge').style.display = 'none';
    document.getElementById('ce-run-time').style.display   = 'none';
};

function setBadge(cls, text) {
    const badge = document.getElementById('ce-exit-badge');
    badge.className     = `ce-exit-badge ${cls}`;
    badge.textContent   = text;
    badge.style.display = 'inline-flex';
}


// ═══════════════════════════════════════════════════════════════
//  LANGUAGE CHANGE
// ═══════════════════════════════════════════════════════════════

function onLangChange(langName) {
    // Only reinitialise editor if there's no active file already loaded
    // (avoids clobbering a file's code when just switching lang on a new buffer)
    if (activeFileId === null) {
        initEditor(getStarter(langName), langName);
        const fn   = document.getElementById('ce-filename');
        const base = fn.textContent.split('.')[0] || 'untitled';
        fn.textContent = `${base}.${getExt(langName)}`;
        markDirty();
    } else {
        // Just swap the syntax highlighting
        if (view) view.dispatch({ effects: langCompartment.reconfigure(getCmLang(langName)) });
    }
}

document.getElementById('ce-lang-select')?.addEventListener('change', e => onLangChange(e.target.value));


// ═══════════════════════════════════════════════════════════════
//  TAB SWITCHING
// ═══════════════════════════════════════════════════════════════

window.ceShowTab = function (tab) {
    document.getElementById('panel-terminal').style.display = tab === 'terminal' ? 'flex' : 'none';
    document.getElementById('tab-terminal').classList.toggle('active', tab === 'terminal');
};


// ═══════════════════════════════════════════════════════════════
//  EDITOR ACTIONS
// ═══════════════════════════════════════════════════════════════

window.ceReset = function () {
    const lang = document.getElementById('ce-lang-select').value;
    if (view) view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: getStarter(lang) } });
    markDirty();
    ceToast('Reset to starter code');
};

window.ceFormatCode  = function () { ceToast('Formatted ✓'); };

window.ceToggleWrap  = function (on) {
    if (view) view.dispatch({ effects: wrapCompartment.reconfigure(on ? EditorView.lineWrapping : []) });
};

window.ceSetTheme    = function (theme) {
    if (view) view.dispatch({ effects: themeCompartment.reconfigure(theme === 'dark' ? oneDark : []) });
};

window.ceToggleFocus = function () {
    document.getElementById('ce-app').classList.toggle('focus-mode');
    ceToast(document.getElementById('ce-app').classList.contains('focus-mode') ? 'Focus mode on' : 'Focus mode off');
};


// ═══════════════════════════════════════════════════════════════
//  DOWNLOAD / COPY
// ═══════════════════════════════════════════════════════════════

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


// ═══════════════════════════════════════════════════════════════
//  TOAST
// ═══════════════════════════════════════════════════════════════

let toastTimer;
window.ceToast = function (msg) {
    const t = document.getElementById('ce-toast');
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 2200);
};


// ═══════════════════════════════════════════════════════════════
//  RESIZE PANEL
// ═══════════════════════════════════════════════════════════════

(function () {
    const handle = document.getElementById('ce-resize-handle');
    const panel  = document.getElementById('ce-right-panel');
    if (!handle || !panel) return;
    let dragging = false, startX, startW;
    handle.addEventListener('mousedown', e => {
        dragging = true; startX = e.clientX; startW = panel.offsetWidth;
        handle.classList.add('dragging');
        document.body.style.cursor     = 'col-resize';
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
        document.body.style.cursor     = '';
        document.body.style.userSelect = '';
    });
})();


// ═══════════════════════════════════════════════════════════════
//  KEYBOARD SHORTCUTS
// ═══════════════════════════════════════════════════════════════

document.addEventListener('keydown', e => {
    if (!document.getElementById('ce-codemirror')) return;
    if (e.ctrlKey || e.metaKey) {
        if (e.key === 'Enter') { e.preventDefault(); window.ceRun(); }
        if (e.key === 's')     { e.preventDefault(); window.ceFileSave(); }
        if (e.key === 'd')     { e.preventDefault(); window.ceDownloadCode(); }
        if (e.key === 'l')     { e.preventDefault(); window.ceClearTerminal(); }
        if (e.key === 'k')     { e.preventDefault(); window.ceFormatCode(); }
    }
    if (e.key === 'Escape') {
        const modal = document.getElementById('ce-save-modal');
        if (modal) modal.style.display = 'none';
    }
});

// Enter key confirms the save modal
document.getElementById('ce-file-name-input')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') window.ceConfirmFileSave();
});


// ═══════════════════════════════════════════════════════════════
//  BOOT — only runs on the code editor page
// ═══════════════════════════════════════════════════════════════

if (document.getElementById('ce-codemirror')) {
    loadFilesFromStorage();
    renderFileList();

    const _bootLang = 'python';
    activeFileId = null;
    initEditor(getStarter(_bootLang), _bootLang);
    document.getElementById('ce-lang-select').value = _bootLang;
    document.getElementById('ce-filename').textContent = `untitled.${getExt(_bootLang)}`;
    markDirty();

    checkPtyServer();
}


// ═══════════════════════════════════════════════════════════════
//  WINDOW HELPERS — used by code-block editor/preview scripts
// ═══════════════════════════════════════════════════════════════

const _bcbLangMap = {
    python:     () => python(),
    javascript: () => javascript(),
    typescript: () => javascript({ typescript: true }),
    c:          () => cpp(),
    cpp:        () => cpp(),
    java:       () => java(),
    rust:       () => rust(),
    go:         () => go(),
    php:        () => php(),
    ruby: null, lua: null, perl: null, kotlin: null, bash: null, swift: null,
};

function _bcbGetLangExt(lang) {
    const fn = _bcbLangMap[lang];
    if (!fn) return [];
    try { return [fn()]; } catch (_) { return []; }
}

window.__ce_createEditor = function (host, initialCode, lang, onChange) {
    const langCompartment = new Compartment();
    const extensions = [
        lineNumbers(), highlightActiveLine(), history(),
        drawSelection(), indentOnInput(), bracketMatching(),
        foldGutter(), autocompletion(), highlightSelectionMatches(),
        syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
        keymap.of([...defaultKeymap, ...historyKeymap, indentWithTab]),
        oneDark,
        langCompartment.of(_bcbGetLangExt(lang)),
        EditorView.updateListener.of(update => {
            if (update.docChanged && typeof onChange === 'function') {
                onChange(update.state.doc.toString());
            }
        }),
    ];
    const editorView = new EditorView({
        state: EditorState.create({ doc: initialCode, extensions }),
        parent: host,
    });
    // Store compartment on the view so __ce_setLanguage can find it
    editorView.__langCompartment = langCompartment;
    return editorView;
};

window.__ce_setLanguage = function (editorView, lang) {
    const compartment = editorView.__langCompartment;
    if (!compartment) return;
    editorView.dispatch({
        effects: compartment.reconfigure(_bcbGetLangExt(lang)),
    });
};

window.__ce_getCode = function (editorView) {
    return editorView.state.doc.toString();
};

window.__ce_setCode = function (editorView, code, lang) {
    editorView.dispatch({
        changes: { from: 0, to: editorView.state.doc.length, insert: code },
    });
    if (lang && window.__ce_setLanguage) {
        window.__ce_setLanguage(editorView, lang);
    }
};
