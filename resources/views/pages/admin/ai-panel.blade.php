@extends('layouts.edditor')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .aip { font-family: inherit; color: var(--text); background: var(--bg); min-height: 100%; }
        .aip-shell  { display: flex; gap: 20px; }
        .aip-left   { width: 260px; flex-shrink: 0; background: var(--bg-subtle); border: 1px solid var(--border); border-radius: 12px; display: flex; flex-direction: column; overflow: hidden; }
        .aip-right  { flex: 1; display: flex; flex-direction: column; min-width: 0; }

        /* ── Left sidebar ── */
        .aip-brand  { padding: 18px; border-bottom: 1px solid var(--border); }
        .aip-brand h1 { font-size: 1rem; font-weight: 700; color: var(--accent); display: flex; align-items: center; gap: 8px; }
        .aip-brand p  { font-size: .75rem; color: var(--text-muted); margin-top: 4px; }
        .aip-nav    { padding: 10px; display: flex; flex-direction: column; gap: 4px; }
        .aip-nav-btn { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; background: none; border: none; color: var(--text-muted); font-size: .85rem; font-family: inherit; cursor: pointer; transition: all .12s; text-align: left; width: 100%; }
        .aip-nav-btn:hover { background: var(--bg-hover); color: var(--text); }
        .aip-nav-btn.active { background: var(--accent); color: #fff; font-weight: 500; }
        .aip-nav-btn .ico { font-size: 1rem; width: 20px; text-align: center; }
        .aip-divider { height: 1px; background: var(--border); margin: 8px 12px; }
        .aip-stat-row { padding: 6px 14px; display: flex; flex-direction: column; gap: 6px; }
        .aip-stat-item { display: flex; justify-content: space-between; align-items: center; font-size: .78rem; }
        .aip-stat-item span:first-child { color: var(--text-muted); }
        .aip-stat-val { font-weight: 600; padding: 2px 8px; border-radius: 999px; font-size: .7rem; }
        .sv-q { background: #fef3c7; color: #92400e; } .sv-p { background: #e0e7ff; color: #4338ca; }
        .sv-d { background: #dcfce7; color: #166534; } .sv-f { background: #fee2e2; color: #991b1b; }
        .sv-s { background: #f3f4f6; color: #374151; } .sv-c { background: #e5e7eb; color: #4b5563; }
        [data-theme="dark"] .sv-q { background: #451a03; color: #fcd34d; }
        [data-theme="dark"] .sv-p { background: #1e1b4b; color: #a5b4fc; }
        [data-theme="dark"] .sv-d { background: #064e3b; color: #86efac; }
        [data-theme="dark"] .sv-f { background: #450a0a; color: #fca5a5; }
        [data-theme="dark"] .sv-s { background: #1f2937; color: #9ca3af; }
        [data-theme="dark"] .sv-c { background: #374151; color: #d1d5db; }

        /* ── Tabs ── */
        .aip-tabs { display: flex; gap: 0; border-bottom: 1px solid var(--border); margin-bottom: 20px; overflow-x: auto; }
        .aip-tab  { padding: 12px 18px; font-size: .85rem; color: var(--text-muted); cursor: pointer; border-bottom: 2px solid transparent; transition: all .15s; background: none; border-top: none; border-left: none; border-right: none; font-family: inherit; white-space: nowrap; }
        .aip-tab:hover  { color: var(--text); }
        .aip-tab.active { color: var(--accent); border-bottom-color: var(--accent); font-weight: 600; }
        .aip-pane        { display: none; }
        .aip-pane.active { display: block; }

        /* ── Cards ── */
        .aip-card { background: var(--bg); border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 16px; box-shadow: 0 1px 3px var(--shadow); }
        .aip-card-title { font-size: .95rem; font-weight: 600; color: var(--text); margin-bottom: 4px; }
        .aip-card-sub   { font-size: .8rem; color: var(--text-muted); margin-bottom: 16px; }

        /* ── Buttons ── */
        .aip-btn { display: inline-flex; align-items: center; gap: 6px; padding: .5rem 1rem; border-radius: 8px; font-size: .8rem; font-weight: 500; cursor: pointer; border: none; font-family: inherit; transition: all .15s; }
        .aip-btn:disabled { opacity: .5; cursor: not-allowed; }
        .aip-btn:not(:disabled):active { transform: scale(.97); }
        .aip-btn-primary { background: var(--accent); color: #fff; }
        .aip-btn-primary:not(:disabled):hover { background: var(--accent-hover); }
        .aip-btn-success { background: #22c55e; color: #fff; }
        .aip-btn-success:not(:disabled):hover { background: #16a34a; }
        .aip-btn-danger  { background: #ef4444; color: #fff; }
        .aip-btn-danger:not(:disabled):hover  { background: #dc2626; }
        .aip-btn-warn    { background: #f59e0b; color: #fff; }
        .aip-btn-warn:not(:disabled):hover    { background: #d97706; }
        .aip-btn-outline { background: transparent; color: var(--text); border: 1px solid var(--border); }
        .aip-btn-outline:not(:disabled):hover { background: var(--bg-hover); }
        .aip-btn-ghost   { background: transparent; color: var(--text-muted); border: 1px solid var(--border); }
        .aip-btn-ghost:not(:disabled):hover   { background: var(--bg-hover); color: var(--text); }
        .aip-btn-indigo  { background: #6366f1; color: #fff; }
        .aip-btn-indigo:not(:disabled):hover  { background: #4f46e5; }
        .aip-btn-sm { padding: .3rem .65rem; font-size: .75rem; }

        /* ── Form elements ── */
        .aip-input, .aip-select, .aip-textarea { background: var(--bg-subtle); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: inherit; font-size: .85rem; padding: .5rem .75rem; width: 100%; outline: none; transition: all .15s; }
        .aip-input:focus, .aip-select:focus, .aip-textarea:focus { border-color: var(--accent); background: var(--bg); }
        .aip-textarea { resize: vertical; min-height: 80px; }
        .aip-label { font-size: .7rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .05em; }
        .aip-field { margin-bottom: 16px; }
        .aip-row   { display: flex; gap: 12px; flex-wrap: wrap; }
        .aip-row .aip-field { flex: 1; min-width: 120px; }

        /* ── Badges ── */
        .aip-pill { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
        .p-queued     { background:#fef3c7; color:#92400e; }
        .p-processing { background:#e0e7ff; color:#4338ca; animation: p-pulse 1.4s infinite; }
        .p-done       { background:#dcfce7; color:#166534; }
        .p-saved      { background:#f3f4f6; color:#374151; }
        .p-failed     { background:#fee2e2; color:#991b1b; }
        .p-cancelled  { background:#e5e7eb; color:#4b5563; }
        [data-theme="dark"] .p-queued     { background:#451a03; color:#fcd34d; }
        [data-theme="dark"] .p-processing { background:#1e1b4b; color:#a5b4fc; }
        [data-theme="dark"] .p-done       { background:#064e3b; color:#86efac; }
        [data-theme="dark"] .p-saved      { background:#1f2937; color:#9ca3af; }
        [data-theme="dark"] .p-failed     { background:#450a0a; color:#fca5a5; }
        [data-theme="dark"] .p-cancelled  { background:#374151; color:#d1d5db; }
        @keyframes p-pulse { 0%,100%{opacity:1}50%{opacity:.6} }

        /* ── Progress bar ── */
        .aip-prog-wrap { height: 6px; background: var(--border); border-radius: 999px; overflow: hidden; }
        .aip-prog-fill { height: 100%; background: var(--accent); border-radius: 999px; transition: width .5s ease; }
        .aip-prog-fill.is-failed { background: #ef4444; }
        .aip-prog-fill.is-done   { background: #22c55e; }

        /* ── Table ── */
        .aip-table-wrap { overflow-x: auto; border: 1px solid var(--border); border-radius: 10px; }
        .aip-table      { width: 100%; border-collapse: collapse; font-size: .8rem; }
        .aip-table th   { padding: 10px 12px; text-align: left; font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; color: var(--text-muted); border-bottom: 1px solid var(--border); font-weight: 600; white-space: nowrap; background: var(--bg-subtle); }
        .aip-table td   { padding: 10px 12px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        .aip-table tr:last-child td { border-bottom: none; }
        .aip-table tr:hover td { background: var(--bg-hover); }
        .aip-table input[type=checkbox] { cursor: pointer; accent-color: var(--accent); width: 15px; height: 15px; }

        /* ── Log terminal ── */
        .aip-terminal { background: var(--bg-subtle); border: 1px solid var(--border); border-radius: 10px; padding: 14px 16px; font-family: 'JetBrains Mono', 'Fira Code', monospace; font-size: .75rem; line-height: 1.7; color: var(--text); max-height: 280px; overflow-y: auto; white-space: pre-wrap; word-break: break-word; }

        /* ── Connection test grid ── */
        .aip-test-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
        .aip-test-box  { background: var(--bg); border: 1px solid var(--border); border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px var(--shadow); }
        .aip-test-box h4 { font-size: .85rem; color: var(--text); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; font-weight: 600; }

        /* ── Feedback box ── */
        .aip-feedback { margin-top: 12px; padding: .75rem 1rem; border-radius: 8px; font-size: .8rem; white-space: pre-wrap; word-break: break-word; display: none; border: 1px solid transparent; }
        .aip-feedback.ok    { background: #f0fdf4; border-color: #bbf7d0; color: #166534; display: block; }
        .aip-feedback.error { background: #fef2f2; border-color: #fecaca; color: #991b1b; display: block; }
        .aip-feedback.warn  { background: #fffbeb; border-color: #fde68a; color: #92400e; display: block; }
        [data-theme="dark"] .aip-feedback.ok    { background: #064e3b; border-color: #166534; color: #86efac; }
        [data-theme="dark"] .aip-feedback.error { background: #450a0a; border-color: #7f1d1d; color: #fca5a5; }
        [data-theme="dark"] .aip-feedback.warn  { background: #451a03; border-color: #78350f; color: #fcd34d; }

        /* ── Upload dropzone ── */
        .aip-drop { border: 2px dashed var(--border); border-radius: 12px; padding: 40px; text-align: center; cursor: pointer; transition: all .15s; background: var(--bg-subtle); }
        .aip-drop:hover, .aip-drop.drag-over { border-color: var(--accent); background: var(--bg-hover); }
        .aip-drop p { color: var(--text-muted); font-size: .9rem; margin-top: 12px; }
        .aip-drop .big-ico { font-size: 2.5rem; color: var(--text-faint); }

        /* ── JSON preview ── */
        .aip-json { background: var(--bg-subtle); border: 1px solid var(--border); border-radius: 10px; padding: 16px; font-family: 'JetBrains Mono', monospace; font-size: .75rem; color: var(--text); max-height: 400px; overflow: auto; white-space: pre-wrap; word-break: break-word; }

        /* ── Detail panel (slide-in) ── */
        .aip-detail-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 9000; backdrop-filter: blur(2px); }
        .aip-detail-panel { position: fixed; right: 0; top: 0; bottom: 0; width: min(700px, 96vw); background: var(--bg); border-left: 1px solid var(--border); display: flex; flex-direction: column; overflow: hidden; transform: translateX(100%); transition: transform .25s ease; z-index: 9001; box-shadow: -4px 0 24px var(--shadow); }
        .aip-detail-overlay.open { display: block; }
        .aip-detail-overlay.open .aip-detail-panel { transform: translateX(0); }
        .aip-detail-head { padding: 18px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 12px; background: var(--bg-subtle); flex-shrink: 0; }
        .aip-detail-head h2 { flex: 1; font-size: 1rem; font-weight: 600; color: var(--text); }
        .aip-detail-body { flex: 1; overflow-y: auto; padding: 20px; }
        .aip-detail-footer { padding: 16px 20px; border-top: 1px solid var(--border); display: flex; gap: 10px; flex-wrap: wrap; background: var(--bg-subtle); flex-shrink: 0; }

        /* ── Misc ── */
        .aip-section-title { font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); margin: 20px 0 10px; display: flex; align-items: center; justify-content: space-between; }
        .aip-kv-grid { display: grid; grid-template-columns: 120px 1fr; gap: 8px 16px; font-size: .85rem; }
        .aip-kv-grid dt { color: var(--text-muted); }
        .aip-kv-grid dd { color: var(--text); font-weight: 500; word-break: break-all; }
        .aip-filter-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 16px; }
        .aip-search { flex: 1; min-width: 200px; }
        .aip-bulk-bar { display: none; align-items: center; gap: 12px; padding: 10px 16px; background: var(--bg-subtle); border-radius: 10px; margin-bottom: 16px; font-size: .85rem; color: var(--text); border: 1px solid var(--border); }
        .aip-bulk-bar.show { display: flex; }
        .hidden { display: none !important; }

        /* ── Snapshot cards ── */
        .snap-card { border: 1px solid var(--border); border-radius: 10px; margin-bottom: 12px; overflow: hidden; }
        .snap-head { background: var(--bg-subtle); padding: 10px 14px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; border-bottom: 1px solid var(--border); }
        .snap-head-title { font-weight: 600; font-size: .85rem; color: var(--text); }
        .snap-head-meta  { font-size: .75rem; color: var(--text-muted); }
        .snap-body { padding: 12px 14px; }
        .snap-actions { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; align-items: center; }

        .result-row { border: 1px solid var(--border); border-radius: 8px; padding: 10px 12px; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; background: var(--bg); }
        .result-row-info { flex: 1; font-size: .8rem; color: var(--text-muted); }
        .result-row-actions { display: flex; gap: 6px; }

        /* ── Chat tab ── */
        .chat-wrap { display: flex; flex-direction: column; height: 500px; border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
        .chat-messages { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 10px; background: var(--bg-subtle); }
        .chat-bubble { max-width: 80%; padding: 10px 14px; border-radius: 12px; font-size: .85rem; line-height: 1.5; white-space: pre-wrap; word-break: break-word; }
        .chat-bubble.user     { background: var(--accent); color: #fff; align-self: flex-end; border-bottom-right-radius: 4px; }
        .chat-bubble.assistant{ background: var(--bg); border: 1px solid var(--border); color: var(--text); align-self: flex-start; border-bottom-left-radius: 4px; }
        .chat-bubble.thinking { opacity: .6; font-style: italic; }
        .chat-input-row { display: flex; gap: 8px; padding: 12px; border-top: 1px solid var(--border); background: var(--bg); }
        .chat-input-row textarea { flex: 1; border-radius: 8px; resize: none; min-height: 44px; max-height: 120px; }

        /* ── Model badge ── */
        .model-tag { background: var(--bg-subtle); border: 1px solid var(--border); border-radius: 6px; padding: 2px 8px; font-size: .7rem; font-family: monospace; color: var(--text-muted); }
    </style>
@endsection

@section('sidebar-elements')
    <div class="aip-brand">
        <h1>⚡ AI Control Panel</h1>
        <p>PDF → Course pipeline manager</p>
    </div>
    <nav class="aip-nav">
        <button class="aip-nav-btn active" data-nav="upload"><span class="ico">📤</span> Upload PDF</button>
        <button class="aip-nav-btn" data-nav="jobs"><span class="ico">📋</span> All Jobs</button>
        <button class="aip-nav-btn" data-nav="connections"><span class="ico">🔌</span> Connections</button>
    </nav>
    <div class="aip-divider"></div>
    <div class="aip-section-title" style="padding: 0 14px; margin-top:10px">Live Stats</div>
    <div class="aip-stat-row" id="sideStats">
        <div class="aip-stat-item"><span>Queued</span>    <span class="aip-stat-val sv-q" id="ss-q">—</span></div>
        <div class="aip-stat-item"><span>Processing</span><span class="aip-stat-val sv-p" id="ss-p">—</span></div>
        <div class="aip-stat-item"><span>Done</span>      <span class="aip-stat-val sv-d" id="ss-d">—</span></div>
        <div class="aip-stat-item"><span>Failed</span>    <span class="aip-stat-val sv-f" id="ss-f">—</span></div>
        <div class="aip-stat-item"><span>Saved</span>     <span class="aip-stat-val sv-s" id="ss-s">—</span></div>
        <div class="aip-stat-item"><span>Cancelled</span> <span class="aip-stat-val sv-c" id="ss-c">—</span></div>
    </div>
    <div class="aip-divider"></div>
    <div style="padding: 8px 14px">
        <div class="aip-stat-item" style="font-size:.75rem">
            <span style="color:var(--text-muted)">Avg duration</span>
            <span id="ss-avg" style="font-weight:600;color:var(--text)">—</span>
        </div>
    </div>
@endsection

@section('main')
<div class="aip">
<div class="aip-shell">
<div class="aip-right">

    {{-- Tabs --}}
    <div class="aip-tabs">
        <button class="aip-tab active" data-tab="upload">📤 Upload</button>
        <button class="aip-tab" data-tab="jobs">📋 Jobs</button>
        <button class="aip-tab" data-tab="connections">🔌 Connections</button>
    </div>

    {{-- ── UPLOAD TAB ──────────────────────────────────────────────────── --}}
    <div class="aip-pane active" id="pane-upload">
        <div class="aip-card">
            <div class="aip-card-title">📄 New PDF → Course Job</div>
            <p class="aip-card-sub">Drop a PDF. MinerU extracts text + images, Ollama structures it.</p>

            <div id="dropZone" class="aip-drop">
                <div class="big-ico">📄</div>
                <p id="dropLabel">Drop PDF here or <strong>click to browse</strong></p>
                <input type="file" id="pdfFile" accept=".pdf" style="display:none">
            </div>

            <div class="aip-row" style="margin-top:16px">
                <div class="aip-field">
                    <label class="aip-label">Model</label>
                    <select id="uModel" class="aip-select">
                        <option value="">Loading models…</option>
                    </select>
                </div>
                <div class="aip-field">
                    <label class="aip-label">Year</label>
                    <select id="uYear" class="aip-select">
                        <option value="1">Year 1</option>
                        <option value="2">Year 2</option>
                        <option value="3">Year 3</option>
                    </select>
                </div>
                <div class="aip-field">
                    <label class="aip-label">Branch</label>
                    <select id="uBranch" class="aip-select">
                        <option value="none">None</option>
                        <option value="mi">MI</option>
                        <option value="st">ST</option>
                    </select>
                </div>
                <div class="aip-field">
                    <label class="aip-label">Max retries</label>
                    <select id="uMaxAttempts" class="aip-select">
                        <option value="1">1</option><option value="2">2</option>
                        <option value="3" selected>3</option><option value="5">5</option>
                    </select>
                </div>
                <div class="aip-field">
                    <label class="aip-label">Priority (1=high)</label>
                    <select id="uPriority" class="aip-select">
                        <option value="1">1 — Urgent</option><option value="3">3 — High</option>
                        <option value="5" selected>5 — Normal</option><option value="8">8 — Low</option>
                    </select>
                </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:8px;align-items:center">
                <button id="uploadBtn" class="aip-btn aip-btn-primary">✨ Upload & Queue</button>
                <span id="uploadFb" class="aip-feedback" style="margin:0;flex:1"></span>
            </div>
        </div>

        {{-- Live upload progress --}}
        <div id="uploadProgress" class="aip-card hidden">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                <div class="aip-card-title" style="margin:0" id="upTitle">Processing…</div>
                <span class="aip-pill" id="upPill">queued</span>
            </div>
            <div class="aip-prog-wrap" style="margin-bottom:12px">
                <div class="aip-prog-fill" id="upBar" style="width:5%"></div>
            </div>
            <div class="aip-terminal" id="upTerminal"></div>
            <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap">
                <button id="upSaveBtn" class="aip-btn aip-btn-success hidden">💾 Save as course</button>
                <button id="upOpenBtn" class="aip-btn aip-btn-outline hidden">🔍 View snapshots</button>
                <button id="upRetryBtn" class="aip-btn aip-btn-warn hidden">↺ Retry</button>
                <button id="upCancelBtn" class="aip-btn aip-btn-ghost">✕ Cancel / Discard</button>
            </div>
            <div id="upFeedback" class="aip-feedback"></div>
        </div>
    </div>

    {{-- ── JOBS TAB ────────────────────────────────────────────────────── --}}
    <div class="aip-pane" id="pane-jobs">
        <div class="aip-bulk-bar" id="bulkBar">
            <span id="bulkCount">0 selected</span>
            <div style="display:flex;gap:8px;margin-left:auto">
                <button class="aip-btn aip-btn-warn aip-btn-sm" onclick="bulkDo('retry')">↺ Retry all</button>
                <button class="aip-btn aip-btn-ghost aip-btn-sm" onclick="bulkDo('cancel')">✕ Cancel all</button>
                <button class="aip-btn aip-btn-danger aip-btn-sm" onclick="bulkDo('delete')">🗑 Delete all</button>
            </div>
        </div>
        <div class="aip-filter-row">
            <input type="text" id="searchInput" class="aip-input aip-search" placeholder="Search filename…">
            <select id="statusFilter" class="aip-select" style="width:auto;min-width:140px">
                <option value="all">All statuses</option>
                <option value="queued">Queued</option>
                <option value="processing">Processing</option>
                <option value="done">Done</option>
                <option value="failed">Failed</option>
                <option value="saved">Saved</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <button class="aip-btn aip-btn-outline aip-btn-sm" onclick="loadJobs()">↻ Refresh</button>
            <label style="display:flex;align-items:center;gap:6px;font-size:.8rem;color:var(--text-muted);cursor:pointer">
                <input type="checkbox" id="autoRefreshChk" checked style="accent-color:var(--accent)"> Auto-refresh
            </label>
        </div>
        <div class="aip-table-wrap">
            <table class="aip-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th>
                        <th>#</th><th>File</th><th>Status</th><th>Model</th>
                        <th>Snapshots</th><th>Attempt</th><th>Year/Branch</th>
                        <th>By</th><th>Duration</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody id="jobsTbody">
                    <tr><td colspan="11" style="text-align:center;color:var(--text-muted);padding:40px">Loading…</td></tr>
                </tbody>
            </table>
        </div>
        <div id="jobsPager" style="display:flex;gap:8px;margin-top:16px;align-items:center;font-size:.8rem;color:var(--text-muted)"></div>
    </div>

    {{-- ── CONNECTIONS TAB ─────────────────────────────────────────────── --}}
    <div class="aip-pane" id="pane-connections">
        <div class="aip-test-grid" style="margin-bottom:20px">
            <div class="aip-test-box">
                <h4>🤖 Ollama</h4>
                <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:10px">Test selected model on port 11434.</p>
                <div class="aip-field" style="margin-bottom:10px">
                    <label class="aip-label">Model</label>
                    <select id="testModelSel" class="aip-select">
                        <option value="">Loading…</option>
                    </select>
                </div>
                <button class="aip-btn aip-btn-outline" style="width:100%" id="testOllamaBtn">Test Ollama</button>
                <div class="aip-feedback" id="testOllamaFb"></div>
            </div>
            <div class="aip-test-box">
                <h4>📑 MinerU (PDF extractor)</h4>
                <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:14px">Checks Python venv and mineru CLI.</p>
                <button class="aip-btn aip-btn-outline" style="width:100%" id="testMinerUBtn">Test MinerU</button>
                <div class="aip-feedback" id="testMinerUFb"></div>
            </div>
            <div class="aip-test-box">
                <h4>🐍 Python debug</h4>
                <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:14px">Verifies PHP can spawn Python.</p>
                <button class="aip-btn aip-btn-outline" style="width:100%" id="testDebugBtn">Debug Python</button>
                <div class="aip-feedback" id="testDebugFb"></div>
            </div>
        </div>

        {{-- Chat with Ollama --}}
        <div class="aip-card">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap">
                <div class="aip-card-title" style="margin:0">💬 Chat with Ollama</div>
                <div style="display:flex;align-items:center;gap:8px;margin-left:auto">
                    <label class="aip-label" style="margin:0">Model:</label>
                    <select id="chatModelSel" class="aip-select" style="width:auto;min-width:140px">
                        <option value="">Loading…</option>
                    </select>
                    <button class="aip-btn aip-btn-ghost aip-btn-sm" id="chatClearBtn">🗑 Clear</button>
                </div>
            </div>
            <div class="chat-wrap">
                <div class="chat-messages" id="chatMessages">
                    <div class="chat-bubble assistant">Hi! Select a model and start chatting. I'm running on your local Ollama.</div>
                </div>
                <div class="chat-input-row">
                    <textarea id="chatInput" class="aip-textarea" style="min-height:44px;margin:0" placeholder="Type a message… (Enter to send, Shift+Enter for newline)"></textarea>
                    <button id="chatSendBtn" class="aip-btn aip-btn-primary">Send</button>
                </div>
            </div>
        </div>
    </div>

</div><!-- .aip-right -->
</div><!-- .aip-shell -->
</div><!-- .aip -->

{{-- ══ JOB DETAIL SLIDE PANEL ═══════════════════════════════════════════════ --}}
<div class="aip-detail-overlay" id="detailOverlay">
    <div class="aip-detail-panel" id="detailPanel">
        <div class="aip-detail-head">
            <h2 id="dpTitle">Job Detail</h2>
            <span class="aip-pill" id="dpPill"></span>
            <button class="aip-btn aip-btn-ghost aip-btn-sm" id="dpClose">✕ Close</button>
        </div>
        <div class="aip-detail-body" id="dpBody">

            {{-- File info --}}
            <div class="aip-section-title"><span>📁 File Info</span></div>
            <dl class="aip-kv-grid" id="dpMeta"></dl>

            {{-- Edit --}}
            <div class="aip-section-title"><span>✏️ Edit Job</span></div>
            <div class="aip-row">
                <div class="aip-field">
                    <label class="aip-label">Model</label>
                    <select id="dpModel" class="aip-select"><option value="">Loading…</option></select>
                </div>
                <div class="aip-field">
                    <label class="aip-label">Max retries</label>
                    <select id="dpMaxAttempts" class="aip-select">
                        <option value="1">1</option><option value="2">2</option>
                        <option value="3">3</option><option value="5">5</option><option value="10">10</option>
                    </select>
                </div>
                <div class="aip-field">
                    <label class="aip-label">Priority</label>
                    <select id="dpPriority" class="aip-select">
                        <option value="1">1 — Urgent</option><option value="3">3 — High</option>
                        <option value="5">5 — Normal</option><option value="8">8 — Low</option><option value="10">10 — Very low</option>
                    </select>
                </div>
                <div class="aip-field">
                    <label class="aip-label">Year</label>
                    <select id="dpYear" class="aip-select">
                        <option value="1">Year 1</option><option value="2">Year 2</option><option value="3">Year 3</option>
                    </select>
                </div>
                <div class="aip-field">
                    <label class="aip-label">Branch</label>
                    <select id="dpBranch" class="aip-select">
                        <option value="none">None</option><option value="mi">MI</option><option value="st">ST</option>
                    </select>
                </div>
            </div>
            <div class="aip-field">
                <label class="aip-label">Admin note</label>
                <textarea id="dpNote" class="aip-textarea" placeholder="Internal note…"></textarea>
            </div>
            <button class="aip-btn aip-btn-primary aip-btn-sm" id="dpSaveMeta">💾 Save changes</button>
            <div class="aip-feedback" id="dpMetaFb" style="margin-top:10px"></div>

            {{-- Progress --}}
            <div class="aip-section-title"><span>⏳ Progress</span></div>
            <div class="aip-prog-wrap" style="margin-bottom:10px">
                <div class="aip-prog-fill" id="dpProgBar" style="width:0%"></div>
            </div>

            {{-- Live logs --}}
            <div class="aip-section-title">
                <span>📟 Live Logs</span>
                <button class="aip-btn aip-btn-ghost aip-btn-sm" id="dpClearLogs">Clear logs</button>
            </div>
            <div class="aip-terminal" id="dpTerminal">No logs yet.</div>

            {{-- Snapshots --}}
            <div class="aip-section-title">
                <span>📸 Snapshots (MinerU extractions)</span>
                <button class="aip-btn aip-btn-indigo aip-btn-sm" id="dpRetryMdBtn">+ New MinerU extract</button>
            </div>
            <div id="dpSnapshots"><p style="color:var(--text-muted);font-size:.8rem">No snapshots yet.</p></div>

        </div>
        <div class="aip-detail-footer" id="dpFooter">
            <button class="aip-btn aip-btn-warn hidden"    id="dpRetryBtn">↺ Full Retry</button>
            <button class="aip-btn aip-btn-ghost hidden"   id="dpCancelBtn">✕ Cancel job</button>
            <button class="aip-btn aip-btn-danger hidden"  id="dpDeleteBtn">🗑 Delete</button>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
(() => {
'use strict';

const CSRF = document.querySelector('meta[name="csrf-token"]').content;

const routes = {
    models:      "{{ route('admin.ai.models') }}",
    test:        "{{ route('admin.ai.test') }}",
    testMineru:  "{{ route('admin.ai.test-mineru') }}",
    debugPython: "{{ route('admin.ai.debug') }}",
    jsonify:     "{{ route('admin.ai.jsonify') }}",
    jobsList:    "{{ route('admin.ai.jobs.list') }}",
    stats:       "{{ route('admin.ai.stats') }}",
    detail:      "{{ route('admin.ai.jobs.detail',   ['id'=>'__ID__']) }}",
    logs:        "{{ route('admin.ai.logs',           ['id'=>'__ID__']) }}",
    status:      "{{ route('admin.ai.status',         ['id'=>'__ID__']) }}",
    update:      "{{ route('admin.ai.jobs.update',    ['id'=>'__ID__']) }}",
    retry:       "{{ route('admin.ai.jobs.retry',     ['id'=>'__ID__']) }}",
    retryMd:     "{{ route('admin.ai.jobs.retry-md',  ['id'=>'__ID__']) }}",
    cancel:      "{{ route('admin.ai.jobs.cancel',    ['id'=>'__ID__']) }}",
    delete:      "{{ route('admin.ai.jobs.delete',    ['id'=>'__ID__']) }}",
    clearLogs:   "{{ route('admin.ai.jobs.clear-logs',['id'=>'__ID__']) }}",
    snapshots:   "{{ route('admin.ai.jobs.snapshots', ['id'=>'__ID__']) }}",
    recutSnap:   "/admin/ai/jobs/__JID__/snapshots/__SID__/recut",
    bulk:        "{{ route('admin.ai.bulk') }}",
    store:       "{{ route('admin.ai.store') }}",
    chat:        "{{ route('admin.ai.chat') }}",
};

const R   = (name, id)          => routes[name]?.replace('__ID__', id ?? '');
const RS  = (jid, sid)          => routes.recutSnap.replace('__JID__', jid).replace('__SID__', sid);

// ── API helper ─────────────────────────────────────────────────────────────
async function api(method, url, body) {
    const opts = { method, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF } };
    if (body) {
        if (body instanceof FormData) opts.body = body;
        else { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
    }
    const res  = await fetch(url, opts);
    const data = await res.json().catch(() => ({}));
    return { ok: res.ok, status: res.status, data };
}

function showFb(el, msg, type) {
    if (!el) return;
    el.textContent = msg;
    el.className   = 'aip-feedback ' + type;
}

function pillClass(s) {
    return { queued:'p-queued', processing:'p-processing', done:'p-done', failed:'p-failed', saved:'p-saved', cancelled:'p-cancelled' }[s] || 'p-queued';
}

function fmtDur(s) {
    if (!s) return '—';
    if (s < 60) return s + 's';
    return Math.floor(s/60) + 'm ' + (s%60) + 's';
}

function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

const LOG_CLR = { info:'var(--text-muted)', ok:'#16a34a', warn:'#d97706', error:'#dc2626' };
const LOG_ICO = { info:'·', ok:'✓', warn:'⚠', error:'✕' };

function renderLogs(logs, container) {
    container.innerHTML = (logs||[]).length
        ? logs.map(e => `<span style="color:${LOG_CLR[e.level]||LOG_CLR.info}">[${escHtml(e.ts)}] ${LOG_ICO[e.level]||'·'} ${escHtml(e.message)}</span>`).join('\n')
        : '<span style="color:var(--text-faint)">No logs yet.</span>';
    container.scrollTop = container.scrollHeight;
}

// ── Model detection ────────────────────────────────────────────────────────
let availableModels = [];

async function loadModels() {
    const { ok, data } = await api('GET', routes.models);
    availableModels = (ok && data.ok) ? data.models : [];

    const selects = ['uModel', 'testModelSel', 'chatModelSel', 'dpModel'];
    selects.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerHTML = availableModels.length
            ? availableModels.map(m => `<option value="${escHtml(m)}">${escHtml(m)}</option>`).join('')
            : '<option value="phi4">phi4 (fallback)</option>';
    });
}

// ── Nav / Tab switching ────────────────────────────────────────────────────
document.querySelectorAll('.aip-nav-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.aip-nav-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const tab = btn.dataset.nav;
        document.querySelectorAll('.aip-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
        document.querySelectorAll('.aip-pane').forEach(p => p.classList.toggle('active', p.id === 'pane-' + tab));
    });
});

document.querySelectorAll('.aip-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.aip-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        const name = tab.dataset.tab;
        document.querySelectorAll('.aip-pane').forEach(p => p.classList.toggle('active', p.id === 'pane-' + name));
        document.querySelectorAll('.aip-nav-btn').forEach(b => b.classList.toggle('active', b.dataset.nav === name));
    });
});

// ── Stats ──────────────────────────────────────────────────────────────────
async function loadStats() {
    const { ok, data } = await api('GET', routes.stats);
    if (!ok) return;
    document.getElementById('ss-q').textContent = data.queued || 0;
    document.getElementById('ss-p').textContent = data.processing || 0;
    document.getElementById('ss-d').textContent = data.done || 0;
    document.getElementById('ss-f').textContent = data.failed || 0;
    document.getElementById('ss-s').textContent = data.saved || 0;
    document.getElementById('ss-c').textContent = data.cancelled || 0;
    document.getElementById('ss-avg').textContent = data.avg_duration ? Math.round(data.avg_duration) + 's' : '—';
}

// ── Jobs list ──────────────────────────────────────────────────────────────
let jobsPage = 1;

async function loadJobs(page) {
    jobsPage = page || 1;
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('searchInput').value;
    const url    = routes.jobsList + `?page=${jobsPage}&status=${status}&search=${encodeURIComponent(search)}`;
    const { ok, data } = await api('GET', url);
    if (!ok) return;

    const tbody = document.getElementById('jobsTbody');
    if (!data.data?.length) {
        tbody.innerHTML = `<tr><td colspan="11" style="text-align:center;color:var(--text-muted);padding:40px">No jobs found.</td></tr>`;
        return;
    }

    tbody.innerHTML = data.data.map(j => `
        <tr>
            <td><input type="checkbox" class="job-chk" value="${j.id}"></td>
            <td style="font-family:monospace;font-size:.75rem">#${j.id}</td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${escHtml(j.original_filename)}">${escHtml(j.original_filename||'—')}</td>
            <td><span class="aip-pill ${pillClass(j.status)}">${j.status}</span></td>
            <td><span class="model-tag">${escHtml(j.model||'?')}</span></td>
            <td style="text-align:center">${j.snapshot_count || 0}</td>
            <td>${j.attempt}/${j.max_attempts}</td>
            <td><span style="font-size:.75rem">${j.year}/${j.branch}</span></td>
            <td style="font-size:.75rem">${escHtml(j.started_by||'—')}</td>
            <td style="font-size:.75rem">${fmtDur(j.duration_seconds)}</td>
            <td>
                <div style="display:flex;gap:4px;flex-wrap:wrap">
                    <button class="aip-btn aip-btn-outline aip-btn-sm" onclick="openDetail(${j.id})">🔍 Detail</button>
                    ${j.can_retry ? `<button class="aip-btn aip-btn-warn aip-btn-sm" onclick="quickRetry(${j.id})">↺</button>` : ''}
                    <button class="aip-btn aip-btn-danger aip-btn-sm" onclick="quickDelete(${j.id})">🗑</button>
                </div>
            </td>
        </tr>`).join('');

    // Pagination
    const pager = document.getElementById('jobsPager');
    pager.innerHTML = '';
    if (data.last_page > 1) {
        for (let p = 1; p <= data.last_page; p++) {
            const btn = document.createElement('button');
            btn.className = 'aip-btn aip-btn-sm ' + (p === data.current_page ? 'aip-btn-primary' : 'aip-btn-ghost');
            btn.textContent = p;
            btn.onclick = () => loadJobs(p);
            pager.appendChild(btn);
        }
    }

    updateBulkBar();
    document.querySelectorAll('.job-chk').forEach(c => c.addEventListener('change', updateBulkBar));
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.job-chk:checked').length;
    document.getElementById('bulkCount').textContent = checked + ' selected';
    document.getElementById('bulkBar').classList.toggle('show', checked > 0);
    document.getElementById('selectAll').checked = checked > 0 && checked === document.querySelectorAll('.job-chk').length;
}

function toggleSelectAll() {
    const all = document.getElementById('selectAll').checked;
    document.querySelectorAll('.job-chk').forEach(c => c.checked = all);
    updateBulkBar();
}

async function bulkDo(action) {
    const ids = [...document.querySelectorAll('.job-chk:checked')].map(c => parseInt(c.value));
    if (!ids.length) return;
    if (action === 'delete' && !confirm(`Delete ${ids.length} jobs?`)) return;
    await api('POST', routes.bulk, { action, ids });
    loadJobs(jobsPage); loadStats();
}

async function quickRetry(id) {
    await api('POST', R('retry', id));
    loadJobs(jobsPage); loadStats();
}

async function quickDelete(id) {
    if (!confirm('Delete job #' + id + '?')) return;
    await api('DELETE', R('delete', id));
    loadJobs(jobsPage); loadStats();
}

// ── Upload ─────────────────────────────────────────────────────────────────
let upJobId   = null;
let upPollT   = null;

const dropZone  = document.getElementById('dropZone');
const pdfInput  = document.getElementById('pdfFile');
const dropLabel = document.getElementById('dropLabel');

dropZone.addEventListener('click', () => pdfInput.click());
pdfInput.addEventListener('change', () => {
    if (pdfInput.files[0]) dropLabel.textContent = '📄 ' + pdfInput.files[0].name;
});
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', e => {
    e.preventDefault(); dropZone.classList.remove('drag-over');
    const f = e.dataTransfer.files[0];
    if (f && f.type === 'application/pdf') {
        const dt = new DataTransfer(); dt.items.add(f); pdfInput.files = dt.files;
        dropLabel.textContent = '📄 ' + f.name;
    }
});

document.getElementById('uploadBtn').addEventListener('click', async () => {
    const file = pdfInput.files[0];
    if (!file) { alert('Select a PDF first.'); return; }

    const fd = new FormData();
    fd.append('pdf_file',      file);
    fd.append('course_year',   document.getElementById('uYear').value);
    fd.append('course_branch', document.getElementById('uBranch').value);
    fd.append('max_attempts',  document.getElementById('uMaxAttempts').value);
    fd.append('priority',      document.getElementById('uPriority').value);
    fd.append('model',         document.getElementById('uModel').value);

    document.getElementById('uploadBtn').disabled = true;
    showFb(document.getElementById('uploadFb'), '⏳ Uploading…', 'warn');

    const { ok, data } = await api('POST', routes.jsonify, fd);
    document.getElementById('uploadBtn').disabled = false;

    if (!ok) {
        showFb(document.getElementById('uploadFb'), '❌ ' + (data.message || 'Upload failed'), 'error');
        return;
    }

    showFb(document.getElementById('uploadFb'), '✅ Queued as job #' + data.job_id, 'ok');
    upJobId = data.job_id;
    document.getElementById('uploadProgress').classList.remove('hidden');
    document.getElementById('upTitle').textContent = 'Job #' + upJobId + ' — ' + file.name;

    clearInterval(upPollT);
    upPollT = setInterval(pollUpload, 2500);
    pollUpload();
});

async function pollUpload() {
    if (!upJobId) return;
    const { ok, data } = await api('GET', R('logs', upJobId));
    if (!ok) return;

    document.getElementById('upPill').textContent = data.status;
    document.getElementById('upPill').className   = 'aip-pill ' + pillClass(data.status);

    const bar = document.getElementById('upBar');
    bar.style.width = (data.progress || 5) + '%';
    bar.className   = 'aip-prog-fill' + (data.status === 'failed' ? ' is-failed' : data.status === 'done' ? ' is-done' : '');

    renderLogs(data.logs || [], document.getElementById('upTerminal'));

    const isDone   = data.status === 'done' || data.status === 'saved';
    const isFailed = data.status === 'failed';
    document.getElementById('upSaveBtn').classList.toggle('hidden', !isDone);
    document.getElementById('upOpenBtn').classList.toggle('hidden', false);
    document.getElementById('upRetryBtn').classList.toggle('hidden', !isFailed);

    if (isDone || isFailed || data.status === 'cancelled') {
        clearInterval(upPollT);
        loadJobs(); loadStats();
    }
}

document.getElementById('upSaveBtn').addEventListener('click', async () => {
    const { ok, data } = await api('POST', routes.store, { job_id: upJobId });
    if (ok && data.success) { alert('✅ Course saved (ID ' + data.course_id + ')'); clearInterval(upPollT); document.getElementById('uploadProgress').classList.add('hidden'); loadJobs(); loadStats(); }
    else alert('❌ ' + (data.error || 'Save failed'));
});

document.getElementById('upOpenBtn').addEventListener('click', () => { if (upJobId) openDetail(upJobId); });
document.getElementById('upRetryBtn').addEventListener('click', async () => {
    await api('POST', R('retry', upJobId));
    clearInterval(upPollT); upPollT = setInterval(pollUpload, 2500); pollUpload();
});
document.getElementById('upCancelBtn').addEventListener('click', () => {
    clearInterval(upPollT);
    if (upJobId) api('POST', R('cancel', upJobId));
    document.getElementById('uploadProgress').classList.add('hidden');
    upJobId = null;
});

// ── Job detail panel ───────────────────────────────────────────────────────
let dpJobId = null;
let dpPollT = null;

const overlay      = document.getElementById('detailOverlay');
const dpTitle      = document.getElementById('dpTitle');
const dpPill       = document.getElementById('dpPill');
const dpMeta       = document.getElementById('dpMeta');
const dpProgBar    = document.getElementById('dpProgBar');
const dpTerm       = document.getElementById('dpTerminal');
const dpRetryBtn   = document.getElementById('dpRetryBtn');
const dpCancelBtn  = document.getElementById('dpCancelBtn');
const dpDeleteBtn  = document.getElementById('dpDeleteBtn');
const dpSaveMeta   = document.getElementById('dpSaveMeta');
const dpMetaFb     = document.getElementById('dpMetaFb');
const dpSnapshots  = document.getElementById('dpSnapshots');
const dpRetryMdBtn = document.getElementById('dpRetryMdBtn');

async function openDetail(id) {
    dpJobId = id;
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    await refreshDetail();
    clearInterval(dpPollT);
    dpPollT = setInterval(async () => {
        const { data } = await api('GET', R('logs', dpJobId));
        if (['done','failed','saved','cancelled'].includes(data.status)) clearInterval(dpPollT);
        await refreshDetail();
    }, 2500);
}

async function refreshDetail() {
    const [detailRes, logsRes, snapRes] = await Promise.all([
        api('GET', R('detail', dpJobId)),
        api('GET', R('logs',   dpJobId)),
        api('GET', R('snapshots', dpJobId)),
    ]);

    const data = detailRes.data;
    const ld   = logsRes.data;
    const sd   = snapRes.data;

    dpTitle.textContent = `Job #${data.id} — ${data.original_filename || ''}`;
    dpPill.textContent  = data.status;
    dpPill.className    = 'aip-pill ' + pillClass(data.status);

    dpMeta.innerHTML = `
        <dt>Filename</dt>  <dd>${escHtml(data.original_filename||'—')}</dd>
        <dt>File size</dt> <dd>${data.file_size_human||'—'}</dd>
        <dt>Year/Branch</dt><dd>${data.year} / ${data.branch}</dd>
        <dt>Model</dt>     <dd><span class="model-tag">${escHtml(data.model||'—')}</span></dd>
        <dt>Uploaded by</dt><dd>${escHtml(data.started_by||'—')}</dd>
        <dt>Started</dt>   <dd>${data.started_at||'—'}</dd>
        <dt>Finished</dt>  <dd>${data.finished_at||'—'}</dd>
        <dt>Duration</dt>  <dd>${fmtDur(data.duration_seconds)}</dd>
        <dt>Attempt</dt>   <dd>${data.attempt} / ${data.max_attempts}</dd>
        <dt>Snapshots</dt> <dd>${data.snapshot_count}</dd>
        ${data.error_message ? `<dt>Error</dt><dd style="color:#dc2626">${escHtml(data.error_message)}</dd>` : ''}
        ${data.note ? `<dt>Note</dt><dd style="color:#d97706">${escHtml(data.note)}</dd>` : ''}
    `;

    // Sync form fields with detected models
    syncModelSelect('dpModel', data.model);
    document.getElementById('dpMaxAttempts').value = data.max_attempts || 3;
    document.getElementById('dpPriority').value    = data.priority || 5;
    document.getElementById('dpYear').value        = data.year || 1;
    document.getElementById('dpBranch').value      = data.branch || 'none';
    document.getElementById('dpNote').value        = data.note || '';

    dpProgBar.style.width = (ld.progress || 0) + '%';
    dpProgBar.className   = 'aip-prog-fill' + (ld.status==='failed'?' is-failed':ld.status==='done'||ld.status==='saved'?' is-done':'');

    renderLogs(ld.logs || [], dpTerm);

    dpRetryBtn.classList.toggle('hidden',  !data.can_retry);
    dpCancelBtn.classList.toggle('hidden', !data.can_cancel);
    dpDeleteBtn.classList.remove('hidden');

    // Render snapshots
    renderSnapshots(sd?.snapshots || [], data);
}

function syncModelSelect(selectId, currentModel) {
    const el = document.getElementById(selectId);
    if (!el) return;
    if (availableModels.length) {
        el.innerHTML = availableModels.map(m => `<option value="${escHtml(m)}" ${m===currentModel?'selected':''}>${escHtml(m)}</option>`).join('');
    }
}

function renderSnapshots(snapshots, jobData) {
    if (!snapshots.length) {
        dpSnapshots.innerHTML = `<p style="color:var(--text-muted);font-size:.8rem">No snapshots yet. Job is still pending or hasn't run.</p>`;
        return;
    }

    dpSnapshots.innerHTML = snapshots.map(snap => {
        const results = snap.results || [];
        const resultsHtml = results.length
            ? results.map(r => `
                <div class="result-row">
                    <div class="result-row-info">
                        <strong>Cut #${r.index}</strong>
                        <span class="model-tag" style="margin-left:6px">${escHtml(r.model)}</span>
                        <span class="aip-pill ${r.status==='done'?'p-done':'p-failed'}" style="margin-left:6px">${r.status}</span>
                        <span style="margin-left:6px;color:var(--text-faint)">${fmtDur(r.duration_seconds)}</span>
                        ${r.error ? `<div style="color:#dc2626;font-size:.75rem;margin-top:4px">⚠ ${escHtml(r.error)}</div>` : ''}
                    </div>
                    <div class="result-row-actions">
                        ${r.has_result && r.status==='done' ? `
                            <button class="aip-btn aip-btn-success aip-btn-sm"
                                onclick="saveResult(${jobData.id}, ${snap.id}, ${r.index})">
                                💾 Save as course
                            </button>` : ''}
                        <button class="aip-btn aip-btn-warn aip-btn-sm"
                            onclick="recutSnapshot(${jobData.id}, ${snap.id})">
                            ↺ Retry cut
                        </button>
                    </div>
                </div>`).join('')
            : `<p style="color:var(--text-muted);font-size:.75rem;margin:6px 0">No cuts yet.</p>`;

        const imgHtml = snap.image_count > 0
            ? `<span style="font-size:.75rem;color:var(--text-muted);margin-left:8px">🖼 ${snap.image_count} image(s)</span>`
            : '';

        return `
        <div class="snap-card">
            <div class="snap-head">
                <span class="snap-head-title">📄 MD #${snap.md_index}</span>
                <span class="aip-pill ${snap.md_status==='done'?'p-done':'p-failed'}">${snap.md_status}</span>
                ${imgHtml}
                <span class="snap-head-meta" style="margin-left:auto">${snap.markdown_length.toLocaleString()} chars</span>
                ${snap.md_created_at ? `<span class="snap-head-meta">${snap.md_created_at}</span>` : ''}
            </div>
            <div class="snap-body">
                <div class="snap-actions">
                    <strong style="font-size:.8rem;color:var(--text-muted)">Cuts:</strong>
                    <button class="aip-btn aip-btn-indigo aip-btn-sm"
                        onclick="recutSnapshot(${jobData.id}, ${snap.id})">
                        + New cut
                    </button>
                    ${snap.image_count > 0 ? `<span style="font-size:.75rem;color:var(--text-muted)">(images included in prompt)</span>` : ''}
                </div>
                ${resultsHtml}
                ${snap.md_status==='failed' ? `
                    <div class="aip-feedback error" style="margin-top:8px">
                        ⚠ MinerU failed: ${escHtml(snap.md_error||'unknown error')}
                    </div>` : ''}
            </div>
        </div>`;
    }).join('');
}

async function saveResult(jobId, snapshotId, resultIndex) {
    const { ok, data } = await api('POST', routes.store, {
        job_id:       jobId,
        snapshot_id:  snapshotId,
        result_index: resultIndex,
    });
    if (ok && data.success) {
        alert('✅ Course saved (ID ' + data.course_id + ')');
        closeDetail(); loadJobs(); loadStats();
    } else {
        alert('❌ ' + (data.error || 'Save failed'));
    }
}

async function recutSnapshot(jobId, snapshotId) {
    const model = document.getElementById('dpModel')?.value || availableModels[0] || 'phi4';
    const { ok, data } = await api('POST', RS(jobId, snapshotId), { model });
    if (!ok) { alert('❌ ' + (data.error || 'Recut failed')); return; }
    showFb(dpMetaFb, '✅ ' + (data.message || 'Recut queued.'), 'ok');
    clearInterval(dpPollT);
    dpPollT = setInterval(() => refreshDetail(), 2500);
    refreshDetail();
}

// Detail panel actions
dpSaveMeta.addEventListener('click', async () => {
    dpSaveMeta.disabled = true;
    const body = {
        max_attempts: parseInt(document.getElementById('dpMaxAttempts').value),
        priority:     parseInt(document.getElementById('dpPriority').value),
        year:         document.getElementById('dpYear').value,
        branch:       document.getElementById('dpBranch').value,
        note:         document.getElementById('dpNote').value,
        model:        document.getElementById('dpModel').value,
    };
    const { ok, data } = await api('PATCH', R('update', dpJobId), body);
    dpSaveMeta.disabled = false;
    showFb(dpMetaFb, ok ? '✅ Saved.' : '❌ ' + (data.error||'Failed'), ok ? 'ok' : 'error');
});

dpRetryBtn.addEventListener('click', async () => {
    const { ok, data } = await api('POST', R('retry', dpJobId));
    if (!ok) { alert('❌ ' + (data.error||'Retry failed')); return; }
    showFb(dpMetaFb, '✅ Full retry queued.', 'ok');
    clearInterval(dpPollT);
    dpPollT = setInterval(() => refreshDetail(), 2500);
    refreshDetail();
});

dpRetryMdBtn.addEventListener('click', async () => {
    const { ok, data } = await api('POST', R('retryMd', dpJobId));
    if (!ok) { alert('❌ ' + (data.error||'Failed')); return; }
    showFb(dpMetaFb, '✅ ' + (data.message||'MinerU re-extract queued.'), 'ok');
    clearInterval(dpPollT);
    dpPollT = setInterval(() => refreshDetail(), 2500);
    refreshDetail();
});

dpCancelBtn.addEventListener('click', async () => {
    await api('POST', R('cancel', dpJobId));
    refreshDetail();
});

dpDeleteBtn.addEventListener('click', async () => {
    if (!confirm('Delete job #' + dpJobId + '?')) return;
    await api('DELETE', R('delete', dpJobId));
    closeDetail();
});

document.getElementById('dpClearLogs').addEventListener('click', async () => {
    await api('DELETE', R('clearLogs', dpJobId));
    dpTerm.textContent = 'Logs cleared.';
});

function closeDetail() {
    clearInterval(dpPollT);
    overlay.classList.remove('open');
    document.body.style.overflow = '';
    dpJobId = null;
    loadJobs(); loadStats();
}

document.getElementById('dpClose').addEventListener('click', closeDetail);
overlay.addEventListener('click', e => { if (e.target === overlay) closeDetail(); });

// ── Connection tests ───────────────────────────────────────────────────────
document.getElementById('testOllamaBtn').addEventListener('click', async function() {
    const model = document.getElementById('testModelSel').value;
    const fb    = document.getElementById('testOllamaFb');
    this.disabled = true; this.textContent = 'Testing…';
    const { ok, data } = await api('POST', routes.test, { model });
    showFb(fb, ok && data.ok ? '✅ ' + data.message : '❌ ' + (data.error||'Not reachable'), ok&&data.ok?'ok':'error');
    this.disabled = false; this.textContent = 'Test Ollama';
});

document.getElementById('testMinerUBtn').addEventListener('click', async function() {
    const fb = document.getElementById('testMinerUFb');
    this.disabled = true; this.textContent = 'Testing…';
    showFb(fb, '⏳ Checking…', 'warn');
    const { ok, data } = await api('POST', routes.testMineru, {});
    showFb(fb, ok && data.ok
        ? `✅ ${data.message}\nVersion: ${data.version}\nCLI: ${data.cli}`
        : `❌ ${data.error} [step: ${data.step||'?'}]${data.detail?'\n→'+data.detail:''}`,
        ok&&data.ok?'ok':'error');
    this.disabled = false; this.textContent = 'Test MinerU';
});

document.getElementById('testDebugBtn').addEventListener('click', async function() {
    const fb = document.getElementById('testDebugFb');
    this.disabled = true; this.textContent = 'Debugging…';
    const { ok, data } = await api('GET', routes.debugPython);
    showFb(fb, `Python exists: ${data.python_exists}\nExit code: ${data.exit_code}\nStdout: ${data.stdout||'(empty)'}\nStderr: ${data.stderr||'(none)'}`, data.exit_code===0?'ok':'error');
    this.disabled = false; this.textContent = 'Debug Python';
});

// ── Chat ───────────────────────────────────────────────────────────────────
let chatHistory = [];

const chatMessages = document.getElementById('chatMessages');
const chatInput    = document.getElementById('chatInput');
const chatSendBtn  = document.getElementById('chatSendBtn');

function appendBubble(role, content, thinking) {
    const div = document.createElement('div');
    div.className = `chat-bubble ${role}${thinking?' thinking':''}`;
    div.textContent = content;
    chatMessages.appendChild(div);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    return div;
}

async function sendChat() {
    const text  = chatInput.value.trim();
    const model = document.getElementById('chatModelSel').value;
    if (!text || !model) return;

    chatHistory.push({ role: 'user', content: text });
    appendBubble('user', text);
    chatInput.value = '';
    chatInput.style.height = 'auto';

    chatSendBtn.disabled = true;
    const thinkBubble = appendBubble('assistant', '…thinking…', true);

    const { ok, data } = await api('POST', routes.chat, { model, messages: chatHistory });
    thinkBubble.remove();
    chatSendBtn.disabled = false;

    if (ok && data.ok) {
        chatHistory.push({ role: 'assistant', content: data.content });
        appendBubble('assistant', data.content);
    } else {
        appendBubble('assistant', '❌ ' + (data.error || 'Error'));
    }
}

chatSendBtn.addEventListener('click', sendChat);
chatInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendChat(); }
});
chatInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

document.getElementById('chatClearBtn').addEventListener('click', () => {
    chatHistory = [];
    chatMessages.innerHTML = '<div class="chat-bubble assistant">Chat cleared. Start a new conversation.</div>';
});

// ── Filters ────────────────────────────────────────────────────────────────
document.getElementById('statusFilter').addEventListener('change', () => loadJobs(1));
document.getElementById('searchInput').addEventListener('input',   () => loadJobs(1));

// ── Auto-refresh ───────────────────────────────────────────────────────────
let statsT = null, jobsT = null;

function startAutoRefresh() {
    clearInterval(statsT); clearInterval(jobsT);
    statsT = setInterval(loadStats,   5000);
    jobsT  = setInterval(() => { if (document.getElementById('autoRefreshChk').checked) loadJobs(jobsPage); }, 8000);
}

document.getElementById('autoRefreshChk').addEventListener('change', function() {
    if (this.checked) startAutoRefresh();
    else { clearInterval(statsT); clearInterval(jobsT); }
});

// ── Expose to window (used by onclick= attributes in dynamic HTML) ─────────
window.openDetail     = openDetail;
window.quickRetry     = quickRetry;
window.quickDelete    = quickDelete;
window.bulkDo         = bulkDo;
window.toggleSelectAll= toggleSelectAll;
window.recutSnapshot  = recutSnapshot;
window.saveResult     = saveResult;

// ── Init ───────────────────────────────────────────────────────────────────
(async () => {
    await loadModels();
    loadStats();
    loadJobs();
    startAutoRefresh();
})();

})();
</script>
@endsection
