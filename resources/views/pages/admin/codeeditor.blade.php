@extends('layouts.edditor')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin-layout.css') }}">
    <style>
        .ce-wrap {
            display: flex;
            flex-direction: column;
            height: 100%;
            gap: 0;
        }

        /* ── Toolbar ── */
        .ce-toolbar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-bottom: 1px solid var(--border);
            background: var(--bg-subtle);
            flex-wrap: wrap;
            flex-shrink: 0;
        }

        .ce-toolbar-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin-right: 4px;
            white-space: nowrap;
        }

        .ce-select {
            background: var(--bg);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 6px;
            padding: 5px 9px;
            font-size: 12px;
            font-family: inherit;
            cursor: pointer;
            outline: none;
            transition: border-color .15s;
        }
        .ce-select:focus { border-color: var(--accent); }

        .ce-title-input {
            flex: 1;
            min-width: 120px;
            max-width: 220px;
            background: var(--bg);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 6px;
            padding: 5px 9px;
            font-size: 12px;
            font-family: inherit;
            outline: none;
            transition: border-color .15s;
        }
        .ce-title-input:focus { border-color: var(--accent); }

        .ce-btn {
            border: none;
            border-radius: 6px;
            padding: 6px 13px;
            font-size: 12px;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            transition: filter .15s;
            white-space: nowrap;
        }
        .ce-btn:hover    { filter: brightness(.93); }
        .ce-btn:disabled { opacity: .5; cursor: not-allowed; }

        .ce-btn-run  { background: var(--accent); color: #fff; }
        .ce-btn-save { background: var(--bg); color: var(--text-muted); border: 1px solid var(--border); }
        .ce-btn-hist { background: var(--bg); color: var(--text-muted); border: 1px solid var(--border); }
        .ce-btn-clear{ background: var(--bg); color: var(--text-muted); border: 1px solid var(--border); }

        /* ── Body split ── */
        .ce-body {
            display: flex;
            flex: 1;
            overflow: hidden;
            min-height: 0;
        }

        /* ── Editor pane ── */
        .ce-editor-pane {
            flex: 1;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border);
            min-width: 0;
        }

        .ce-pane-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: var(--bg-subtle);
            border-bottom: 1px solid var(--border);
            font-size: 11px;
            color: var(--text-faint);
            flex-shrink: 0;
        }

        .ce-lang-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--accent);
            display: inline-block;
        }

        #ce-textarea {
            flex: 1;
            resize: none;
            background: var(--bg);
            color: var(--text);
            border: none;
            outline: none;
            padding: 14px 16px;
            font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
            font-size: 13px;
            line-height: 1.7;
            tab-size: 4;
            white-space: pre;
            overflow-wrap: normal;
            overflow-x: auto;
        }

        /* ── Terminal pane ── */
        .ce-terminal-pane {
            width: 42%;
            min-width: 260px;
            display: flex;
            flex-direction: column;
            background: #0d1117;
        }

        .ce-terminal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 12px;
            background: #161b22;
            border-bottom: 1px solid #30363d;
            font-size: 11px;
            color: #8b949e;
            flex-shrink: 0;
        }

        #ce-terminal {
            flex: 1;
            padding: 8px;
            overflow: hidden;
        }

        #ce-terminal .xterm {
            height: 100%;
        }

        .ce-stdin-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px 8px;
            border-top: 1px solid #30363d;
            background: #161b22;
            flex-shrink: 0;
        }

        .ce-prompt {
            color: #22c55e;
            font-weight: bold;
            font-size: 14px;
            font-family: monospace;
        }

        #ce-stdin-input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: #79c0ff;
            font-family: 'JetBrains Mono', 'Consolas', monospace;
            font-size: 12px;
        }

        .ce-stdin-send {
            background: #21262d;
            border: 1px solid #30363d;
            border-radius: 4px;
            color: #8b949e;
            cursor: pointer;
            padding: 3px 9px;
            font-size: 12px;
            font-family: inherit;
        }
        .ce-stdin-send:hover { background: #30363d; }

        .ce-status-bar {
            padding: 4px 14px;
            background: #161b22;
            border-top: 1px solid #30363d;
            font-size: 11px;
            color: #8b949e;
            min-height: 22px;
            flex-shrink: 0;
        }

        /* ── History panel ── */
        .ce-history-panel {
            position: fixed;
            top: 60px;
            right: -380px;
            width: 360px;
            height: calc(100vh - 60px);
            background: var(--bg);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            transition: right .25s ease;
            z-index: 500;
            box-shadow: -4px 0 16px var(--shadow);
        }
        .ce-history-panel.open { right: 0; }

        .ce-history-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            flex-shrink: 0;
        }

        .ce-history-close {
            background: none;
            border: none;
            color: var(--text-faint);
            font-size: 16px;
            cursor: pointer;
            line-height: 1;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .ce-history-close:hover { background: var(--bg-hover); color: var(--text); }

        .ce-history-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
        }

        .ce-history-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 9px 10px;
            border-radius: 6px;
            cursor: pointer;
            border: 1px solid transparent;
            margin-bottom: 3px;
            transition: background .1s;
        }
        .ce-history-item:hover {
            background: var(--bg-hover);
            border-color: var(--border);
        }

        .ce-history-info { flex: 1; min-width: 0; }

        .ce-history-title {
            font-size: 12px;
            font-weight: 500;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ce-history-meta {
            font-size: 10px;
            color: var(--text-faint);
            margin-top: 2px;
        }

        .ce-history-del {
            background: none;
            border: none;
            color: var(--text-faint);
            font-size: 13px;
            cursor: pointer;
            padding: 2px 5px;
            border-radius: 4px;
            line-height: 1;
            flex-shrink: 0;
        }
        .ce-history-del:hover { color: #ef4444; background: var(--bg-hover); }

        .ce-history-empty {
            text-align: center;
            padding: 30px 16px;
            color: var(--text-faint);
            font-size: 12px;
        }

        .ce-spinner {
            display: inline-block;
            animation: cespin .7s linear infinite;
        }
        @keyframes cespin { to { transform: rotate(360deg); } }
    </style>
@endsection

@section('main')
    <div class="ce-wrap">

        <div class="ce-toolbar">
            <span class="ce-toolbar-title">⌨ Code Editor</span>

            <select id="ce-lang-select" class="ce-select" style="min-width:140px;" onchange="ceLangChange(this.value)">
                <option value="python">Python</option>
                <option value="javascript">JavaScript</option>
                <option value="c">C</option>
                <option value="cpp">C++</option>
                <option value="bash">Bash</option>
            </select>

            <input id="ce-title-input" class="ce-title-input" type="text" placeholder="Session title…" value="Untitled">

            <button class="ce-btn ce-btn-run"  id="ce-run-btn"  onclick="ceRun()">▶ Run</button>
            <button class="ce-btn ce-btn-save" onclick="ceSave()">Save</button>
            <button class="ce-btn ce-btn-hist" onclick="ceToggleHistory()">History</button>
            <button class="ce-btn ce-btn-clear" onclick="ceClearOutput()">Clear</button>
        </div>

        <div class="ce-body">

            <div class="ce-editor-pane">
                <div class="ce-pane-header">
                    <span class="ce-lang-dot"></span>
                    <span id="ce-lang-label">python</span>
                    <span style="margin-left:auto;">Ctrl+Enter = Run · Tab = indent</span>
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
                <span style="display:flex;align-items:center;gap:6px;">
                    <span style="width:7px;height:7px;border-radius:50%;background:#22c55e;display:inline-block;"></span>
                    <span>Terminal</span>
                </span>
                    <span id="ce-status-inline"></span>
                </div>

                <div id="ce-terminal"></div>

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


