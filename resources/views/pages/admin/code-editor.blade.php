
@extends('layouts.edditor')

@section('main')
<div class="ce-page" id="ce-app">

    {{-- ── Top bar ── --}}
    <div class="ce-topbar">
        <div class="ce-topbar-left">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            <span class="ce-topbar-title">Code Editor</span>
        </div>
        <div class="ce-topbar-right">
            <div class="ce-lang-wrap">
                <select id="ce-lang-select" class="ce-select" title="Language">
                    <option value="">Loading languages…</option>
                </select>
                <select id="ce-version-select" class="ce-select" title="Version"></select>
            </div>
            <button id="ce-run-btn" class="ce-btn ce-btn-run" onclick="ceRun()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                Run
            </button>
            <button class="ce-btn ce-btn-ghost" onclick="ceClear()">Clear</button>
            <button class="ce-btn ce-btn-ghost" onclick="ceReset()">Reset</button>
        </div>
    </div>

    {{-- ── Body ── --}}
    <div class="ce-body">

        {{-- LEFT: editor panel --}}
        <div class="ce-editor-panel">
            <div class="ce-editor-header">
                <span class="ce-panel-label">Editor</span>
                <div style="display:flex;gap:6px;align-items:center;">
                    <label class="ce-panel-label" style="cursor:pointer;">
                        <input type="checkbox" id="ce-wrap-toggle" style="margin-right:4px;" onchange="ceToggleWrap(this.checked)">
                        Word wrap
                    </label>
                    <select id="ce-theme-select" class="ce-select ce-select-sm" onchange="ceSetTheme(this.value)">
                        <option value="dark">Dark</option>
                        <option value="light">Light</option>
                    </select>
                </div>
            </div>
            <div id="ce-codemirror" class="ce-cm-host"></div>
        </div>

        {{-- RIGHT: stdin + terminal --}}
        <div class="ce-right-panel">

            {{-- stdin --}}
            <div class="ce-stdin-panel">
                <div class="ce-editor-header">
                    <span class="ce-panel-label">stdin (input)</span>
                    <button class="ce-btn-tiny" onclick="document.getElementById('ce-stdin').value=''">Clear</button>
                </div>
                <textarea id="ce-stdin" class="ce-stdin-area" placeholder="Type program input here…"></textarea>
            </div>

            {{-- terminal output --}}
            <div class="ce-terminal-panel">
                <div class="ce-editor-header">
                    <span class="ce-panel-label">Output</span>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <span id="ce-exit-badge" class="ce-exit-badge" style="display:none;"></span>
                        <button class="ce-btn-tiny" onclick="ceClearTerminal()">Clear</button>
                    </div>
                </div>
                <div id="ce-terminal" class="ce-terminal">
                    <span class="ce-terminal-hint">Press <kbd>Run</kbd> to execute your code.</span>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@section('css')
<style>
/* ══ Page shell ══ */
.ce-page {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 60px); /* subtract header */
    background: var(--bg);
    overflow: hidden;
}

/* ══ Top bar ══ */
.ce-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 18px;
    border-bottom: 1px solid var(--border);
    background: var(--bg-subtle);
    flex-shrink: 0;
    gap: 12px;
    flex-wrap: wrap;
}
.ce-topbar-left {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text);
    font-weight: 600;
    font-size: 14px;
}
.ce-topbar-title { letter-spacing: -.01em; }
.ce-topbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.ce-lang-wrap { display: flex; gap: 6px; }

/* Selects */
.ce-select {
    font-size: 12px;
    padding: 5px 8px;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: var(--bg);
    color: var(--text);
    font-family: inherit;
    cursor: pointer;
    outline: none;
}
.ce-select-sm { font-size: 11px; padding: 3px 6px; }

/* Buttons */
.ce-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 14px;
    border-radius: 7px;
    border: none;
    font-size: 12px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: background .15s, box-shadow .15s;
}
.ce-btn-run {
    background: #10b981;
    color: #fff;
}
.ce-btn-run:hover { background: #059669; }
.ce-btn-run:disabled { opacity: .5; cursor: not-allowed; }
.ce-btn-ghost {
    background: none;
    border: 1px solid var(--border);
    color: var(--text-muted);
}
.ce-btn-ghost:hover { background: var(--bg-hover); color: var(--text); }
.ce-btn-tiny {
    font-size: 10px;
    padding: 2px 8px;
    border: 1px solid var(--border);
    border-radius: 4px;
    background: none;
    color: var(--text-faint);
    cursor: pointer;
    font-family: inherit;
}
.ce-btn-tiny:hover { background: var(--bg-hover); color: var(--text); }

/* ══ Body split ══ */
.ce-body {
    display: flex;
    flex: 1;
    overflow: hidden;
    gap: 0;
}

/* ══ Editor panel ══ */
.ce-editor-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-right: 1px solid var(--border);
    min-width: 0;
}
.ce-editor-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 12px;
    border-bottom: 1px solid var(--border);
    background: var(--bg-subtle);
    flex-shrink: 0;
}
.ce-panel-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--text-faint);
}
.ce-cm-host {
    flex: 1;
    overflow: auto;
    font-size: 13px;
    line-height: 1.65;
}
/* Make CodeMirror fill the host */
.ce-cm-host .cm-editor { height: 100%; }
.ce-cm-host .cm-scroller { overflow: auto; }

/* ══ Right panel ══ */
.ce-right-panel {
    width: 380px;
    min-width: 280px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* stdin */
.ce-stdin-panel {
    display: flex;
    flex-direction: column;
    border-bottom: 1px solid var(--border);
    max-height: 160px;
}
.ce-stdin-area {
    flex: 1;
    resize: none;
    border: none;
    outline: none;
    padding: 10px 12px;
    font-size: 12px;
    font-family: 'JetBrains Mono','Fira Code',monospace;
    background: var(--bg);
    color: var(--text);
    line-height: 1.6;
    height: 110px;
}
.ce-stdin-area::placeholder { color: var(--text-faint); }

/* terminal */
.ce-terminal-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.ce-terminal {
    flex: 1;
    overflow-y: auto;
    padding: 12px 14px;
    font-family: 'JetBrains Mono','Fira Code',monospace;
    font-size: 12px;
    line-height: 1.7;
    background: #0d1117;
    color: #e2e8f0;
    white-space: pre-wrap;
    word-break: break-all;
}
.ce-terminal-hint { color: #4a5568; font-style: italic; }
.ce-terminal .ce-out-stdout { color: #e2e8f0; }
.ce-terminal .ce-out-stderr { color: #f87171; }
.ce-terminal .ce-out-compile-err { color: #fbbf24; }
.ce-terminal .ce-out-system { color: #60a5fa; font-style: italic; }

/* exit code badge */
.ce-exit-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
}
.ce-exit-badge.ok   { background: #d1fae5; color: #065f46; }
.ce-exit-badge.fail { background: #fee2e2; color: #991b1b; }
[data-theme="dark"] .ce-exit-badge.ok   { background: #064e3b; color: #6ee7b7; }
[data-theme="dark"] .ce-exit-badge.fail { background: #450a0a; color: #fca5a5; }

/* spinner */
.ce-spin { animation: ce-spin .7s linear infinite; display: inline-block; }
@keyframes ce-spin { to { transform: rotate(360deg); } }

/* scrollbar */
.ce-terminal::-webkit-scrollbar { width: 6px; }
.ce-terminal::-webkit-scrollbar-track { background: #0d1117; }
.ce-terminal::-webkit-scrollbar-thumb { background: #2d3748; border-radius: 3px; }
</style>
@endsection

@section('js')
{{-- CodeMirror 6 via CDN --}}
<script type="module">
// ── Import CodeMirror 6 from CDN ──
import { EditorState, Compartment } from 'https://esm.sh/@codemirror/state@6';
import { EditorView, keymap, lineNumbers, highlightActiveLine, drawSelection } from 'https://esm.sh/@codemirror/view@6';
import { defaultKeymap, history, historyKeymap, indentWithTab } from 'https://esm.sh/@codemirror/commands@6';
import { indentOnInput, bracketMatching, foldGutter } from 'https://esm.sh/@codemirror/language@6';
import { python }     from 'https://esm.sh/@codemirror/lang-python@6';
import { javascript } from 'https://esm.sh/@codemirror/lang-javascript@6';
import { cpp }        from 'https://esm.sh/@codemirror/lang-cpp@6';
import { java }       from 'https://esm.sh/@codemirror/lang-java@6';
import { rust }       from 'https://esm.sh/@codemirror/lang-rust@6';
import { oneDark }    from 'https://esm.sh/@codemirror/theme-one-dark@6';
import { autocompletion } from 'https://esm.sh/@codemirror/autocomplete@6';

// ── State ──
let view, runtimes = [], currentLang = null;
const langCompartment    = new Compartment();
const themeCompartment   = new Compartment();
const wrapCompartment    = new Compartment();

// ── Language map: Piston language → CodeMirror extension ──
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

function getCmLang(langName) {
    return CM_LANG[langName?.toLowerCase()] ?? [];
}

// ── Starter code templates ──
const STARTERS = {
    python:     'print("Hello, World!")\n',
    javascript: 'console.log("Hello, World!");\n',
    typescript: 'console.log("Hello, World!");\n',
    cpp:        '#include <iostream>\nusing namespace std;\nint main() {\n    cout << "Hello, World!" << endl;\n    return 0;\n}\n',
    'c++':      '#include <iostream>\nusing namespace std;\nint main() {\n    cout << "Hello, World!" << endl;\n    return 0;\n}\n',
    c:          '#include <stdio.h>\nint main() {\n    printf("Hello, World!\\n");\n    return 0;\n}\n',
    java:       'public class Main {\n    public static void main(String[] args) {\n        System.out.println("Hello, World!");\n    }\n}\n',
    rust:       'fn main() {\n    println!("Hello, World!");\n}\n',
};

function getStarter(langName) {
    return STARTERS[langName?.toLowerCase()] ?? '// Start coding...\n';
}

// ── Init CodeMirror ──
function initEditor(code = '// Start coding...\n', langName = '') {
    const host = document.getElementById('ce-codemirror');
    if (view) view.destroy();

    const isDark = document.documentElement.dataset.theme !== 'light';

    view = new EditorView({
        state: EditorState.create({
            doc: code,
            extensions: [
                lineNumbers(),
                highlightActiveLine(),
                history(),
                drawSelection(),
                indentOnInput(),
                bracketMatching(),
                foldGutter(),
                autocompletion(),
                keymap.of([...defaultKeymap, ...historyKeymap, indentWithTab]),
                langCompartment.of(getCmLang(langName)),
                themeCompartment.of(isDark ? oneDark : []),
                wrapCompartment.of([]),
                EditorView.lineWrapping,
            ],
        }),
        parent: host,
    });
}

// ── Load runtimes ──
async function loadRuntimes() {
    try {
        const res  = await fetch('/api/code/runtimes');
        const data = await res.json();

        runtimes = data;

        // Group by language name, keep latest version per language
        const langMap = {};
        data.forEach(r => {
            if (!langMap[r.language]) langMap[r.language] = [];
            langMap[r.language].push(r.version);
        });

        const langSel = document.getElementById('ce-lang-select');
        langSel.innerHTML = '';

        // Sort alphabetically
        Object.keys(langMap).sort().forEach(lang => {
            const opt = document.createElement('option');
            opt.value = lang;
            opt.textContent = lang.charAt(0).toUpperCase() + lang.slice(1);
            langSel.appendChild(opt);
        });

        // Expose langMap globally for version updates
        window._ceLangMap = langMap;

        // Default to python if available
        const defaultLang = langMap['python'] ? 'python' : Object.keys(langMap)[0];
        langSel.value = defaultLang;
        onLangChange(defaultLang);

    } catch (e) {
        document.getElementById('ce-lang-select').innerHTML = '<option>Error loading languages</option>';
        ceTerminalWrite('system', '⚠ Could not reach Piston. Is it running on localhost:2000?');
    }
}

function onLangChange(langName) {
    currentLang = langName;
    const verSel = document.getElementById('ce-version-select');
    const versions = window._ceLangMap?.[langName] ?? [];
    verSel.innerHTML = versions
        .sort((a, b) => b.localeCompare(a, undefined, { numeric: true }))
        .map(v => `<option value="${v}">${v}</option>`)
        .join('');

    const starter = getStarter(langName);
    initEditor(starter, langName);
}

// ── Events ──
document.getElementById('ce-lang-select').addEventListener('change', e => onLangChange(e.target.value));

// ── Expose to window for inline handlers ──
window.ceRun = async function () {
    const lang    = document.getElementById('ce-lang-select').value;
    const version = document.getElementById('ce-version-select').value;
    const code    = view ? view.state.doc.toString() : '';
    const stdin   = document.getElementById('ce-stdin').value;

    if (!lang || !version || !code.trim()) return;

    const btn = document.getElementById('ce-run-btn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="ce-spin" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Running…';

    ceClearTerminal();
    ceTerminalWrite('system', `▶ Running ${lang} ${version}…\n`);

    try {
        const res  = await fetch('/api/code/execute', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ language: lang, version, code, stdin }),
        });

        const data = await res.json();

        if (data.error) {
            ceTerminalWrite('stderr', '✗ ' + data.error);
            setBadge('fail', '✗');
            return;
        }

        // Compile errors (C, C++, Java, Rust)
        if (data.compile?.stderr) {
            ceTerminalWrite('compile-err', '[Compile Error]\n' + data.compile.stderr);
        }
        if (data.compile?.stdout) {
            ceTerminalWrite('stdout', data.compile.stdout);
        }

        if (data.stdout) ceTerminalWrite('stdout', data.stdout);
        if (data.stderr) ceTerminalWrite('stderr', data.stderr);

        if (!data.stdout && !data.stderr && !data.compile?.stderr) {
            ceTerminalWrite('system', '(no output)');
        }

        const code2 = data.exit_code;
        setBadge(code2 === 0 ? 'ok' : 'fail', code2 === 0 ? `Exit 0 ✓` : `Exit ${code2}`);

    } catch (e) {
        ceTerminalWrite('stderr', '✗ Network error: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg> Run';
    }
};

window.ceClear = function () {
    if (view) {
        view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: '' } });
    }
};

window.ceReset = function () {
    const lang = document.getElementById('ce-lang-select').value;
    if (view) {
        const starter = getStarter(lang);
        view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: starter } });
    }
};

window.ceClearTerminal = function () {
    document.getElementById('ce-terminal').innerHTML = '';
    const badge = document.getElementById('ce-exit-badge');
    badge.style.display = 'none';
};

window.ceToggleWrap = function (on) {
    if (view) {
        view.dispatch({ effects: wrapCompartment.reconfigure(on ? EditorView.lineWrapping : []) });
    }
};

window.ceSetTheme = function (theme) {
    if (view) {
        view.dispatch({ effects: themeCompartment.reconfigure(theme === 'dark' ? oneDark : []) });
    }
};

function setBadge(cls, text) {
    const badge = document.getElementById('ce-exit-badge');
    badge.className = `ce-exit-badge ${cls}`;
    badge.textContent = text;
    badge.style.display = 'inline-flex';
}

function ceTerminalWrite(type, text) {
    const terminal = document.getElementById('ce-terminal');
    const span = document.createElement('span');
    span.className = `ce-out-${type}`;
    span.textContent = text;
    terminal.appendChild(span);
    terminal.scrollTop = terminal.scrollHeight;
}

// ── Keyboard shortcut: Ctrl+Enter to run ──
document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        window.ceRun();
    }
});

// ── Boot ──
loadRuntimes();
</script>
@endsection
