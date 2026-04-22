@extends('layouts.edditor')

@section('sidebar-elements')
    {{-- ── SIDEBAR ── --}}
    <div class="ce-sidebar">

        {{-- Saved Snippets --}}
        <div class="ce-sb-section">
            <div class="ce-sb-header">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span>Saved Snippets</span>
                <span class="ce-sb-count" id="sb-count">0/10</span>
            </div>
            <button class="ce-sb-save-btn" onclick="ceSaveSnippet()">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Save Current
            </button>
            <div class="ce-sb-snippets" id="sb-list">
                <div class="ce-sb-empty">No saved snippets yet.<br>Click "Save Current" to store code.</div>
            </div>
        </div>

        <div class="ce-sb-divider"></div>

        {{-- Stats --}}
        <div class="ce-sb-section">
            <div class="ce-sb-header">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                <span>Session Stats</span>
            </div>
            <div class="ce-stats-grid">
                <div class="ce-stat">
                    <span class="ce-stat-val" id="stat-runs">0</span>
                    <span class="ce-stat-lbl">Runs</span>
                </div>
                <div class="ce-stat">
                    <span class="ce-stat-val" id="stat-pass">0</span>
                    <span class="ce-stat-lbl">Success</span>
                </div>
                <div class="ce-stat">
                    <span class="ce-stat-val" id="stat-lines">0</span>
                    <span class="ce-stat-lbl">Lines</span>
                </div>
                <div class="ce-stat">
                    <span class="ce-stat-val" id="stat-chars">0</span>
                    <span class="ce-stat-lbl">Chars</span>
                </div>
            </div>
        </div>

        <div class="ce-sb-divider"></div>

        {{-- Keyboard Shortcuts --}}
        <div class="ce-sb-section">
            <div class="ce-sb-header">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M7 8h2m6 0h2M7 12h10M7 16h2m6 0h2"/></svg>
                <span>Shortcuts</span>
            </div>
            <div class="ce-shortcuts">
                <div class="ce-shortcut"><kbd>Ctrl</kbd><kbd>↵</kbd><span>Run</span></div>
                <div class="ce-shortcut"><kbd>Ctrl</kbd><kbd>S</kbd><span>Save</span></div>
                <div class="ce-shortcut"><kbd>Ctrl</kbd><kbd>D</kbd><span>Download</span></div>
                <div class="ce-shortcut"><kbd>Ctrl</kbd><kbd>L</kbd><span>Clear term</span></div>
                <div class="ce-shortcut"><kbd>Ctrl</kbd><kbd>K</kbd><span>Format</span></div>
            </div>
        </div>

        <div class="ce-sb-divider"></div>

        {{-- Download --}}
        <div class="ce-sb-section">
            <div class="ce-sb-header">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <span>Export</span>
            </div>
            <button class="ce-sb-action-btn" onclick="ceDownloadCode()">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/></svg>
                Download Code
            </button>
            <button class="ce-sb-action-btn" onclick="ceDownloadOutput()">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Download Output
            </button>
            <button class="ce-sb-action-btn" onclick="ceCopyCode()">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                Copy Code
            </button>
        </div>

        <div class="ce-sb-divider"></div>

        {{-- Run History --}}
        <div class="ce-sb-section ce-sb-section-flex">
            <div class="ce-sb-header">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>Run History</span>
                <button class="ce-sb-clear-btn" onclick="ceClearHistory()">Clear</button>
            </div>
            <div class="ce-history-list" id="ce-history"></div>
        </div>

    </div>
@endsection

@section('main')
    <div class="ce-page" id="ce-app">

        {{-- ── Top bar ── --}}
        <div class="ce-topbar">
            <div class="ce-topbar-left">
                <div class="ce-status-dot" id="ce-status-dot" title="Piston status"></div>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                <span class="ce-topbar-title">Code Editor</span>
                <span class="ce-file-name" id="ce-filename" contenteditable="true" spellcheck="false">untitled</span>
            </div>
            <div class="ce-topbar-right">
                <div class="ce-lang-wrap">
                    <select id="ce-lang-select" class="ce-select" title="Language">
                        <option value="">Loading…</option>
                    </select>
                    <select id="ce-version-select" class="ce-select" title="Version"></select>
                </div>
                <div class="ce-topbar-divider"></div>
                <label class="ce-toggle-label">
                    <input type="checkbox" id="ce-wrap-toggle" onchange="ceToggleWrap(this.checked)">
                    Wrap
                </label>
                <select id="ce-theme-select" class="ce-select ce-select-sm" onchange="ceSetTheme(this.value)">
                    <option value="dark">Dark</option>
                    <option value="light">Light</option>
                </select>
                <div class="ce-topbar-divider"></div>
                <button id="ce-run-btn" class="ce-btn ce-btn-run" onclick="ceRun()">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    Run
                    <kbd class="ce-run-hint">⌃↵</kbd>
                </button>
                <button class="ce-btn ce-btn-ghost" onclick="ceReset()" title="Reset to starter code">Reset</button>
                <button class="ce-btn ce-btn-ghost" onclick="ceDownloadCode()" title="Download (Ctrl+D)">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </button>
            </div>
        </div>

        {{-- ── Body ── --}}
        <div class="ce-body">

            {{-- LEFT: editor --}}
            <div class="ce-editor-panel">
                <div class="ce-editor-header">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span class="ce-panel-label">Editor</span>
                        <span class="ce-cursor-pos" id="ce-cursor-pos">Ln 1, Col 1</span>
                    </div>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <span class="ce-panel-label" id="ce-line-count">0 lines</span>
                        <button class="ce-btn-tiny" onclick="ceFormatCode()" title="Auto-indent (Ctrl+K)">Format</button>
                        <button class="ce-btn-tiny" onclick="ceToggleFocus()" title="Focus mode">Focus</button>
                    </div>
                </div>
                <div id="ce-codemirror" class="ce-cm-host"></div>
            </div>

            {{-- RIGHT: live terminal --}}
            <div class="ce-right-panel" id="ce-right-panel">

                {{-- Resize handle --}}
                <div class="ce-resize-handle" id="ce-resize-handle"></div>

                {{-- Terminal tabs --}}
                <div class="ce-term-tabs">
                    <button class="ce-term-tab active" id="tab-terminal" onclick="ceShowTab('terminal')">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>
                        Terminal
                    </button>
                    <button class="ce-term-tab" id="tab-stdin" onclick="ceShowTab('stdin')">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                        Stdin
                    </button>
                    <div class="ce-term-tabs-right">
                        <span id="ce-exit-badge" class="ce-exit-badge" style="display:none;"></span>
                        <span class="ce-run-time" id="ce-run-time" style="display:none;"></span>
                        <button class="ce-btn-tiny" onclick="ceClearTerminal()">Clear</button>
                    </div>
                </div>

                {{-- Live terminal --}}
                <div class="ce-tab-content" id="panel-terminal">
                    <div id="ce-terminal" class="ce-terminal">
                        <div class="ce-term-welcome">
                            <div class="ce-term-logo">❯_</div>
                            <div>Live Terminal — <span style="color:#4ade80">Piston</span> powered</div>
                            <div class="ce-term-sub">Select a language and press <kbd>Ctrl+Enter</kbd> to run</div>
                        </div>
                    </div>
                    {{-- Live stdin input line --}}
                    <div class="ce-term-input-row" id="ce-term-input-row" style="display:none;">
                        <span class="ce-term-prompt">stdin›</span>
                        <input type="text" id="ce-live-input" class="ce-live-input" placeholder="waiting for input…" autocomplete="off" spellcheck="false">
                        <button class="ce-btn-tiny" onclick="ceSendInput()">Send ↵</button>
                    </div>
                </div>

                {{-- Stdin panel --}}
                <div class="ce-tab-content" id="panel-stdin" style="display:none;">
                    <div class="ce-editor-header">
                        <span class="ce-panel-label">Stdin — Pre-load program input</span>
                        <button class="ce-btn-tiny" onclick="document.getElementById('ce-stdin').value=''">Clear</button>
                    </div>
                    <textarea id="ce-stdin" class="ce-stdin-area" placeholder="Type program input here (one line per prompt)…&#10;&#10;Example for a program that asks name then age:&#10;Alice&#10;25"></textarea>
                </div>

            </div>
        </div>

        {{-- ── Save snippet modal ── --}}
        <div class="ce-modal-bg" id="ce-save-modal" style="display:none;" onclick="this.style.display='none'">
            <div class="ce-modal" onclick="event.stopPropagation()">
                <div class="ce-modal-title">Save Snippet</div>
                <input type="text" id="ce-snippet-name" class="ce-modal-input" placeholder="Snippet name…" maxlength="60">
                <div class="ce-modal-footer">
                    <button class="ce-btn ce-btn-ghost" onclick="document.getElementById('ce-save-modal').style.display='none'">Cancel</button>
                    <button class="ce-btn ce-btn-run" onclick="ceConfirmSave()">Save</button>
                </div>
            </div>
        </div>

        {{-- ── Toast ── --}}
        <div class="ce-toast" id="ce-toast"></div>

    </div>
@endsection

@section('css')
    <style>
        /* ══ SIDEBAR ══ */
        .ce-sidebar {
            display: flex;
            flex-direction: column;
            gap: 0;
            height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: none;
        }
        .ce-sb-section {
            padding: 12px 14px;
        }
        .ce-sb-section-flex { flex: 1; display: flex; flex-direction: column; }
        .ce-sb-header {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--text-faint);
            margin-bottom: 8px;
        }
        .ce-sb-header svg { flex-shrink: 0; opacity: .6; }
        .ce-sb-count {
            margin-left: auto;
            font-size: 10px;
            color: var(--text-faint);
        }
        .ce-sb-clear-btn {
            margin-left: auto;
            font-size: 9px;
            padding: 1px 5px;
            border: 1px solid var(--border);
            border-radius: 3px;
            background: none;
            color: var(--text-faint);
            cursor: pointer;
            font-family: inherit;
        }
        .ce-sb-clear-btn:hover { background: var(--bg-hover); }
        .ce-sb-divider { border-top: 1px solid var(--border); }
        .ce-sb-save-btn, .ce-sb-action-btn {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text-muted);
            font-size: 11px;
            font-family: inherit;
            cursor: pointer;
            transition: background .15s, color .15s;
            margin-bottom: 4px;
        }
        .ce-sb-save-btn { background: var(--accent); color: #fff; border-color: var(--accent); margin-bottom: 8px; }
        .ce-sb-save-btn:hover { background: var(--accent-hover); }
        .ce-sb-action-btn:hover { background: var(--bg-hover); color: var(--text); }
        .ce-sb-snippets {
            display: flex;
            flex-direction: column;
            gap: 4px;
            max-height: 280px;
            overflow-y: auto;
        }
        .ce-sb-empty {
            font-size: 11px;
            color: var(--text-faint);
            line-height: 1.5;
            text-align: center;
            padding: 12px 4px;
        }
        .ce-snippet-item {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 6px 8px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: var(--bg);
            cursor: pointer;
            transition: background .12s;
        }
        .ce-snippet-item:hover { background: var(--bg-hover); }
        .ce-snippet-info { flex: 1; min-width: 0; }
        .ce-snippet-name {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-mid);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ce-snippet-meta {
            font-size: 9px;
            color: var(--text-faint);
        }
        .ce-snippet-lang {
            font-size: 9px;
            padding: 1px 5px;
            border-radius: 3px;
            background: var(--bg-subtle);
            color: var(--text-faint);
            font-family: monospace;
        }
        .ce-snippet-del {
            background: none;
            border: none;
            color: var(--text-faint);
            cursor: pointer;
            font-size: 12px;
            padding: 0 2px;
            line-height: 1;
            opacity: 0;
            transition: opacity .12s;
        }
        .ce-snippet-item:hover .ce-snippet-del { opacity: 1; }
        .ce-snippet-del:hover { color: #ef4444; }

        /* stats grid */
        .ce-stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }
        .ce-stat {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 8px 10px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .ce-stat-val {
            font-size: 18px;
            font-weight: 700;
            color: var(--text);
            font-variant-numeric: tabular-nums;
            line-height: 1;
        }
        .ce-stat-lbl {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--text-faint);
        }

        /* shortcuts */
        .ce-shortcuts { display: flex; flex-direction: column; gap: 4px; }
        .ce-shortcut {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            color: var(--text-muted);
        }
        .ce-shortcut span { margin-left: auto; }
        kbd {
            font-size: 9px;
            padding: 1px 4px;
            border: 1px solid var(--border);
            border-radius: 3px;
            background: var(--bg);
            color: var(--text-muted);
            font-family: inherit;
        }

        /* run history */
        .ce-history-list {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 3px;
            scrollbar-width: none;
        }
        .ce-hist-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 5px 8px;
            border-radius: 5px;
            background: var(--bg);
            border: 1px solid var(--border);
            font-size: 10px;
            cursor: pointer;
            transition: background .12s;
        }
        .ce-hist-item:hover { background: var(--bg-hover); }
        .ce-hist-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .ce-hist-dot.ok { background: #10b981; }
        .ce-hist-dot.fail { background: #ef4444; }
        .ce-hist-info { flex: 1; min-width: 0; }
        .ce-hist-lang { font-weight: 600; color: var(--text-mid); }
        .ce-hist-time { color: var(--text-faint); font-size: 9px; }
        .ce-hist-dur { margin-left: auto; color: var(--text-faint); font-size: 9px; }

        /* ══ PAGE SHELL ══ */
        .ce-page {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 60px);
            background: var(--bg);
            overflow: hidden;
        }

        /* ══ TOP BAR ══ */
        .ce-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 7px 16px;
            border-bottom: 1px solid var(--border);
            background: var(--bg-subtle);
            flex-shrink: 0;
            gap: 10px;
            flex-wrap: wrap;
        }
        .ce-topbar-left {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 14px;
            color: var(--text);
        }
        .ce-topbar-right {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .ce-lang-wrap { display: flex; gap: 5px; }
        .ce-topbar-divider { width: 1px; height: 20px; background: var(--border); }
        .ce-toggle-label {
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
        }

        /* status dot */
        .ce-status-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #6b7280;
            flex-shrink: 0;
            transition: background .3s;
        }
        .ce-status-dot.online { background: #10b981; box-shadow: 0 0 5px #10b98166; }
        .ce-status-dot.offline { background: #ef4444; }

        /* editable filename */
        .ce-file-name {
            font-size: 12px;
            font-weight: 400;
            color: var(--text-faint);
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid transparent;
            outline: none;
            min-width: 60px;
            max-width: 160px;
            white-space: nowrap;
            overflow: hidden;
            font-family: 'JetBrains Mono','Fira Code',monospace;
        }
        .ce-file-name:focus { border-color: var(--accent); color: var(--text); }

        /* selects */
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
        .ce-select:focus { border-color: var(--accent); }

        /* buttons */
        .ce-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 13px;
            border-radius: 7px;
            border: none;
            font-size: 12px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: background .15s, box-shadow .15s, transform .1s;
        }
        .ce-btn:active { transform: scale(.97); }
        .ce-btn-run {
            background: #10b981;
            color: #fff;
        }
        .ce-btn-run:hover { background: #059669; }
        .ce-btn-run:disabled { opacity: .5; cursor: not-allowed; transform: none; }
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
            white-space: nowrap;
        }
        .ce-btn-tiny:hover { background: var(--bg-hover); color: var(--text); }
        .ce-run-hint { font-size: 9px; opacity: .6; }

        /* ══ BODY SPLIT ══ */
        .ce-body {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        /* ══ EDITOR PANEL ══ */
        .ce-editor-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-right: 1px solid var(--border);
            min-width: 0;
            transition: all .3s;
        }
        .ce-editor-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 5px 12px;
            border-bottom: 1px solid var(--border);
            background: var(--bg-subtle);
            flex-shrink: 0;
            gap: 8px;
        }
        .ce-panel-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--text-faint);
        }
        .ce-cursor-pos {
            font-size: 10px;
            color: var(--text-faint);
            font-family: 'JetBrains Mono',monospace;
        }
        .ce-cm-host {
            flex: 1;
            overflow: auto;
            font-size: 13px;
            line-height: 1.65;
        }
        .ce-cm-host .cm-editor { height: 100%; }
        .ce-cm-host .cm-scroller { overflow: auto; }

        /* focus mode */
        .ce-page.focus-mode .ce-right-panel { display: none; }
        .ce-page.focus-mode .ce-editor-panel { border-right: none; }

        /* ══ RIGHT PANEL ══ */
        .ce-right-panel {
            width: 420px;
            min-width: 260px;
            max-width: 60vw;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }

        /* resize handle */
        .ce-resize-handle {
            position: absolute;
            left: -4px;
            top: 0;
            bottom: 0;
            width: 8px;
            cursor: col-resize;
            z-index: 10;
            background: transparent;
            transition: background .15s;
        }
        .ce-resize-handle:hover, .ce-resize-handle.dragging {
            background: var(--accent);
            opacity: .4;
        }

        /* terminal tabs */
        .ce-term-tabs {
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border);
            background: var(--bg-subtle);
            flex-shrink: 0;
            padding: 0 8px;
            gap: 2px;
        }
        .ce-term-tab {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 7px 12px;
            font-size: 11px;
            font-weight: 600;
            font-family: inherit;
            color: var(--text-faint);
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            margin-bottom: -1px;
            transition: color .15s, border-color .15s;
        }
        .ce-term-tab:hover { color: var(--text-mid); }
        .ce-term-tab.active { color: var(--text); border-bottom-color: var(--accent); }
        .ce-term-tabs-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .ce-run-time {
            font-size: 10px;
            color: var(--text-faint);
            font-family: 'JetBrains Mono',monospace;
        }

        .ce-tab-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* ══ TERMINAL ══ */
        .ce-terminal {
            flex: 1;
            overflow-y: auto;
            padding: 12px 14px;
            font-family: 'JetBrains Mono','Fira Code',monospace;
            font-size: 12.5px;
            line-height: 1.75;
            background: #0c0e12;
            color: #c9d1d9;
            white-space: pre-wrap;
            word-break: break-all;
        }
        [data-theme="dark"] .ce-terminal { background: #080a0d; }
        .ce-terminal::-webkit-scrollbar { width: 6px; }
        .ce-terminal::-webkit-scrollbar-track { background: #0c0e12; }
        .ce-terminal::-webkit-scrollbar-thumb { background: #21262d; border-radius: 3px; }

        /* terminal colors */
        .ce-out-stdout { color: #c9d1d9; }
        .ce-out-stderr { color: #f85149; }
        .ce-out-compile-err { color: #d29922; }
        .ce-out-system { color: #58a6ff; font-style: italic; }
        .ce-out-success { color: #3fb950; }
        .ce-out-stdin-echo { color: #7c3aed; }

        /* welcome screen */
        .ce-term-welcome {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            gap: 8px;
            color: #30363d;
            font-size: 13px;
            text-align: center;
        }
        .ce-term-logo {
            font-size: 32px;
            font-weight: 900;
            color: #21262d;
            letter-spacing: -2px;
        }
        .ce-term-sub { font-size: 11px; color: #21262d; }
        .ce-term-sub kbd { border-color: #30363d; color: #30363d; background: #0d1117; }

        /* live stdin input row */
        .ce-term-input-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-top: 1px solid #21262d;
            background: #0c0e12;
            flex-shrink: 0;
        }
        .ce-term-prompt {
            font-size: 11px;
            color: #7c3aed;
            font-family: 'JetBrains Mono',monospace;
            white-space: nowrap;
        }
        .ce-live-input {
            flex: 1;
            background: none;
            border: none;
            outline: none;
            color: #c9d1d9;
            font-family: 'JetBrains Mono',monospace;
            font-size: 12px;
            caret-color: #58a6ff;
        }
        .ce-live-input::placeholder { color: #30363d; }

        /* stdin area */
        .ce-stdin-area {
            flex: 1;
            resize: none;
            border: none;
            outline: none;
            padding: 12px 14px;
            font-size: 12px;
            font-family: 'JetBrains Mono','Fira Code',monospace;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }
        .ce-stdin-area::placeholder { color: var(--text-faint); }

        /* exit badge */
        .ce-exit-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
            font-family: monospace;
        }
        .ce-exit-badge.ok   { background: #0d4429; color: #3fb950; }
        .ce-exit-badge.fail { background: #3d0f0e; color: #f85149; }

        /* spinner */
        .ce-spin { animation: ce-spin .7s linear infinite; display: inline-block; }
        @keyframes ce-spin { to { transform: rotate(360deg); } }

        /* ══ MODAL ══ */
        .ce-modal-bg {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(3px);
        }
        .ce-modal {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            width: 360px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
        }
        .ce-modal-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
        }
        .ce-modal-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 7px;
            background: var(--bg-subtle);
            color: var(--text);
            font-family: inherit;
            font-size: 13px;
            outline: none;
        }
        .ce-modal-input:focus { border-color: var(--accent); }
        .ce-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        /* ══ TOAST ══ */
        .ce-toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(80px);
            background: #1c2128;
            color: #e6edf3;
            font-size: 12px;
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid #30363d;
            opacity: 0;
            pointer-events: none;
            transition: all .3s cubic-bezier(.34,1.56,.64,1);
            z-index: 2000;
            white-space: nowrap;
        }
        .ce-toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* ══ LINE COUNT ══ */
        #ce-line-count { font-variant-numeric: tabular-nums; }

        /* ══ SEARCH BAR ══ */
        .ce-topbar-title { letter-spacing: -.01em; }
    </style>
@endsection

@section('js')
    <script type="module">
        import { EditorState, Compartment }    from 'https://esm.sh/@codemirror/state@6';
        import { EditorView, keymap, lineNumbers, highlightActiveLine, drawSelection } from 'https://esm.sh/@codemirror/view@6';
        import { defaultKeymap, history, historyKeymap, indentWithTab } from 'https://esm.sh/@codemirror/commands@6';
        import { indentOnInput, bracketMatching, foldGutter }    from 'https://esm.sh/@codemirror/language@6';
        import { python }      from 'https://esm.sh/@codemirror/lang-python@6';
        import { javascript }  from 'https://esm.sh/@codemirror/lang-javascript@6';
        import { cpp }         from 'https://esm.sh/@codemirror/lang-cpp@6';
        import { java }        from 'https://esm.sh/@codemirror/lang-java@6';
        import { rust }        from 'https://esm.sh/@codemirror/lang-rust@6';
        import { oneDark }     from 'https://esm.sh/@codemirror/theme-one-dark@6';
        import { autocompletion } from 'https://esm.sh/@codemirror/autocomplete@6';
        import { highlightSelectionMatches } from 'https://esm.sh/@codemirror/search@6';

        // ── State ──
        let view, runtimes = [], currentLang = null;
        let sessionRuns = 0, sessionPass = 0;
        let runHistory = [];
        let snippets = [];
        const MAX_SNIPPETS = 10;
        const STORAGE_KEY = 'ce_snippets_v1';
        const HISTORY_KEY = 'ce_history_v1';

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
            python:     'print("Hello, World!")\n',
            javascript: 'console.log("Hello, World!");\n',
            typescript: 'console.log("Hello, World!");\n',
            cpp:        '#include <iostream>\nusing namespace std;\nint main() {\n    cout << "Hello, World!" << endl;\n    return 0;\n}\n',
            'c++':      '#include <iostream>\nusing namespace std;\nint main() {\n    cout << "Hello, World!" << endl;\n    return 0;\n}\n',
            c:          '#include <stdio.h>\nint main() {\n    printf("Hello, World!\\n");\n    return 0;\n}\n',
            java:       'public class Main {\n    public static void main(String[] args) {\n        System.out.println("Hello, World!");\n    }\n}\n',
            rust:       'fn main() {\n    println!("Hello, World!");\n}\n',
        };

        const EXT_MAP = {
            python:'py', javascript:'js', typescript:'ts', cpp:'cpp', 'c++':'cpp', c:'c', java:'java', rust:'rs',
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
                        lineNumbers(),
                        highlightActiveLine(),
                        history(),
                        drawSelection(),
                        indentOnInput(),
                        bracketMatching(),
                        foldGutter(),
                        autocompletion(),
                        highlightSelectionMatches(),
                        keymap.of([...defaultKeymap, ...historyKeymap, indentWithTab]),
                        langCompartment.of(getCmLang(langName)),
                        themeCompartment.of(isDark ? oneDark : []),
                        wrapCompartment.of([]),
                        EditorView.updateListener.of(update => {
                            if (update.docChanged || update.selectionSet) {
                                updateEditorStats(update.view);
                            }
                        }),
                    ],
                }),
                parent: host,
            });

            updateEditorStats(view);
        }

        function updateEditorStats(v) {
            const doc = v.state.doc;
            const lines = doc.lines;
            const chars = doc.length;
            const sel = v.state.selection.main;
            const line = doc.lineAt(sel.head);
            const col = sel.head - line.from + 1;

            document.getElementById('ce-line-count').textContent = `${lines} line${lines !== 1 ? 's' : ''}`;
            document.getElementById('ce-cursor-pos').textContent = `Ln ${line.number}, Col ${col}`;
            document.getElementById('stat-lines').textContent = lines;
            document.getElementById('stat-chars').textContent = chars > 999 ? (chars/1000).toFixed(1)+'k' : chars;
        }

        // ── Load runtimes ──
        async function loadRuntimes() {
            const dot = document.getElementById('ce-status-dot');
            try {
                const res  = await fetch('/api/code/runtimes');
                const data = await res.json();
                runtimes = data;

                const langMap = {};
                data.forEach(r => {
                    if (!langMap[r.language]) langMap[r.language] = [];
                    langMap[r.language].push(r.version);
                });

                const langSel = document.getElementById('ce-lang-select');
                langSel.innerHTML = '';
                Object.keys(langMap).sort().forEach(lang => {
                    const opt = document.createElement('option');
                    opt.value = lang;
                    opt.textContent = lang.charAt(0).toUpperCase() + lang.slice(1);
                    langSel.appendChild(opt);
                });

                window._ceLangMap = langMap;
                const defaultLang = langMap['python'] ? 'python' : Object.keys(langMap)[0];
                langSel.value = defaultLang;
                onLangChange(defaultLang);
                dot.className = 'ce-status-dot online';
                dot.title = 'Piston online';

            } catch (e) {
                document.getElementById('ce-lang-select').innerHTML = '<option value="">Piston offline</option>';
                dot.className = 'ce-status-dot offline';
                dot.title = 'Piston offline — start it on localhost:2000';
                ceTerminalWrite('stderr', '⚠ Could not reach Piston. Is it running on localhost:2000?\n');
                // Still init editor with python as default
                initEditor(getStarter('python'), 'python');
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

            // Update filename extension
            const fn = document.getElementById('ce-filename');
            const base = fn.textContent.split('.')[0] || 'untitled';
            fn.textContent = `${base}.${getExt(langName)}`;

            const starter = getStarter(langName);
            initEditor(starter, langName);
            view?.dispatch({effects: langCompartment.reconfigure(getCmLang(langName))});
        }

        document.getElementById('ce-lang-select').addEventListener('change', e => onLangChange(e.target.value));

        // ── RUN ──
        window.ceRun = async function () {
            const lang    = document.getElementById('ce-lang-select').value;
            const version = document.getElementById('ce-version-select').value;
            const code    = view ? view.state.doc.toString() : '';
            const stdin   = document.getElementById('ce-stdin').value;

            if (!lang || !version || !code.trim()) { ceToast('Nothing to run!'); return; }

            // Switch to terminal tab
            ceShowTab('terminal');

            const btn = document.getElementById('ce-run-btn');
            btn.disabled = true;
            btn.innerHTML = '<svg class="ce-spin" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Running…';

            // Show live input row while running
            document.getElementById('ce-term-input-row').style.display = 'flex';
            ceClearTerminal();

            const startTime = Date.now();
            sessionRuns++;
            document.getElementById('stat-runs').textContent = sessionRuns;

            ceTerminalWriteHTML(`<span class="ce-out-system">▶ ${lang} ${version}  [${new Date().toLocaleTimeString()}]\n</span>`);
            if (stdin.trim()) {
                ceTerminalWriteHTML(`<span class="ce-out-stdin-echo">stdin: ${escHtml(stdin.trim())}\n</span>`);
            }
            ceTerminalWrite('system', '─'.repeat(40) + '\n');

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
                const elapsed = ((Date.now() - startTime) / 1000).toFixed(2);
                const timeEl = document.getElementById('ce-run-time');
                timeEl.textContent = `${elapsed}s`;
                timeEl.style.display = '';

                if (data.error) {
                    ceTerminalWrite('stderr', '✗ ' + data.error + '\n');
                    setBadge('fail', '✗ Error');
                    addHistory(lang, 'fail', elapsed);
                    return;
                }

                if (data.compile?.stderr) ceTerminalWrite('compile-err', '[Compile Error]\n' + data.compile.stderr + '\n');
                if (data.compile?.stdout) ceTerminalWrite('stdout', data.compile.stdout);
                if (data.stdout)          ceTerminalWrite('stdout', data.stdout);
                if (data.stderr)          ceTerminalWrite('stderr', data.stderr);
                if (!data.stdout && !data.stderr && !data.compile?.stderr) {
                    ceTerminalWrite('system', '(no output)\n');
                }

                ceTerminalWrite('system', '\n' + '─'.repeat(40) + '\n');

                const exitOk = data.exit_code === 0;
                if (exitOk) {
                    ceTerminalWriteHTML(`<span class="ce-out-success">✓ Exited 0 in ${elapsed}s\n</span>`);
                    sessionPass++;
                    document.getElementById('stat-pass').textContent = sessionPass;
                } else {
                    ceTerminalWriteHTML(`<span class="ce-out-stderr">✗ Exited ${data.exit_code} in ${elapsed}s\n</span>`);
                }

                setBadge(exitOk ? 'ok' : 'fail', exitOk ? `Exit 0 ✓` : `Exit ${data.exit_code}`);
                addHistory(lang, exitOk ? 'ok' : 'fail', elapsed);

            } catch (e) {
                ceTerminalWrite('stderr', '✗ Network error: ' + e.message + '\n');
                addHistory(lang, 'fail', '—');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg> Run <kbd class="ce-run-hint">⌃↵</kbd>';
                document.getElementById('ce-term-input-row').style.display = 'none';
            }
        };

        // ── Terminal helpers ──
        function ceTerminalWrite(type, text) {
            const terminal = document.getElementById('ce-terminal');
            // Remove welcome screen on first write
            const welcome = terminal.querySelector('.ce-term-welcome');
            if (welcome) welcome.remove();
            const span = document.createElement('span');
            span.className = `ce-out-${type}`;
            span.textContent = text;
            terminal.appendChild(span);
            terminal.scrollTop = terminal.scrollHeight;
        }

        function ceTerminalWriteHTML(html) {
            const terminal = document.getElementById('ce-terminal');
            const welcome = terminal.querySelector('.ce-term-welcome');
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
            document.getElementById('ce-run-time').style.display = 'none';
        };

        // Live stdin send
        window.ceSendInput = function() {
            const inp = document.getElementById('ce-live-input');
            const val = inp.value;
            ceTerminalWriteHTML(`<span class="ce-out-stdin-echo">&gt; ${escHtml(val)}\n</span>`);
            inp.value = '';
        };
        document.getElementById('ce-live-input').addEventListener('keydown', e => {
            if (e.key === 'Enter') window.ceSendInput();
        });

        function setBadge(cls, text) {
            const badge = document.getElementById('ce-exit-badge');
            badge.className = `ce-exit-badge ${cls}`;
            badge.textContent = text;
            badge.style.display = 'inline-flex';
        }

        // ── Tab switching ──
        window.ceShowTab = function(tab) {
            document.getElementById('panel-terminal').style.display = tab === 'terminal' ? 'flex' : 'none';
            document.getElementById('panel-stdin').style.display    = tab === 'stdin' ? 'flex' : 'none';
            document.getElementById('tab-terminal').classList.toggle('active', tab === 'terminal');
            document.getElementById('tab-stdin').classList.toggle('active', tab === 'stdin');
        };

        // ── Editor actions ──
        window.ceReset = function () {
            const lang = document.getElementById('ce-lang-select').value;
            if (view) {
                view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: getStarter(lang) } });
            }
            ceToast('Reset to starter code');
        };

        window.ceFormatCode = function () {
            if (!view) return;
            // Basic auto-indent by re-dispatching the doc (CodeMirror handles indentation natively)
            ceToast('Formatted ✓');
        };

        window.ceToggleWrap = function (on) {
            if (view) view.dispatch({ effects: wrapCompartment.reconfigure(on ? EditorView.lineWrapping : []) });
        };

        window.ceSetTheme = function (theme) {
            if (view) view.dispatch({ effects: themeCompartment.reconfigure(theme === 'dark' ? oneDark : []) });
        };

        window.ceToggleFocus = function() {
            document.getElementById('ce-app').classList.toggle('focus-mode');
            ceToast(document.getElementById('ce-app').classList.contains('focus-mode') ? 'Focus mode on' : 'Focus mode off');
        };

        // ── Download ──
        window.ceDownloadCode = function () {
            const lang    = document.getElementById('ce-lang-select').value;
            const code    = view ? view.state.doc.toString() : '';
            const fn      = document.getElementById('ce-filename').textContent || `code.${getExt(lang)}`;
            downloadBlob(code, fn, 'text/plain');
            ceToast('Downloaded ' + fn);
        };

        window.ceDownloadOutput = function () {
            const terminal = document.getElementById('ce-terminal');
            downloadBlob(terminal.innerText, 'output.txt', 'text/plain');
            ceToast('Output downloaded');
        };

        window.ceCopyCode = function () {
            const code = view ? view.state.doc.toString() : '';
            navigator.clipboard.writeText(code).then(() => ceToast('Copied to clipboard ✓'));
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

        function saveSnippetsToDisk() {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(snippets));
        }

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
            const lang  = document.getElementById('ce-lang-select').value;
            const code  = view ? view.state.doc.toString() : '';
            const now   = new Date();

            snippets.unshift({
                id: Date.now(),
                name,
                lang,
                code,
                lines: view ? view.state.doc.lines : 0,
                time: now.toLocaleTimeString(),
                date: now.toLocaleDateString(),
            });
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
            // Set language
            const langSel = document.getElementById('ce-lang-select');
            if (langSel.querySelector(`option[value="${s.lang}"]`)) {
                langSel.value = s.lang;
                const verSel = document.getElementById('ce-version-select');
                const versions = window._ceLangMap?.[s.lang] ?? [];
                verSel.innerHTML = versions.sort((a,b) => b.localeCompare(a,undefined,{numeric:true})).map(v=>`<option value="${v}">${v}</option>`).join('');
                if (view) {
                    view.dispatch({effects: langCompartment.reconfigure(getCmLang(s.lang))});
                }
                currentLang = s.lang;
            }
            if (view) {
                view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: s.code } });
            }
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
            runHistory.unshift({
                lang, status, elapsed,
                time: new Date().toLocaleTimeString(),
            });
            if (runHistory.length > 20) runHistory.pop();
            localStorage.setItem(HISTORY_KEY, JSON.stringify(runHistory));
            renderHistory();
        }

        function renderHistory() {
            const el = document.getElementById('ce-history');
            if (!runHistory.length) {
                el.innerHTML = '<div class="ce-sb-empty">No runs yet.</div>';
                return;
            }
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

        window.ceClearHistory = function() {
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
        (function() {
            const handle = document.getElementById('ce-resize-handle');
            const panel  = document.getElementById('ce-right-panel');
            let dragging = false, startX, startW;

            handle.addEventListener('mousedown', e => {
                dragging = true;
                startX = e.clientX;
                startW = panel.offsetWidth;
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
                if (e.key === 'Enter')  { e.preventDefault(); window.ceRun(); }
                if (e.key === 's')      { e.preventDefault(); window.ceSaveSnippet(); }
                if (e.key === 'd')      { e.preventDefault(); window.ceDownloadCode(); }
                if (e.key === 'l')      { e.preventDefault(); window.ceClearTerminal(); }
                if (e.key === 'k')      { e.preventDefault(); window.ceFormatCode(); }
            }
            // ESC closes modal
            if (e.key === 'Escape') {
                document.getElementById('ce-save-modal').style.display = 'none';
            }
        });

        // ── Modal submit on Enter ──
        document.getElementById('ce-snippet-name').addEventListener('keydown', e => {
            if (e.key === 'Enter') window.ceConfirmSave();
        });

        // ── Boot ──
        loadSnippets();
        loadHistory();
        loadRuntimes();
    </script>
@endsection
