@extends('layouts.edditor')

@section('sidebar-elements')
    <div class="ce-sidebar">

        {{-- ══ FILES ══ --}}
        <div class="ce-sb-section">
            <div class="ce-sb-header">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>
                <span>Files</span>
                <span class="ce-sb-count" id="sb-count">0</span>
            </div>

            {{-- Action buttons --}}
            <div class="ce-sb-file-actions">
                <button class="ce-sb-btn ce-sb-btn-primary" onclick="ceFileSave()">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Save
                    <kbd class="ce-sb-kbd">⌃S</kbd>
                </button>
                <div class="ce-sb-btn-row">
                    <button class="ce-sb-btn ce-sb-btn-ghost" onclick="ceFileSaveAs()">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Save As
                    </button>
                    <button class="ce-sb-btn ce-sb-btn-ghost" onclick="ceNewFile()">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        New File
                    </button>
                </div>
            </div>

            {{-- File list --}}
            <div class="ce-sb-files" id="sb-list">
                <div class="ce-sb-empty">No saved files yet.<br>Press <strong>Save</strong> to store your code.</div>
            </div>
        </div>

        <div class="ce-sb-divider"></div>

        {{-- ══ SHORTCUTS ══ --}}
        <div class="ce-sb-section">
            <div class="ce-sb-header">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <path d="M7 8h2m6 0h2M7 12h10M7 16h2m6 0h2"/>
                </svg>
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

    </div>
@endsection

@section('main')
    <div class="ce-page" id="ce-app">

        {{-- ══ TOP BAR ══ --}}
        <div class="ce-topbar">
            <div class="ce-topbar-left">
                <div class="ce-status-dot" id="ce-status-dot" title="PTY server status"></div>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="16 18 22 12 16 6"/>
                    <polyline points="8 6 2 12 8 18"/>
                </svg>
                <span class="ce-topbar-title">Code Editor</span>
                {{-- Editable file name --}}
                <span class="ce-file-name" id="ce-filename" contenteditable="true" spellcheck="false">untitled.py</span>
                {{-- Orange dot = unsaved changes --}}
                <span class="ce-dirty-dot" id="ce-dirty-dot" title="Unsaved changes">●</span>
            </div>
            <div class="ce-topbar-right">
                <div class="ce-lang-wrap">
                    <select id="ce-lang-select" class="ce-select" title="Language">
                        <option value="python">Python</option>
                        <option value="javascript">JavaScript</option>
                        <option value="typescript">TypeScript</option>
                        <option value="c">C</option>
                        <option value="cpp">C++</option>
                        <option value="java">Java</option>
                        <option value="rust">Rust</option>
                        <option value="go">Go</option>
                        <option value="ruby">Ruby</option>
                        <option value="php">PHP</option>
                        <option value="lua">Lua</option>
                        <option value="perl">Perl</option>
                        <option value="kotlin">Kotlin</option>
                        <option value="bash">Bash</option>
                        <option value="swift">Swift</option>
                    </select>
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
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                    Run
                    <kbd class="ce-run-hint">⌃↵</kbd>
                </button>
                <button id="ce-kill-btn" class="ce-btn ce-btn-kill" onclick="ceKill()" style="display:none;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    Kill
                </button>
                <button class="ce-btn ce-btn-ghost" onclick="ceReset()" title="Reset to starter code">Reset</button>
                <button class="ce-btn ce-btn-ghost" onclick="ceDownloadCode()" title="Download (Ctrl+D)">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ══ BODY ══ --}}
        <div class="ce-body">

            {{-- LEFT: CodeMirror editor --}}
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
                <div class="ce-resize-handle" id="ce-resize-handle"></div>

                <div class="ce-term-tabs">
                    <button class="ce-term-tab active" id="tab-terminal" onclick="ceShowTab('terminal')">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="4 17 10 11 4 5"/>
                            <line x1="12" y1="19" x2="20" y2="19"/>
                        </svg>
                        Terminal
                    </button>
                    <div class="ce-term-tabs-right">
                        <span id="ce-exit-badge" class="ce-exit-badge" style="display:none;"></span>
                        <span class="ce-run-time" id="ce-run-time" style="display:none;"></span>
                        <button class="ce-btn-tiny" onclick="ceClearTerminal()">Clear</button>
                    </div>
                </div>

                <div class="ce-tab-content" id="panel-terminal">
                    <div id="ce-terminal" class="ce-terminal">
                        <div class="ce-term-welcome">
                            <div class="ce-term-logo">❯_</div>
                            <div>Live Terminal — <span style="color:#4ade80">Interactive PTY</span></div>
                            <div class="ce-term-sub">Select a language and press <kbd>Ctrl+Enter</kbd> to run</div>
                        </div>
                    </div>
                    <div class="ce-term-input-row" id="ce-term-input-row" style="display:none;">
                        <span class="ce-term-prompt">❯</span>
                        <input type="text" id="ce-live-input" class="ce-live-input"
                               placeholder="Type input and press Enter…"
                               autocomplete="off" spellcheck="false">
                        <button class="ce-btn-tiny" onclick="ceSendInput()">Send ↵</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ SAVE MODAL ══
             Shown for:
               • "Save" when buffer is new (no activeFileId) → prompt for name
               • "Save As" always → prompt for name, always creates new file
        --}}
        <div class="ce-modal-bg" id="ce-save-modal" style="display:none;"
             onclick="this.style.display='none'">
            <div class="ce-modal" onclick="event.stopPropagation()">
                <div class="ce-modal-title" id="ce-modal-title">Save File</div>

                <div class="ce-modal-field">
                    <label class="ce-modal-label" for="ce-file-name-input">File name</label>
                    <input type="text" id="ce-file-name-input"
                           class="ce-modal-input" placeholder="e.g. solution.py" maxlength="80">
                </div>

                {{-- Shown only when the typed name already exists in saved files --}}
                <div class="ce-modal-hint ce-modal-hint-warn" id="ce-modal-overwrite-hint" style="display:none;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    A file with this name already exists — saving will overwrite it.
                </div>

                <div class="ce-modal-footer">
                    <button class="ce-btn ce-btn-ghost"
                            onclick="document.getElementById('ce-save-modal').style.display='none'">
                        Cancel
                    </button>
                    <button class="ce-btn ce-btn-run" onclick="ceConfirmFileSave()">Save</button>
                </div>
            </div>
        </div>

        {{-- ══ TOAST ══ --}}
        <div class="ce-toast" id="ce-toast"></div>

    </div>
@endsection

@section('css')
    <style>

        /* ════════════════════════════════
           SIDEBAR
        ════════════════════════════════ */
        .ce-sidebar {
            display: flex; flex-direction: column;
            height: 100%; overflow-y: auto; overflow-x: hidden;
            scrollbar-width: none;
        }
        .ce-sidebar::-webkit-scrollbar { display: none; }

        .ce-sb-section  { padding: 14px 14px 12px; }
        .ce-sb-divider  { border-top: 1px solid var(--border); }

        .ce-sb-header {
            display: flex; align-items: center; gap: 6px;
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .08em;
            color: var(--text-faint); margin-bottom: 10px;
        }
        .ce-sb-header svg { flex-shrink: 0; opacity: .55; }
        .ce-sb-count {
            margin-left: auto; font-size: 10px; color: var(--text-faint);
            background: var(--bg-hover); padding: 1px 7px; border-radius: 10px;
        }

        /* Save / action buttons */
        .ce-sb-file-actions { display: flex; flex-direction: column; gap: 5px; margin-bottom: 10px; }
        .ce-sb-btn-row      { display: flex; gap: 5px; }
        .ce-sb-btn {
            flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 5px;
            padding: 6px 10px; border-radius: 6px; border: 1px solid var(--border);
            font-size: 11px; font-weight: 600; font-family: inherit;
            cursor: pointer; transition: background .12s, color .12s; white-space: nowrap;
        }
        .ce-sb-btn-primary { background: var(--accent); color: #fff; border-color: var(--accent); }
        .ce-sb-btn-primary:hover { background: var(--accent-hover); }
        .ce-sb-btn-ghost   { background: var(--bg); color: var(--text-muted); }
        .ce-sb-btn-ghost:hover { background: var(--bg-hover); color: var(--text); }
        .ce-sb-kbd {
            margin-left: auto; font-size: 9px; opacity: .75;
            background: rgba(255,255,255,.15); padding: 1px 5px;
            border-radius: 3px; font-family: inherit;
        }

        /* File list */
        .ce-sb-files  { display: flex; flex-direction: column; gap: 3px; }
        .ce-sb-empty  {
            font-size: 11px; color: var(--text-faint);
            line-height: 1.65; text-align: center; padding: 16px 4px;
        }
        .ce-file-item {
            display: flex; align-items: center; gap: 5px;
            padding: 7px 8px; border-radius: 7px;
            border: 1px solid var(--border); background: var(--bg);
            cursor: pointer; transition: background .12s, border-color .12s;
        }
        .ce-file-item:hover  { background: var(--bg-hover); }
        .ce-file-item.active {
            border-color: var(--accent);
            background: color-mix(in srgb, var(--accent) 9%, var(--bg));
        }
        .ce-file-info    { flex: 1; min-width: 0; }
        .ce-file-title   {
            font-size: 11px; font-weight: 600; color: var(--text-mid);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .ce-file-meta  { font-size: 9px; color: var(--text-faint); margin-top: 2px; }
        .ce-file-lang  {
            font-size: 9px; padding: 1px 5px; border-radius: 3px; flex-shrink: 0;
            background: var(--bg-subtle); color: var(--text-faint); font-family: monospace;
        }
        .ce-file-del {
            background: none; border: none; color: var(--text-faint);
            cursor: pointer; font-size: 13px; padding: 0 2px; line-height: 1;
            opacity: 0; transition: opacity .12s; flex-shrink: 0;
        }
        .ce-file-item:hover .ce-file-del { opacity: 1; }
        .ce-file-del:hover { color: #ef4444; }

        /* Shortcuts */
        .ce-shortcuts { display: flex; flex-direction: column; gap: 5px; }
        .ce-shortcut  { display: flex; align-items: center; gap: 4px; font-size: 10px; color: var(--text-muted); }
        .ce-shortcut span { margin-left: auto; }
        kbd {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 1px 5px; border-radius: 4px; font-size: 9px;
            font-family: inherit; border: 1px solid var(--border);
            background: var(--bg-subtle); color: var(--text-muted);
        }

        /* ════════════════════════════════
           PAGE LAYOUT
        ════════════════════════════════ */
        .ce-page { display: flex; flex-direction: column; height: 100%; overflow: hidden; }

        /* ── Top bar ── */
        .ce-topbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 12px; height: 44px; border-bottom: 1px solid var(--border);
            background: var(--bg-subtle); flex-shrink: 0; gap: 8px;
        }
        .ce-topbar-left  { display: flex; align-items: center; gap: 8px; min-width: 0; }
        .ce-topbar-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
        .ce-topbar-divider { width: 1px; height: 18px; background: var(--border); }

        .ce-status-dot {
            width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0;
            background: #30363d; transition: background .3s;
        }
        .ce-status-dot.online  { background: #3fb950; }
        .ce-status-dot.offline { background: #f85149; }

        .ce-file-name {
            font-size: 12px; color: var(--text-muted);
            font-family: 'JetBrains Mono', monospace;
            border: 1px solid transparent; border-radius: 4px;
            padding: 1px 5px; outline: none; min-width: 60px; max-width: 220px;
            white-space: nowrap; overflow: hidden;
        }
        .ce-file-name:focus { border-color: var(--border); background: var(--bg); }

        /* Unsaved indicator */
        .ce-dirty-dot {
            font-size: 13px; color: #f59e0b; line-height: 1;
            margin-left: -6px; flex-shrink: 0;
            transition: opacity .2s;
        }

        .ce-lang-wrap  { display: flex; gap: 4px; align-items: center; }
        .ce-select {
            padding: 4px 8px; border-radius: 6px;
            border: 1px solid var(--border); background: var(--bg);
            color: var(--text); font-size: 11px; font-family: inherit;
            cursor: pointer; outline: none;
        }
        .ce-select-sm  { padding: 4px 6px; }
        .ce-toggle-label { font-size: 11px; color: var(--text-muted); display: flex; align-items: center; gap: 5px; cursor: pointer; }

        .ce-btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 12px; border-radius: 6px; font-size: 11px;
            font-weight: 600; font-family: inherit; cursor: pointer;
            border: 1px solid var(--border); transition: all .15s; white-space: nowrap;
        }
        .ce-btn-run  { background: var(--accent); color: #fff; border-color: var(--accent); }
        .ce-btn-run:hover:not(:disabled) { background: var(--accent-hover); }
        .ce-btn-run:disabled { opacity: .5; cursor: not-allowed; }
        .ce-btn-kill { background: #7f1d1d; color: #fca5a5; border-color: #991b1b; }
        .ce-btn-kill:hover { background: #991b1b; }
        .ce-btn-ghost { background: var(--bg); color: var(--text-muted); }
        .ce-btn-ghost:hover { background: var(--bg-hover); color: var(--text); }
        .ce-btn-tiny {
            padding: 2px 7px; font-size: 10px; font-family: inherit;
            border: 1px solid var(--border); border-radius: 4px;
            background: var(--bg); color: var(--text-faint); cursor: pointer; transition: background .12s;
        }
        .ce-btn-tiny:hover { background: var(--bg-hover); color: var(--text-mid); }
        .ce-run-hint { font-size: 9px; opacity: .6; }

        /* ── Body split ── */
        .ce-body { display: flex; flex: 1; overflow: hidden; }

        /* ── Editor panel ── */
        .ce-editor-panel {
            flex: 1; display: flex; flex-direction: column;
            overflow: hidden; border-right: 1px solid var(--border); min-width: 0;
        }
        .ce-editor-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 5px 12px; border-bottom: 1px solid var(--border);
            background: var(--bg-subtle); flex-shrink: 0; gap: 8px;
        }
        .ce-panel-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: var(--text-faint); }
        .ce-cursor-pos  { font-size: 10px; color: var(--text-faint); font-family: 'JetBrains Mono', monospace; }
        .ce-cm-host { flex: 1; overflow: auto; font-size: 13px; line-height: 1.65; }
        .ce-cm-host .cm-editor   { height: 100%; }
        .ce-cm-host .cm-scroller { overflow: auto; }

        /* focus mode */
        .ce-page.focus-mode .ce-right-panel  { display: none; }
        .ce-page.focus-mode .ce-editor-panel { border-right: none; }

        /* ── Right panel ── */
        .ce-right-panel {
            width: 420px; min-width: 260px; max-width: 60vw;
            display: flex; flex-direction: column; overflow: hidden; position: relative;
        }
        .ce-resize-handle {
            position: absolute; left: -4px; top: 0; bottom: 0;
            width: 8px; cursor: col-resize; z-index: 10;
            background: transparent; transition: background .15s;
        }
        .ce-resize-handle:hover, .ce-resize-handle.dragging { background: var(--accent); opacity: .4; }

        /* ── Terminal tabs ── */
        .ce-term-tabs {
            display: flex; align-items: center;
            border-bottom: 1px solid var(--border);
            background: var(--bg-subtle); flex-shrink: 0; padding: 0 8px; gap: 2px;
        }
        .ce-term-tab {
            display: flex; align-items: center; gap: 5px;
            padding: 7px 12px; font-size: 11px; font-weight: 600;
            font-family: inherit; color: var(--text-faint);
            background: none; border: none; border-bottom: 2px solid transparent;
            cursor: pointer; margin-bottom: -1px; transition: color .15s, border-color .15s;
        }
        .ce-term-tab:hover  { color: var(--text-mid); }
        .ce-term-tab.active { color: var(--text); border-bottom-color: var(--accent); }
        .ce-term-tabs-right { margin-left: auto; display: flex; align-items: center; gap: 6px; }
        .ce-run-time { font-size: 10px; color: var(--text-faint); font-family: 'JetBrains Mono', monospace; }
        .ce-tab-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }

        /* ── Terminal ── */
        .ce-terminal {
            flex: 1; overflow-y: auto; padding: 12px 14px;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 12.5px; line-height: 1.75;
            background: #0c0e12; color: #c9d1d9;
            white-space: pre-wrap; word-break: break-all;
        }
        [data-theme="dark"] .ce-terminal { background: #080a0d; }
        .ce-terminal::-webkit-scrollbar { width: 6px; }
        .ce-terminal::-webkit-scrollbar-track { background: #0c0e12; }
        .ce-terminal::-webkit-scrollbar-thumb { background: #21262d; border-radius: 3px; }

        .ce-out-stdout     { color: #c9d1d9; }
        .ce-out-stderr     { color: #f85149; }
        .ce-out-system     { color: #58a6ff; font-style: italic; }
        .ce-out-success    { color: #3fb950; }
        .ce-out-stdin-echo { color: #7c3aed; }

        .ce-term-welcome {
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            height: 100%; gap: 8px; color: #30363d;
            font-size: 13px; text-align: center;
        }
        .ce-term-logo { font-size: 32px; font-weight: 900; color: #21262d; letter-spacing: -2px; }
        .ce-term-sub  { font-size: 11px; color: #21262d; }
        .ce-term-sub kbd { border-color: #30363d; color: #30363d; background: #0d1117; }

        .ce-term-input-row {
            display: flex; align-items: center; gap: 8px;
            padding: 6px 12px; border-top: 1px solid #21262d;
            background: #0c0e12; flex-shrink: 0;
        }
        .ce-term-prompt { font-size: 14px; color: #4ade80; font-family: 'JetBrains Mono', monospace; white-space: nowrap; }
        .ce-live-input  {
            flex: 1; background: none; border: none; outline: none;
            color: #c9d1d9; font-family: 'JetBrains Mono', monospace;
            font-size: 12px; caret-color: #58a6ff;
        }
        .ce-live-input::placeholder { color: #30363d; }

        .ce-exit-badge { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 20px; font-family: monospace; }
        .ce-exit-badge.ok   { background: #0d4429; color: #3fb950; }
        .ce-exit-badge.fail { background: #3d0f0e; color: #f85149; }

        .ce-spin { animation: ce-spin .7s linear infinite; display: inline-block; }
        @keyframes ce-spin { to { transform: rotate(360deg); } }

        /* ════════════════════════════════
           MODAL
        ════════════════════════════════ */
        .ce-modal-bg {
            position: fixed; inset: 0; background: rgba(0,0,0,.52);
            z-index: 1000; display: flex; align-items: center;
            justify-content: center; backdrop-filter: blur(3px);
        }
        .ce-modal {
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 12px; padding: 24px; width: 400px;
            display: flex; flex-direction: column; gap: 16px;
            box-shadow: 0 24px 64px rgba(0,0,0,.35);
        }
        .ce-modal-title { font-size: 15px; font-weight: 700; color: var(--text); }
        .ce-modal-field { display: flex; flex-direction: column; gap: 6px; }
        .ce-modal-label { font-size: 11px; color: var(--text-faint); font-weight: 600; text-transform: uppercase; letter-spacing: .06em; }
        .ce-modal-input {
            width: 100%; padding: 9px 12px; border: 1px solid var(--border);
            border-radius: 7px; background: var(--bg-subtle); color: var(--text);
            font-family: 'JetBrains Mono', monospace; font-size: 13px; outline: none;
        }
        .ce-modal-input:focus { border-color: var(--accent); }

        .ce-modal-hint {
            display: flex; align-items: flex-start; gap: 8px;
            font-size: 11px; line-height: 1.5;
            border-radius: 7px; padding: 9px 11px;
        }
        .ce-modal-hint-warn {
            color: #f59e0b;
            background: color-mix(in srgb, #f59e0b 10%, var(--bg));
            border: 1px solid color-mix(in srgb, #f59e0b 28%, var(--border));
        }
        .ce-modal-hint svg { flex-shrink: 0; margin-top: 1px; }

        .ce-modal-footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 2px; }

        /* ════════════════════════════════
           TOAST
        ════════════════════════════════ */
        .ce-toast {
            position: fixed; bottom: 24px; left: 50%;
            transform: translateX(-50%) translateY(80px);
            background: #1c2128; color: #e6edf3; font-size: 12px;
            padding: 8px 18px; border-radius: 8px; border: 1px solid #30363d;
            opacity: 0; pointer-events: none;
            transition: all .3s cubic-bezier(.34,1.56,.64,1);
            z-index: 2000; white-space: nowrap;
        }
        .ce-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

        /* Misc */
        #ce-line-count    { font-variant-numeric: tabular-nums; }
        .ce-topbar-title  { letter-spacing: -.01em; }
    </style>
@endsection

@section('js')
    <script>
        window.__PTY_BASE__  = "{{ $ptyUrl }}";
        window.__PTY_TOKEN__ = "{{ $ptyToken }}";
    </script>
@endsection
