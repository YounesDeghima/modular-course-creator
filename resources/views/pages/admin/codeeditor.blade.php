@extends('layouts.edditor')

@section('css')
<link rel="stylesheet" href="{{ asset('css/modular-site-preview.css') }}">
<style>
:root {
    --ce-bg:        #0d1117;
    --ce-bg2:       #161b22;
    --ce-bg3:       #21262d;
    --ce-border:    #30363d;
    --ce-text:      #e6edf3;
    --ce-text-dim:  #8b949e;
    --ce-green:     #3fb950;
    --ce-blue:      #79c0ff;
    --ce-red:       #ff7b72;
    --ce-yellow:    #e3b341;
    --ce-accent:    #1f6feb;
}

body { background: var(--ce-bg) !important; }

.ce-layout {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 60px);
    background: var(--ce-bg);
    color: var(--ce-text);
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
}

.ce-toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    background: var(--ce-bg2);
    border-bottom: 1px solid var(--ce-border);
    flex-wrap: wrap;
}

.ce-toolbar-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--ce-blue);
    margin-right: 8px;
    white-space: nowrap;
}

.ce-select {
    background: var(--ce-bg3);
    border: 1px solid var(--ce-border);
    color: var(--ce-text);
    border-radius: 6px;
    padding: 5px 10px;
    font-size: 12px;
    font-family: inherit;
    cursor: pointer;
    outline: none;
}
.ce-select:focus { border-color: var(--ce-accent); }

.ce-input-title {
    flex: 1;
    min-width: 120px;
    max-width: 260px;
    background: var(--ce-bg3);
    border: 1px solid var(--ce-border);
    color: var(--ce-text);
    border-radius: 6px;
    padding: 5px 10px;
    font-size: 12px;
    font-family: inherit;
    outline: none;
}
.ce-input-title:focus { border-color: var(--ce-accent); }

.ce-btn {
    border: none;
    border-radius: 6px;
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: filter .15s;
    white-space: nowrap;
}
.ce-btn:hover { filter: brightness(1.15); }
.ce-btn:disabled { opacity: .5; cursor: not-allowed; }

.ce-btn-run  { background: #238636; color: #fff; }
.ce-btn-save { background: var(--ce-accent); color: #fff; }
.ce-btn-hist { background: var(--ce-bg3); color: var(--ce-text); border: 1px solid var(--ce-border); }
.ce-btn-clear{ background: var(--ce-bg3); color: var(--ce-text-dim); border: 1px solid var(--ce-border); }

.ce-body {
    display: flex;
    flex: 1;
    overflow: hidden;
}

.ce-editor-pane {
    flex: 1;
    display: flex;
    flex-direction: column;
    border-right: 1px solid var(--ce-border);
    min-width: 0;
}

.ce-editor-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: var(--ce-bg2);
    border-bottom: 1px solid var(--ce-border);
    font-size: 11px;
    color: var(--ce-text-dim);
}

.ce-lang-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--ce-blue);
    display: inline-block;
}

#ce-textarea {
    flex: 1;
    resize: none;
    background: var(--ce-bg);
    color: var(--ce-text);
    border: none;
    outline: none;
    padding: 16px;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    font-size: 14px;
    line-height: 1.7;
    tab-size: 4;
    white-space: pre;
    overflow-wrap: normal;
    overflow-x: auto;
}

.ce-terminal-pane {
    width: 42%;
    min-width: 280px;
    display: flex;
    flex-direction: column;
    background: #010409;
}

.ce-terminal-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: var(--ce-bg2);
    border-bottom: 1px solid var(--ce-border);
    font-size: 11px;
    color: var(--ce-text-dim);
}

.ce-terminal-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--ce-green);
    display: inline-block;
}

#ce-output {
    flex: 1;
    overflow-y: auto;
    padding: 12px 16px;
    font-size: 13px;
    line-height: 1.6;
    color: var(--ce-text);
    white-space: pre-wrap;
    word-break: break-all;
}

.ce-line-stdout  { color: #e6edf3; }
.ce-line-stderr  { color: #ff7b72; }
.ce-line-info    { color: #8b949e; font-style: italic; }
.ce-line-success { color: #3fb950; }
.ce-line-stdin   { color: #79c0ff; }

.ce-stdin-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px 8px;
    border-top: 1px solid var(--ce-border);
    background: var(--ce-bg2);
}

.ce-prompt { color: var(--ce-green); font-weight: bold; font-size: 15px; }

#ce-stdin-input {
    flex: 1;
    background: transparent;
    border: none;
    outline: none;
    color: var(--ce-blue);
    font-family: 'JetBrains Mono', monospace;
    font-size: 13px;
    caret-color: var(--ce-green);
}

.ce-stdin-send {
    background: var(--ce-bg3);
    border: 1px solid var(--ce-border);
    border-radius: 4px;
    color: var(--ce-text-dim);
    cursor: pointer;
    padding: 3px 10px;
    font-size: 13px;
}

.ce-status-bar {
    padding: 4px 16px;
    background: var(--ce-bg2);
    border-top: 1px solid var(--ce-border);
    font-size: 11px;
    color: var(--ce-text-dim);
}

.ce-history-panel {
    position: fixed;
    top: 0; right: -400px;
    width: 380px;
    height: 100vh;
    background: var(--ce-bg2);
    border-left: 1px solid var(--ce-border);
    display: flex;
    flex-direction: column;
    transition: right .25s ease;
    z-index: 9999;
    font-family: 'JetBrains Mono', monospace;
}
.ce-history-panel.open { right: 0; }

.ce-history-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid var(--ce-border);
    font-size: 13px;
    font-weight: 700;
    color: var(--ce-blue);
}

.ce-history-close {
    background: none;
    border: none;
    color: var(--ce-text-dim);
    font-size: 18px;
    cursor: pointer;
    line-height: 1;
}

.ce-history-list {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
}

.ce-history-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px;
    border-radius: 6px;
    cursor: pointer;
    border: 1px solid transparent;
    margin-bottom: 4px;
    transition: background .1s;
}
.ce-history-item:hover {
    background: var(--ce-bg3);
    border-color: var(--ce-border);
}

.ce-history-info { flex: 1; min-width: 0; }
.ce-history-title {
    font-size: 12px;
    font-weight: 600;
    color: var(--ce-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ce-history-meta {
    font-size: 10px;
    color: var(--ce-text-dim);
    margin-top: 2px;
}
.ce-history-del {
    background: none;
    border: none;
    color: var(--ce-text-dim);
    font-size: 14px;
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 4px;
    line-height: 1;
    flex-shrink: 0;
}
.ce-history-del:hover { color: var(--ce-red); background: rgba(255,119,104,.1); }

.ce-history-empty {
    text-align: center;
    padding: 30px 16px;
    color: var(--ce-text-dim);
    font-size: 12px;
}

.ce-spinner { display: inline-block; animation: cespin .7s linear infinite; }
@keyframes cespin { to { transform: rotate(360deg); } }
</style>
@endsection

@section('main')
<div class="ce-layout">

    <div class="ce-toolbar">
        <span class="ce-toolbar-title">⌨ Code Editor</span>

        <select id="ce-lang-select" class="ce-select" style="min-width:160px;" onchange="ceLangChange(this.value)">
            <option>⏳ Loading…</option>
        </select>

        <select id="ce-ver-select" class="ce-select" style="width:110px;">
            <option>*</option>
        </select>

        <input id="ce-title-input" class="ce-input-title" type="text" placeholder="Session title…" value="Untitled">

        <button class="ce-btn ce-btn-run"  id="ce-run-btn"  onclick="ceRun()">▶ Run</button>
        <button class="ce-btn ce-btn-save" onclick="ceSave()">💾 Save</button>
        <button class="ce-btn ce-btn-hist" onclick="ceToggleHistory()">🕒 History</button>
        <button class="ce-btn ce-btn-clear" onclick="ceClearOutput()">✕ Clear</button>
    </div>

    <div class="ce-body">

        <div class="ce-editor-pane">
            <div class="ce-editor-header">
                <span class="ce-lang-dot"></span>
                <span id="ce-editor-lang-label">javascript</span>
                <span style="margin-left:auto;font-size:10px;">Tab = 4 spaces · Ctrl+Enter = Run</span>
            </div>
            <textarea
                id="ce-textarea"
                spellcheck="false"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                placeholder="// Start coding…"
                onkeydown="ceHandleKey(event)"
            ></textarea>
        </div>

        <div class="ce-terminal-pane">
            <div class="ce-terminal-header">
                <span class="ce-terminal-dot"></span>
                <span>Terminal</span>
                <span style="margin-left:auto" id="ce-status-label"></span>
            </div>

            <div id="ce-output"></div>

            <div class="ce-stdin-row" id="ce-stdin-row" style="display:none;">
                <span class="ce-prompt">›</span>
                <input
                    id="ce-stdin-input"
                    type="text"
                    placeholder="Type input and press Enter…"
                    autocomplete="off"
                    spellcheck="false"
                    onkeydown="if(event.key==='Enter') ceSendStdin()"
                >
                <button class="ce-stdin-send" onclick="ceSendStdin()">↵</button>
            </div>

            <div class="ce-status-bar" id="ce-status-bar">Ready</div>
        </div>
    </div>
</div>

<div class="ce-history-panel" id="ce-history-panel">
    <div class="ce-history-head">
        <span>Session History</span>
        <button class="ce-history-close" onclick="ceToggleHistory()">✕</button>
    </div>
    <div class="ce-history-list" id="ce-history-list">
        <div class="ce-history-empty">Loading…</div>
    </div>
</div>
@endsection

@section('js')
<script>
const CE = {
    runtimes:       [],
    running:        false,
    stdinResolve:   null,
    stdinBuffer:    [],
    historyOpen:    false,
};

function ceSetStatus(text) {
    const bar   = document.getElementById('ce-status-bar');
    const label = document.getElementById('ce-status-label');
    if (bar)   bar.innerHTML   = text;
    if (label) label.innerHTML = text;
}

function cePrint(text, cls = 'ce-line-stdout') {
    const out = document.getElementById('ce-output');
    if (!out) return;
    const div = document.createElement('div');
    div.className = cls;
    div.textContent = text;
    out.appendChild(div);
    out.scrollTop = out.scrollHeight;
}

function ceClearOutput() {
    const out = document.getElementById('ce-output');
    if (out) out.innerHTML = '';
    ceSetStatus('Ready');
    document.getElementById('ce-stdin-row').style.display = 'none';
}

function ceHandleKey(e) {
    if (e.key === 'Tab') {
        e.preventDefault();
        const ta    = document.getElementById('ce-textarea');
        const start = ta.selectionStart;
        const end   = ta.selectionEnd;
        ta.value    = ta.value.substring(0, start) + '    ' + ta.value.substring(end);
        ta.selectionStart = ta.selectionEnd = start + 4;
    }
    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
        e.preventDefault();
        ceRun();
    }
}

async function ceLoadRuntimes() {
    try {
        const res  = await fetch('/code/runtimes');
        const data = await res.json();
        if (!Array.isArray(data)) throw new Error('Bad response');

        CE.runtimes = data;

        const langMap = {};
        data.forEach(r => {
            if (!langMap[r.language]) langMap[r.language] = r.version;
        });

        const sel = document.getElementById('ce-lang-select');
        sel.innerHTML = Object.keys(langMap).sort()
            .map(l => `<option value="${l}">${l}</option>`)
            .join('');

        // Default to javascript if available
        if (langMap['javascript']) sel.value = 'javascript';
        ceLangChange(sel.value);

    } catch (e) {
        document.getElementById('ce-lang-select').innerHTML =
            '<option>Piston not running</option>';
        ceSetStatus('⚠ Piston not reachable — run: docker run -d -p 2000:2000 ghcr.io/engineer-man/piston');
    }
}

function ceLangChange(lang) {
    document.getElementById('ce-editor-lang-label').textContent = lang;

    const verSel  = document.getElementById('ce-ver-select');
    const versions = CE.runtimes.filter(r => r.language === lang).map(r => r.version);
    verSel.innerHTML = versions.map(v => `<option value="${v}">${v}</option>`).join('');

    const ta = document.getElementById('ce-textarea');
    if (!ta.value.trim()) {
        ta.value = ceStarterCode(lang);
    }
}

function ceStarterCode(lang) {
    const templates = {
        javascript: '// Hello World in JavaScript\nconsole.log("Hello, World!");',
        python:     '# Hello World in Python\nprint("Hello, World!")',
        'c':        '#include <stdio.h>\n\nint main() {\n    printf("Hello, World!\\n");\n    return 0;\n}',
        'c++':      '#include <iostream>\nusing namespace std;\n\nint main() {\n    cout << "Hello, World!" << endl;\n    return 0;\n}',
        java:       'public class main {\n    public static void main(String[] args) {\n        System.out.println("Hello, World!");\n    }\n}',
        rust:       'fn main() {\n    println!("Hello, World!");\n}',
        go:         'package main\n\nimport "fmt"\n\nfunc main() {\n    fmt.Println("Hello, World!")\n}',
        php:        '<?php\necho "Hello, World!\\n";',
        ruby:       'puts "Hello, World!"',
        bash:       '#!/bin/bash\necho "Hello, World!"',
    };
    return templates[lang] || `// ${lang}\n// Start coding…`;
}

function ceRequestStdin(prompt) {
    return new Promise(resolve => {
        CE.stdinResolve = resolve;
        if (prompt) cePrint(prompt, 'ce-line-info');
        const row = document.getElementById('ce-stdin-row');
        const inp = document.getElementById('ce-stdin-input');
        row.style.display = 'flex';
        inp.value = '';
        inp.focus();
    });
}

function ceSendStdin() {
    const inp   = document.getElementById('ce-stdin-input');
    const value = inp.value;
    inp.value   = '';
    document.getElementById('ce-stdin-row').style.display = 'none';

    cePrint('› ' + value, 'ce-line-stdin');
    CE.stdinBuffer.push(value);

    if (CE.stdinResolve) {
        CE.stdinResolve(value);
        CE.stdinResolve = null;
    }
}

// ── Run ───────────────────────────────────────

async function ceRun() {
    if (CE.running) return;

    const ta   = document.getElementById('ce-textarea');
    const lang = document.getElementById('ce-lang-select').value;
    const ver  = document.getElementById('ce-ver-select').value;
    const code = ta.value;

    if (!code.trim()) { ceSetStatus('Nothing to run'); return; }
    if (!lang || lang === '⏳ Loading…' || lang === 'Piston not running') {
        ceSetStatus('⚠ Select a language first'); return;
    }

    CE.running     = true;
    CE.stdinBuffer = [];
    ceClearOutput();

    const runBtn = document.getElementById('ce-run-btn');
    runBtn.disabled = true;

    cePrint(`Running ${lang} ${ver}…`, 'ce-line-info');
    ceSetStatus('<span class="ce-spinner">⟳</span> Executing…');

    try {
        // Phase 1: run with empty stdin
        const r1 = await cePost('/code/run', { language: lang, version: ver, code, stdin: '' });

        // Compile error
        if (r1.compile?.code !== 0 && r1.compile?.stderr) {
            cePrint('── Compile Error ──', 'ce-line-info');
            r1.compile.stderr.split('\n').forEach(l => cePrint(l, 'ce-line-stderr'));
            ceSetStatus('✗ Compile error');
            return;
        }

        const needsInput = r1.code !== 0 || r1.stderr?.includes('EOF') || r1.stderr?.includes('EOFError');

        if (!needsInput) {
            if (r1.stdout) r1.stdout.split('\n').forEach(l => cePrint(l, 'ce-line-stdout'));
            if (r1.stderr) r1.stderr.split('\n').forEach(l => cePrint(l, 'ce-line-stderr'));
            cePrint(`── Exited ${r1.code} ──`, r1.code === 0 ? 'ce-line-success' : 'ce-line-stderr');
            ceSetStatus(r1.code === 0 ? '✓ Done' : `✗ Exit ${r1.code}`);
            return;
        }

        // Phase 2: collect stdin interactively
        if (r1.stdout) r1.stdout.split('\n').filter(l => l.trim()).forEach(l => cePrint(l, 'ce-line-stdout'));

        const promptCount = Math.max(1, (r1.stdout.match(/[:?]\s*$/gm) || []).length);
        const stdinLines  = [];
        for (let i = 0; i < promptCount; i++) {
            const promptLine = (r1.stdout.split('\n').filter(l => l.trim()))[i] || null;
            const val = await ceRequestStdin(i === 0 ? null : promptLine);
            stdinLines.push(val);
        }

        cePrint('', 'ce-line-info');
        ceSetStatus('<span class="ce-spinner">⟳</span> Re-running…');

        // Phase 3: re-run with stdin
        const r3 = await cePost('/code/run', { language: lang, version: ver, code, stdin: stdinLines.join('\n') });

        if (r3.stdout) r3.stdout.split('\n').forEach(l => cePrint(l, 'ce-line-stdout'));
        if (r3.stderr) r3.stderr.split('\n').forEach(l => cePrint(l, 'ce-line-stderr'));
        cePrint(`── Exited ${r3.code} ──`, r3.code === 0 ? 'ce-line-success' : 'ce-line-stderr');
        ceSetStatus(r3.code === 0 ? '✓ Done' : `✗ Exit ${r3.code}`);

    } catch (e) {
        cePrint('Error: ' + e.message, 'ce-line-stderr');
        ceSetStatus('✗ Network error');
    } finally {
        CE.running = false;
        runBtn.disabled = false;
    }
}

// ── Save ──────────────────────────────────────

async function ceSave() {
    const ta    = document.getElementById('ce-textarea');
    const lang  = document.getElementById('ce-lang-select').value;
    const ver   = document.getElementById('ce-ver-select').value;
    const title = document.getElementById('ce-title-input').value || 'Untitled';
    const code  = ta.value;

    if (!code.trim()) { ceSetStatus('Nothing to save'); return; }

    try {
        await cePost('/codeeditor/save', { language: lang, version: ver, code, title });
        ceSetStatus('✓ Saved');
        setTimeout(() => ceSetStatus('Ready'), 2000);
        if (CE.historyOpen) ceLoadHistory();
    } catch (e) {
        ceSetStatus('✗ Save failed');
    }
}

// ── History ───────────────────────────────────

function ceToggleHistory() {
    CE.historyOpen = !CE.historyOpen;
    document.getElementById('ce-history-panel').classList.toggle('open', CE.historyOpen);
    if (CE.historyOpen) ceLoadHistory();
}

async function ceLoadHistory() {
    const list = document.getElementById('ce-history-list');
    list.innerHTML = '<div class="ce-history-empty">Loading…</div>';

    try {
        const res  = await fetch('/codeeditor/history');
        const data = await res.json();

        if (!data.length) {
            list.innerHTML = '<div class="ce-history-empty">No saved sessions yet.</div>';
            return;
        }

        list.innerHTML = data.map(s => `
            <div class="ce-history-item" onclick="ceLoadSession(${JSON.stringify(s).replace(/"/g, '&quot;')})">
                <div class="ce-history-info">
                    <div class="ce-history-title">${ceEsc(s.title)}</div>
                    <div class="ce-history-meta">${ceEsc(s.language)} · ${ceEsc(s.created_at)}</div>
                </div>
                <button class="ce-history-del" onclick="event.stopPropagation();ceDelSession(${s.id}, this)">🗑</button>
            </div>
        `).join('');

    } catch (e) {
        list.innerHTML = '<div class="ce-history-empty">Failed to load history.</div>';
    }
}

function ceLoadSession(session) {
    document.getElementById('ce-textarea').value          = session.code;
    document.getElementById('ce-title-input').value       = session.title;

    // Set language
    const langSel = document.getElementById('ce-lang-select');
    if ([...langSel.options].some(o => o.value === session.language)) {
        langSel.value = session.language;
        ceLangChange(session.language);
        // Set version
        const verSel = document.getElementById('ce-ver-select');
        if ([...verSel.options].some(o => o.value === session.version)) {
            verSel.value = session.version;
        }
    }

    // Restore code (ceLangChange sets starter code if empty)
    document.getElementById('ce-textarea').value = session.code;
    ceToggleHistory();
}

async function ceDelSession(id, btn) {
    btn.textContent = '…';
    try {
        await fetch(`/codeeditor/history/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken() },
        });
        ceLoadHistory();
    } catch { btn.textContent = '🗑'; }
}

// ── Utilities ─────────────────────────────────

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function cePost(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(body),
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
}

function ceEsc(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ── Boot ──────────────────────────────────────
document.addEventListener('DOMContentLoaded', ceLoadRuntimes);
</script>
@endsection
