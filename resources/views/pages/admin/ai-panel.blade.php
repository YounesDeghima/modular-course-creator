@extends('layouts.edditor')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* ── AI Panel Theme Integration ───────────────────────────────────────── */
        .aip {
            /* Inherit theme variables */
            font-family: inherit;
            color: var(--text);
            background: var(--bg);
            min-height: 100%;
        }

        /* ── Layout ───────────────────────────────────────────────────────── */
        .aip-shell  { display: flex; gap: 20px; }
        .aip-left   { width: 260px; flex-shrink: 0; background: var(--bg-subtle); border: 1px solid var(--border); border-radius: 12px; display: flex; flex-direction: column; overflow: hidden; }
        .aip-right  { flex: 1; display: flex; flex-direction: column; }

        /* ── Left sidebar ─────────────────────────────────────────────────── */
        .aip-brand  { padding: 18px; border-bottom: 1px solid var(--border); }
        .aip-brand h1 { font-size: 1rem; font-weight: 700; color: var(--accent); display: flex; align-items: center; gap: 8px; }
        .aip-brand p  { font-size: .75rem; color: var(--text-muted); margin-top: 4px; }

        .aip-nav    { padding: 10px; display: flex; flex-direction: column; gap: 4px; }
        .aip-nav-btn {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 8px;
            background: none; border: none; color: var(--text-muted);
            font-size: .85rem; font-family: inherit; cursor: pointer;
            transition: all .12s; text-align: left; width: 100%;
        }
        .aip-nav-btn:hover { background: var(--bg-hover); color: var(--text); }
        .aip-nav-btn.active { background: var(--accent); color: #fff; font-weight: 500; }
        .aip-nav-btn .ico { font-size: 1rem; width: 20px; text-align: center; }

        .aip-divider { height: 1px; background: var(--border); margin: 8px 12px; }

        .aip-stat-row { padding: 6px 14px; display: flex; flex-direction: column; gap: 6px; }
        .aip-stat-item { display: flex; justify-content: space-between; align-items: center; font-size: .78rem; }
        .aip-stat-item span:first-child { color: var(--text-muted); }
        .aip-stat-val { font-weight: 600; padding: 2px 8px; border-radius: 999px; font-size: .7rem; }
        .sv-q { background: #fef3c7; color: #92400e; }
        .sv-p { background: #e0e7ff; color: #4338ca; }
        .sv-d { background: #dcfce7; color: #166534; }
        .sv-f { background: #fee2e2; color: #991b1b; }
        .sv-s { background: #f3f4f6; color: #374151; }
        .sv-c { background: #e5e7eb; color: #4b5563; }

        [data-theme="dark"] .sv-q { background: #451a03; color: #fcd34d; }
        [data-theme="dark"] .sv-p { background: #1e1b4b; color: #a5b4fc; }
        [data-theme="dark"] .sv-d { background: #064e3b; color: #86efac; }
        [data-theme="dark"] .sv-f { background: #450a0a; color: #fca5a5; }
        [data-theme="dark"] .sv-s { background: #1f2937; color: #9ca3af; }
        [data-theme="dark"] .sv-c { background: #374151; color: #d1d5db; }

        /* ── Tabs ─────────────────────────────────────────────────────────── */
        .aip-tabs { display: flex; gap: 0; border-bottom: 1px solid var(--border); margin-bottom: 20px; }
        .aip-tab  { padding: 12px 18px; font-size: .85rem; color: var(--text-muted); cursor: pointer; border-bottom: 2px solid transparent; transition: all .15s; background: none; border-top: none; border-left: none; border-right: none; font-family: inherit; }
        .aip-tab:hover   { color: var(--text); }
        .aip-tab.active  { color: var(--accent); border-bottom-color: var(--accent); font-weight: 600; }

        /* ── Tab panes ────────────────────────────────────────────────────── */
        .aip-pane        { display: none; }
        .aip-pane.active { display: block; }

        /* ── Cards ────────────────────────────────────────────────────────── */
        .aip-card {
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 12px; padding: 20px; margin-bottom: 16px;
            box-shadow: 0 1px 3px var(--shadow);
        }
        .aip-card-title { font-size: .95rem; font-weight: 600; color: var(--text); margin-bottom: 4px; }
        .aip-card-sub   { font-size: .8rem; color: var(--text-muted); margin-bottom: 16px; }

        /* ── Buttons ──────────────────────────────────────────────────────── */
        .aip-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: .5rem 1rem; border-radius: 8px; font-size: .8rem;
            font-weight: 500; cursor: pointer; border: none; font-family: inherit;
            transition: all .15s;
        }
        .aip-btn:disabled { opacity: .5; cursor: not-allowed; }
        .aip-btn:not(:disabled):active { transform: scale(.97); }
        .aip-btn-primary  { background: var(--accent); color: #fff; }
        .aip-btn-primary:hover { background: var(--accent-hover); }
        .aip-btn-success  { background: #22c55e; color: #fff; }
        .aip-btn-success:hover { background: #16a34a; }
        .aip-btn-danger   { background: #ef4444; color: #fff; }
        .aip-btn-danger:hover { background: #dc2626; }
        .aip-btn-warn     { background: #f59e0b; color: #fff; }
        .aip-btn-warn:hover { background: #d97706; }
        .aip-btn-outline  { background: transparent; color: var(--text); border: 1px solid var(--border); }
        .aip-btn-outline:hover { background: var(--bg-hover); border-color: var(--text-muted); }
        .aip-btn-ghost    { background: transparent; color: var(--text-muted); border: 1px solid var(--border); }
        .aip-btn-ghost:hover { background: var(--bg-hover); color: var(--text); }
        .aip-btn-sm       { padding: .35rem .7rem; font-size: .75rem; }

        /* ── Form elements ────────────────────────────────────────────────── */
        .aip-input, .aip-select, .aip-textarea {
            background: var(--bg-subtle); border: 1px solid var(--border);
            border-radius: 8px; color: var(--text); font-family: inherit;
            font-size: .85rem; padding: .5rem .75rem; width: 100%;
            outline: none; transition: all .15s;
        }
        .aip-input:focus, .aip-select:focus, .aip-textarea:focus { border-color: var(--accent); background: var(--bg); }
        .aip-textarea { resize: vertical; min-height: 80px; }
        .aip-label { font-size: .7rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .05em; }
        .aip-field { margin-bottom: 16px; }
        .aip-row   { display: flex; gap: 12px; flex-wrap: wrap; }
        .aip-row .aip-field { flex: 1; min-width: 140px; }

        /* ── Badges / pills ───────────────────────────────────────────────── */
        .aip-pill {
            display: inline-block; padding: 3px 10px; border-radius: 999px;
            font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em;
        }
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

        /* ── Progress bar ─────────────────────────────────────────────────── */
        .aip-prog-wrap { height: 6px; background: var(--border); border-radius: 999px; overflow: hidden; }
        .aip-prog-fill { height: 100%; background: var(--accent); border-radius: 999px; transition: width .5s ease; }
        .aip-prog-fill.is-failed { background: #ef4444; }
        .aip-prog-fill.is-done   { background: #22c55e; }

        /* ── Table ────────────────────────────────────────────────────────── */
        .aip-table-wrap { overflow-x: auto; border: 1px solid var(--border); border-radius: 10px; }
        .aip-table      { width: 100%; border-collapse: collapse; font-size: .8rem; }
        .aip-table th   { padding: 10px 12px; text-align: left; font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; color: var(--text-muted); border-bottom: 1px solid var(--border); font-weight: 600; white-space: nowrap; background: var(--bg-subtle); }
        .aip-table td   { padding: 12px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        .aip-table tr:last-child td { border-bottom: none; }
        .aip-table tr:hover td { background: var(--bg-hover); }
        .aip-table input[type=checkbox] { cursor: pointer; accent-color: var(--accent); width: 15px; height: 15px; }

        /* ── Log terminal ─────────────────────────────────────────────────── */
        .aip-terminal {
            background: var(--bg-subtle); border: 1px solid var(--border); border-radius: 10px;
            padding: 14px 16px; font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: .75rem; line-height: 1.7; color: var(--text);
            max-height: 320px; overflow-y: auto; white-space: pre-wrap; word-break: break-word;
        }
        .log-info  { color: var(--text-muted); }
        .log-ok    { color: #16a34a; }
        .log-warn  { color: #d97706; }
        .log-error { color: #dc2626; }

        [data-theme="dark"] .log-ok    { color: #86efac; }
        [data-theme="dark"] .log-warn  { color: #fcd34d; }
        [data-theme="dark"] .log-error { color: #fca5a5; }

        /* ── Connection test grid ─────────────────────────────────────────── */
        .aip-test-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; }
        .aip-test-box  { background: var(--bg); border: 1px solid var(--border); border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px var(--shadow); }
        .aip-test-box h4 { font-size: .85rem; color: var(--text); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; font-weight: 600; }
        .aip-test-box p { margin-bottom: 12px; }

        /* ── Feedback box ─────────────────────────────────────────────────── */
        .aip-feedback { margin-top: 12px; padding: .75rem 1rem; border-radius: 8px; font-size: .8rem; white-space: pre-wrap; word-break: break-word; display: none; border: 1px solid transparent; }
        .aip-feedback.ok    { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
        .aip-feedback.error { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
        .aip-feedback.warn  { background: #fffbeb; border-color: #fde68a; color: #92400e; }

        [data-theme="dark"] .aip-feedback.ok    { background: #064e3b; border-color: #166534; color: #86efac; }
        [data-theme="dark"] .aip-feedback.error { background: #450a0a; border-color: #7f1d1d; color: #fca5a5; }
        [data-theme="dark"] .aip-feedback.warn  { background: #451a03; border-color: #78350f; color: #fcd34d; }

        .aip-feedback.show  { display: block; }

        /* ── Upload dropzone ──────────────────────────────────────────────── */
        .aip-drop {
            border: 2px dashed var(--border); border-radius: 12px;
            padding: 40px; text-align: center; cursor: pointer;
            transition: all .15s; background: var(--bg-subtle);
        }
        .aip-drop:hover, .aip-drop.drag-over { border-color: var(--accent); background: var(--bg-hover); }
        .aip-drop p { color: var(--text-muted); font-size: .9rem; margin-top: 12px; }
        .aip-drop .big-ico { font-size: 2.5rem; color: var(--text-faint); }

        /* ── JSON preview ─────────────────────────────────────────────────── */
        .aip-json { background: var(--bg-subtle); border: 1px solid var(--border); border-radius: 10px; padding: 16px; font-family: 'JetBrains Mono', monospace; font-size: .75rem; color: var(--text); max-height: 400px; overflow: auto; white-space: pre-wrap; word-break: break-word; }

        /* ── Detail panel (slide-in) ──────────────────────────────────────── */
        .aip-detail-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 9000; backdrop-filter: blur(2px); }
        .aip-detail-panel {
            position: fixed; right: 0; top: 0; bottom: 0; width: min(640px, 95vw);
            background: var(--bg); border-left: 1px solid var(--border);
            display: flex; flex-direction: column; overflow: hidden;
            transform: translateX(100%); transition: transform .25s ease;
            z-index: 9001; box-shadow: -4px 0 24px var(--shadow);
        }
        .aip-detail-overlay.open { display: block; }
        .aip-detail-overlay.open .aip-detail-panel { transform: translateX(0); }
        .aip-detail-head { padding: 18px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 12px; background: var(--bg-subtle); }
        .aip-detail-head h2 { flex: 1; font-size: 1rem; font-weight: 600; color: var(--text); }
        .aip-detail-body { flex: 1; overflow-y: auto; padding: 20px; }
        .aip-detail-footer { padding: 16px 20px; border-top: 1px solid var(--border); display: flex; gap: 10px; flex-wrap: wrap; background: var(--bg-subtle); }

        /* ── Misc ─────────────────────────────────────────────────────────── */
        .aip-section-title { font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); margin: 20px 0 10px; }
        .aip-kv-grid { display: grid; grid-template-columns: 120px 1fr; gap: 8px 16px; font-size: .85rem; }
        .aip-kv-grid dt { color: var(--text-muted); }
        .aip-kv-grid dd { color: var(--text); font-weight: 500; word-break: break-all; }
        .aip-filter-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 16px; }
        .aip-search { flex: 1; min-width: 200px; }
        .aip-bulk-bar { display: none; align-items: center; gap: 12px; padding: 10px 16px; background: var(--bg-subtle); border-radius: 10px; margin-bottom: 16px; font-size: .85rem; color: var(--text); border: 1px solid var(--border); }
        .aip-bulk-bar.show { display: flex; }
        .hidden { display: none !important; }
    </style>
@endsection

@section('sidebar-elements')
    {{-- ══ LEFT SIDEBAR ══════════════════════════════════════════════════════ --}}

        <div class="aip-brand">
            <h1>⚡ AI Control Panel</h1>
            <p>PDF → Course pipeline manager</p>
        </div>

        <nav class="aip-nav">
            <button class="aip-nav-btn active" data-nav="upload">
                <span class="ico">📤</span> Upload PDF
            </button>
            <button class="aip-nav-btn" data-nav="jobs">
                <span class="ico">📋</span> All Jobs
            </button>
            <button class="aip-nav-btn" data-nav="connections">
                <span class="ico">🔌</span> Connections
            </button>
        </nav>

        <div class="aip-divider"></div>

        <div class="aip-section-title" style="padding: 0 14px">Live Stats</div>
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
            {{-- ══ RIGHT MAIN ══════════════════════════════════════════════════════════ --}}
            <div class="aip-right">
                {{-- Tabs --}}
                <div class="aip-tabs">
                    <button class="aip-tab active" data-tab="upload">📤 Upload</button>
                    <button class="aip-tab" data-tab="jobs">📋 Jobs</button>
                    <button class="aip-tab" data-tab="connections">🔌 Connections</button>
                </div>

                {{-- ── UPLOAD TAB ───────────────────────────────────────────────────── --}}
                <div class="aip-pane active" id="pane-upload">
                    <div class="aip-card">
                        <div class="aip-card-title">📄 New PDF → Course Job</div>
                        <p class="aip-card-sub">Drop a teacher's PDF. MinerU extracts the text, phi4 structures it into chapters/lessons/blocks.</p>

                        <div id="dropZone" class="aip-drop">
                            <div class="big-ico">📄</div>
                            <p id="dropLabel">Drop PDF here or <strong>click to browse</strong></p>
                            <input type="file" id="pdfFile" accept=".pdf" style="display:none">
                        </div>

                        <div class="aip-row" style="margin-top:16px">
                            <div class="aip-field">
                                <label class="aip-label">Year</label>
                                <select id="uYear" class="aip-select">
                                    <option value="1">Year 1</option>
                                    <option value="2">Year 2</option>
                                    <option value="3">Year 3</option>
                                </select>
                            </div>
                            <div class="aip-field" id="uBranchWrap">
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
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3" selected>3</option>
                                    <option value="5">5</option>
                                </select>
                            </div>
                            <div class="aip-field">
                                <label class="aip-label">Priority (1=high)</label>
                                <select id="uPriority" class="aip-select">
                                    <option value="1">1 — Urgent</option>
                                    <option value="3">3 — High</option>
                                    <option value="5" selected>5 — Normal</option>
                                    <option value="8">8 — Low</option>
                                </select>
                            </div>
                        </div>

                        <button id="uploadBtn" class="aip-btn aip-btn-primary" style="margin-top:8px">
                            ✨ Upload & Queue
                        </button>
                    </div>

                    {{-- Live progress for current upload --}}
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
                            <button id="upViewJsonBtn" class="aip-btn aip-btn-outline hidden">👁 View JSON</button>
                            <button id="upRetryBtn" class="aip-btn aip-btn-warn hidden">↺ Retry</button>
                            <button id="upCancelBtn" class="aip-btn aip-btn-ghost">✕ Cancel / Discard</button>
                        </div>
                        <div id="upFeedback" class="aip-feedback"></div>
                    </div>

                    {{-- JSON result (hidden until done) --}}
                    <div id="jsonResultCard" class="aip-card hidden">
                        <div class="aip-card-title">JSON Preview</div>
                        <pre id="jsonPreview" class="aip-json"></pre>
                    </div>
                </div>

                {{-- ── JOBS TAB ──────────────────────────────────────────────────────── --}}
                <div class="aip-pane" id="pane-jobs">
                    {{-- Bulk action bar --}}
                    <div class="aip-bulk-bar" id="bulkBar">
                        <span id="bulkCount">0 selected</span>
                        <div style="display:flex;gap:8px;margin-left:auto">
                            <button class="aip-btn aip-btn-warn aip-btn-sm" onclick="bulkDo('retry')">↺ Retry all</button>
                            <button class="aip-btn aip-btn-ghost aip-btn-sm" onclick="bulkDo('cancel')">✕ Cancel all</button>
                            <button class="aip-btn aip-btn-danger aip-btn-sm" onclick="bulkDo('delete')">🗑 Delete all</button>
                        </div>
                    </div>

                    {{-- Filters --}}
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
                        <label style="display:flex;align-items:center;gap:8px;font-size:.8rem;color:var(--text-muted);cursor:pointer">
                            <input type="checkbox" id="autoRefreshChk" checked style="accent-color:var(--accent)"> Auto-refresh
                        </label>
                    </div>

                    <div class="aip-table-wrap">
                        <table class="aip-table">
                            <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th>
                                <th>#</th>
                                <th>File</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Attempt</th>
                                <th>Year/Branch</th>
                                <th>By</th>
                                <th>Started</th>
                                <th>Duration</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody id="jobsTbody">
                            <tr><td colspan="11" style="text-align:center;color:var(--text-muted);padding:40px">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="jobsPager" style="display:flex;gap:8px;margin-top:16px;align-items:center;font-size:.8rem;color:var(--text-muted)"></div>
                </div>

                {{-- ── CONNECTIONS TAB ───────────────────────────────────────────────── --}}
                <div class="aip-pane" id="pane-connections">
                    <div class="aip-test-grid">
                        <div class="aip-test-box">
                            <h4>🤖 Ollama (phi4)</h4>
                            <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:14px">Tests if phi4 is loaded and responding on port 11434.</p>
                            <button class="aip-btn aip-btn-outline" style="width:100%" id="testOllamaBtn">Test Ollama</button>
                            <div class="aip-feedback" id="testOllamaFb"></div>
                        </div>

                        <div class="aip-test-box">
                            <h4>📑 MinerU (PDF extractor)</h4>
                            <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:14px">Checks that the Python venv and mineru CLI are present.</p>
                            <button class="aip-btn aip-btn-outline" style="width:100%" id="testMinerUBtn">Test MinerU</button>
                            <div class="aip-feedback" id="testMinerUFb"></div>
                        </div>

                        <div class="aip-test-box">
                            <h4>🐍 Python debug</h4>
                            <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:14px">Verifies PHP can spawn Python and import mineru correctly.</p>
                            <button class="aip-btn aip-btn-outline" style="width:100%" id="testDebugBtn">Debug Python</button>
                            <div class="aip-feedback" id="testDebugFb"></div>
                        </div>
                    </div>
                </div>
            </div><!-- .aip-right -->
        </div><!-- .aip-shell -->
    </div><!-- .aip -->

    {{-- ══ JOB DETAIL SLIDE PANEL ══════════════════════════════════════════════════ --}}
    <div class="aip-detail-overlay" id="detailOverlay">
        <div class="aip-detail-panel" id="detailPanel">
            <div class="aip-detail-head">
                <h2 id="dpTitle">Job Detail</h2>
                <span class="aip-pill" id="dpPill"></span>
                <button class="aip-btn aip-btn-ghost aip-btn-sm" id="dpClose">✕ Close</button>
            </div>

            <div class="aip-detail-body" id="dpBody">
                {{-- Meta info --}}
                <div class="aip-section-title">📁 File Info</div>
                <dl class="aip-kv-grid" id="dpMeta"></dl>

                {{-- Edit section --}}
                <div class="aip-section-title">✏️ Edit Job</div>
                <div class="aip-row">
                    <div class="aip-field">
                        <label class="aip-label">Max retries</label>
                        <select id="dpMaxAttempts" class="aip-select">
                            <option value="1">1</option><option value="2">2</option>
                            <option value="3">3</option><option value="5">5</option><option value="10">10</option>
                        </select>
                    </div>
                    <div class="aip-field">
                        <label class="aip-label">Priority (1=high)</label>
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
                    <textarea id="dpNote" class="aip-textarea" placeholder="Internal note about this job…"></textarea>
                </div>
                <button class="aip-btn aip-btn-primary aip-btn-sm" id="dpSaveMeta">💾 Save changes</button>
                <div class="aip-feedback" id="dpMetaFb" style="margin-top:10px"></div>

                {{-- Progress --}}
                <div class="aip-section-title">⏳ Progress</div>
                <div class="aip-prog-wrap" style="margin-bottom:10px">
                    <div class="aip-prog-fill" id="dpProgBar" style="width:0%"></div>
                </div>

                {{-- Log terminal --}}
                <div class="aip-section-title" style="display:flex;align-items:center;justify-content:space-between">
                    <span>📟 Live Logs</span>
                    <button class="aip-btn aip-btn-ghost aip-btn-sm" id="dpClearLogs">Clear logs</button>
                </div>
                <div class="aip-terminal" id="dpTerminal">No logs yet.</div>

                {{-- JSON result --}}
                <div id="dpResultSection" class="hidden">
                    <div class="aip-section-title" style="display:flex;align-items:center;justify-content:space-between">
                        <span>📦 Result JSON</span>
                        <button class="aip-btn aip-btn-ghost aip-btn-sm" id="dpToggleJson">Show / Hide</button>
                    </div>
                    <pre id="dpJson" class="aip-json hidden"></pre>
                </div>
            </div>

            <div class="aip-detail-footer" id="dpFooter">
                <button class="aip-btn aip-btn-success hidden" id="dpSaveBtn">💾 Save as course</button>
                <button class="aip-btn aip-btn-warn hidden"    id="dpRetryBtn">↺ Retry</button>
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

            const CSRF   = document.querySelector('meta[name="csrf-token"]').content;
            const R      = (name, id) => routes[name]?.replace('__ID__', id ?? '');

// All routes
            const routes = {
                test:        "{{ route('admin.ai.test') }}",
                testMineru:  "{{ route('admin.ai.test-mineru') }}",
                debugPython: "{{ route('admin.ai.debug') }}",
                jsonify:     "{{ route('admin.ai.jsonify') }}",
                jobsList:    "{{ route('admin.ai.jobs.list') }}",
                stats:       "{{ route('admin.ai.stats') }}",
                detail:      "{{ route('admin.ai.jobs.detail', ['id'=>'__ID__']) }}",
                logs:        "{{ route('admin.ai.logs', ['id'=>'__ID__']) }}",
                status:      "{{ route('admin.ai.status', ['id'=>'__ID__']) }}",
                update:      "{{ route('admin.ai.jobs.update', ['id'=>'__ID__']) }}",
                retry:       "{{ route('admin.ai.jobs.retry', ['id'=>'__ID__']) }}",
                cancel:      "{{ route('admin.ai.jobs.cancel', ['id'=>'__ID__']) }}",
                delete:      "{{ route('admin.ai.jobs.delete', ['id'=>'__ID__']) }}",
                clearLogs:   "{{ route('admin.ai.jobs.clear-logs', ['id'=>'__ID__']) }}",
                bulk:        "{{ route('admin.ai.bulk') }}",
                store:       "{{ route('admin.ai.store') }}",
            };

// ── Helpers ────────────────────────────────────────────────────────────────
            async function api(method, url, body) {
                const opts = { method, headers: { 'Accept':'application/json', 'X-CSRF-TOKEN': CSRF } };
                if (body) {
                    if (body instanceof FormData) {
                        opts.body = body;
                    } else {
                        opts.headers['Content-Type'] = 'application/json';
                        opts.body = JSON.stringify(body);
                    }
                }
                const res  = await fetch(url, opts);
                const data = await res.json().catch(() => ({}));
                return { ok: res.ok, status: res.status, data };
            }

            function showFb(el, msg, type) {
                el.textContent = msg;
                el.className   = 'aip-feedback show ' + type;
            }

            function pillClass(status) {
                const map = { queued:'p-queued', processing:'p-processing', done:'p-done', failed:'p-failed', saved:'p-saved', cancelled:'p-cancelled' };
                return map[status] || 'p-queued';
            }

            function fmtDur(s) {
                if (!s) return '—';
                if (s < 60) return s + 's';
                return Math.floor(s/60) + 'm ' + (s%60) + 's';
            }

            function escHtml(s) {
                return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            }

            const LOG_CLR  = { info:'var(--text-muted)', ok:'#16a34a', warn:'#d97706', error:'#dc2626' };
            const LOG_ICO  = { info:'·', ok:'✓', warn:'⚠', error:'✕' };

            function renderLogs(logs, container) {
                container.innerHTML = logs.length
                    ? logs.map(e => `<span style="color:${LOG_CLR[e.level]||LOG_CLR.info}">[${escHtml(e.ts)}] ${LOG_ICO[e.level]||'·'} ${escHtml(e.message)}</span>`).join('\n')
                    : '<span style="color:var(--text-faint)">No logs yet.</span>';
                container.scrollTop = container.scrollHeight;
            }

// ── Nav / Tab switching ────────────────────────────────────────────────────
            document.querySelectorAll('.aip-nav-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.aip-nav-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    const target = btn.dataset.nav;
                    document.querySelectorAll('.aip-tab').forEach(t => {
                        t.classList.toggle('active', t.dataset.tab === target);
                    });
                    document.querySelectorAll('.aip-pane').forEach(p => {
                        p.classList.toggle('active', p.id === 'pane-' + target);
                    });
                    if (target === 'jobs') loadJobs();
                });
            });

            document.querySelectorAll('.aip-tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    const target = tab.dataset.tab;
                    document.querySelectorAll('.aip-tab').forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    document.querySelectorAll('.aip-pane').forEach(p => p.classList.toggle('active', p.id === 'pane-'+target));
                    document.querySelectorAll('.aip-nav-btn').forEach(b => b.classList.toggle('active', b.dataset.nav === target));
                    if (target === 'jobs') loadJobs();
                });
            });

// ── Stats ──────────────────────────────────────────────────────────────────
            async function loadStats() {
                const { ok, data } = await api('GET', routes.stats);
                if (!ok) return;
                document.getElementById('ss-q').textContent = data.queued;
                document.getElementById('ss-p').textContent = data.processing;
                document.getElementById('ss-d').textContent = data.done;
                document.getElementById('ss-f').textContent = data.failed;
                document.getElementById('ss-s').textContent = data.saved;
                document.getElementById('ss-c').textContent = data.cancelled;
                document.getElementById('ss-avg').textContent = data.avg_duration ? fmtDur(Math.round(data.avg_duration)) : '—';
            }

            setInterval(loadStats, 8000);
            loadStats();

// ── Upload flow ────────────────────────────────────────────────────────────
            let currentJobId   = null;
            let uploadPollT    = null;
            let uploadLogCount = 0;

            const dropZone   = document.getElementById('dropZone');
            const pdfInput   = document.getElementById('pdfFile');
            const dropLabel  = document.getElementById('dropLabel');
            const uploadBtn  = document.getElementById('uploadBtn');
            const upProgress = document.getElementById('uploadProgress');
            const upTitle    = document.getElementById('upTitle');
            const upPill     = document.getElementById('upPill');
            const upBar      = document.getElementById('upBar');
            const upTerminal = document.getElementById('upTerminal');
            const upSaveBtn  = document.getElementById('upSaveBtn');
            const upViewJson = document.getElementById('upViewJsonBtn');
            const upRetryBtn = document.getElementById('upRetryBtn');
            const upCancelBtn= document.getElementById('upCancelBtn');
            const upFeedback = document.getElementById('upFeedback');
            const jsonCard   = document.getElementById('jsonResultCard');
            const jsonPreview= document.getElementById('jsonPreview');
            const uYear      = document.getElementById('uYear');
            const uBranch    = document.getElementById('uBranch');
            const uBranchWrap= document.getElementById('uBranchWrap');

            uYear.addEventListener('change', () => {
                uBranchWrap.style.opacity = parseInt(uYear.value) > 1 ? '1' : '0.5';
                if (parseInt(uYear.value) === 1) uBranch.value = 'none';
            });

            dropZone.addEventListener('click', () => pdfInput.click());
            dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
            dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
            dropZone.addEventListener('drop', e => {
                e.preventDefault(); dropZone.classList.remove('drag-over');
                const f = e.dataTransfer.files[0];
                if (f?.type === 'application/pdf') { setFile(f); }
            });
            pdfInput.addEventListener('change', () => { if (pdfInput.files[0]) setFile(pdfInput.files[0]); });

            function setFile(f) {
                const dt = new DataTransfer(); dt.items.add(f); pdfInput.files = dt.files;
                dropLabel.innerHTML = `📄 <strong>${escHtml(f.name)}</strong> (${(f.size/1024).toFixed(0)} KB)`;
            }

            uploadBtn.addEventListener('click', async () => {
                if (!pdfInput.files[0]) { alert('Select a PDF first.'); return; }
                uploadBtn.disabled = true; uploadBtn.textContent = 'Uploading…';

                const fd = new FormData();
                fd.append('pdf_file',      pdfInput.files[0]);
                fd.append('course_year',   uYear.value);
                fd.append('course_branch', uBranch.value);
                fd.append('max_attempts',  document.getElementById('uMaxAttempts').value);
                fd.append('priority',      document.getElementById('uPriority').value);

                const { ok, data } = await api('POST', routes.jsonify, fd);
                uploadBtn.disabled = false; uploadBtn.textContent = '✨ Upload & Queue';

                if (!ok) { alert('Upload failed: ' + (data.message || data.error)); return; }

                currentJobId   = data.job_id;
                uploadLogCount = 0;
                upFeedback.className = 'aip-feedback';
                upSaveBtn.classList.add('hidden');
                upViewJson.classList.add('hidden');
                upRetryBtn.classList.add('hidden');
                jsonCard.classList.add('hidden');
                upProgress.classList.remove('hidden');
                upProgress.scrollIntoView({ behavior:'smooth' });
                startUploadPoll();
            });

            function startUploadPoll() {
                if (uploadPollT) clearInterval(uploadPollT);
                uploadPollT = setInterval(pollUpload, 2500);
            }

            async function pollUpload() {
                if (!currentJobId) return;
                const { ok, data } = await api('GET', R('logs', currentJobId));
                if (!ok) return;

                upPill.textContent = data.status;
                upPill.className   = 'aip-pill ' + pillClass(data.status);

                const pct = data.progress || 0;
                upBar.style.width = pct + '%';
                upBar.className   = 'aip-prog-fill' + (data.status==='failed'?' is-failed':data.status==='done'||data.status==='saved'?' is-done':'');

                const logs = data.logs || [];
                if (logs.length > uploadLogCount) {
                    const newEntries = logs.slice(uploadLogCount);
                    newEntries.forEach(e => {
                        const line = document.createElement('span');
                        line.style.color   = e.level === 'info' ? 'var(--text-muted)' : LOG_CLR[e.level] || LOG_CLR.info;
                        line.style.display = 'block';
                        line.textContent   = `[${e.ts}] ${LOG_ICO[e.level]||'·'} ${e.message}`;
                        upTerminal.appendChild(line);
                    });
                    upTerminal.scrollTop = upTerminal.scrollHeight;
                    uploadLogCount = logs.length;
                }

                if (data.status === 'done') {
                    clearInterval(uploadPollT);
                    upTitle.textContent = '✅ Job complete!';
                    upSaveBtn.classList.remove('hidden');
                    upViewJson.classList.remove('hidden');
                    loadStats();
                    loadJobs();
                } else if (data.status === 'failed') {
                    clearInterval(uploadPollT);
                    upTitle.textContent = '❌ Job failed';
                    upRetryBtn.classList.remove('hidden');
                    loadStats();
                    loadJobs();
                } else if (data.status === 'processing') {
                    upTitle.textContent = '⚙️ Processing…';
                }
            }

            upSaveBtn.addEventListener('click', async () => {
                if (!currentJobId) return;
                upSaveBtn.disabled = true; upSaveBtn.textContent = 'Saving…';
                const { ok, data } = await api('POST', routes.store, { job_id: currentJobId });
                upSaveBtn.disabled = false; upSaveBtn.textContent = '💾 Save as course';
                if (ok && data.success) {
                    showFb(upFeedback, '✅ Course saved as draft (ID ' + data.course_id + '). Reload the courses page to see it.', 'ok');
                    upSaveBtn.disabled = true;
                    loadStats(); loadJobs();
                } else {
                    showFb(upFeedback, '❌ ' + (data.error || 'Save failed'), 'error');
                }
            });

            upViewJson.addEventListener('click', async () => {
                if (!currentJobId) return;
                const { ok, data } = await api('GET', R('status', currentJobId));
                if (ok && data.result) {
                    jsonPreview.textContent = JSON.stringify(data.result, null, 2);
                    jsonCard.classList.toggle('hidden');
                    jsonCard.scrollIntoView({ behavior:'smooth' });
                }
            });

            upRetryBtn.addEventListener('click', async () => {
                if (!currentJobId) return;
                const { ok, data } = await api('POST', R('retry', currentJobId));
                if (ok) {
                    uploadLogCount = 0;
                    upTerminal.innerHTML = '';
                    upRetryBtn.classList.add('hidden');
                    upTitle.textContent = '⏳ Re-queued…';
                    startUploadPoll();
                } else {
                    showFb(upFeedback, '❌ ' + (data.error || 'Retry failed'), 'error');
                }
            });

            upCancelBtn.addEventListener('click', async () => {
                if (currentJobId) await api('POST', R('cancel', currentJobId));
                clearInterval(uploadPollT);
                currentJobId = null; uploadLogCount = 0;
                upProgress.classList.add('hidden');
                jsonCard.classList.add('hidden');
                pdfInput.value = '';
                dropLabel.innerHTML = 'Drop PDF here or <strong>click to browse</strong>';
                loadStats(); loadJobs();
            });

// ── Jobs table ─────────────────────────────────────────────────────────────
            let jobsPage = 1;
            let autoRefreshT = null;
            const jobsTbody = document.getElementById('jobsTbody');
            const jobsPager = document.getElementById('jobsPager');

            async function loadJobs(page) {
                page = page || jobsPage;
                const search = document.getElementById('searchInput').value;
                const status = document.getElementById('statusFilter').value;
                const url    = routes.jobsList + `?page=${page}&search=${encodeURIComponent(search)}&status=${status}`;
                const { ok, data } = await api('GET', url);
                if (!ok) return;

                jobsPage = data.current_page;
                renderJobRows(data.data);
                renderPager(data);
                loadStats();
            }

            function renderJobRows(jobs) {
                if (!jobs.length) {
                    jobsTbody.innerHTML = '<tr><td colspan="11" style="text-align:center;color:var(--text-muted);padding:40px">No jobs found.</td></tr>';
                    return;
                }
                jobsTbody.innerHTML = jobs.map(j => `
        <tr data-id="${j.id}">
            <td><input type="checkbox" class="job-chk" value="${j.id}" onchange="updateBulkBar()"></td>
            <td style="font-weight:600;color:var(--text-muted)">#${j.id}</td>
            <td>
                <div style="font-weight:500;font-size:.85rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${escHtml(j.original_filename||'')}">
                    ${escHtml(j.original_filename || 'unknown')}
                </div>
                <div style="font-size:.75rem;color:var(--text-faint)">${j.file_size_human}</div>
                ${j.note ? `<div style="font-size:.75rem;color:var(--text-warn);margin-top:2px">📝 ${escHtml(j.note)}</div>` : ''}
            </td>
            <td><span class="aip-pill ${pillClass(j.status)}">${j.status}</span></td>
            <td style="min-width:100px">
                <div class="aip-prog-wrap">
                    <div class="aip-prog-fill${j.status==='failed'?' is-failed':j.status==='done'||j.status==='saved'?' is-done':''}" style="width:${j.progress}%"></div>
                </div>
                <div style="font-size:.7rem;color:var(--text-faint);margin-top:4px">${j.progress}%</div>
            </td>
            <td style="font-size:.8rem">${j.attempt}/${j.max_attempts}</td>
            <td style="font-size:.8rem">${j.year} / ${j.branch}</td>
            <td style="font-size:.8rem;color:var(--text-muted)">${escHtml(j.started_by||'—')}</td>
            <td style="font-size:.75rem;color:var(--text-faint);white-space:nowrap">${j.started_at ? j.started_at.slice(0,16) : j.created_at.slice(0,16)}</td>
            <td style="font-size:.8rem">${fmtDur(j.duration_seconds)}</td>
            <td>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                    <button class="aip-btn aip-btn-outline aip-btn-sm" onclick="openDetail(${j.id})">📋</button>
                    ${j.can_retry  ? `<button class="aip-btn aip-btn-warn aip-btn-sm"    onclick="doRetry(${j.id})">↺</button>` : ''}
                    ${j.can_cancel ? `<button class="aip-btn aip-btn-ghost aip-btn-sm"   onclick="doCancel(${j.id})">✕</button>` : ''}
                    <button class="aip-btn aip-btn-danger aip-btn-sm" onclick="doDelete(${j.id})">🗑</button>
                </div>
            </td>
        </tr>
    `).join('');
            }

            function renderPager(data) {
                if (data.last_page <= 1) { jobsPager.innerHTML = ''; return; }
                let html = `<span>Page ${data.current_page} of ${data.last_page}</span>`;
                if (data.current_page > 1) html += `<button class="aip-btn aip-btn-ghost aip-btn-sm" onclick="loadJobs(${data.current_page-1})">← Prev</button>`;
                if (data.current_page < data.last_page) html += `<button class="aip-btn aip-btn-ghost aip-btn-sm" onclick="loadJobs(${data.current_page+1})">Next →</button>`;
                jobsPager.innerHTML = html;
            }

            document.getElementById('autoRefreshChk').addEventListener('change', function() {
                if (this.checked) { autoRefreshT = setInterval(loadJobs, 5000); }
                else { clearInterval(autoRefreshT); }
            });
            autoRefreshT = setInterval(loadJobs, 5000);

            document.getElementById('searchInput').addEventListener('input',  () => { jobsPage=1; loadJobs(); });
            document.getElementById('statusFilter').addEventListener('change', () => { jobsPage=1; loadJobs(); });
            loadJobs();

// ── Bulk select ────────────────────────────────────────────────────────────
            function toggleSelectAll() {
                const checked = document.getElementById('selectAll').checked;
                document.querySelectorAll('.job-chk').forEach(c => c.checked = checked);
                updateBulkBar();
            }
            window.toggleSelectAll = toggleSelectAll;

            function updateBulkBar() {
                const selected = getSelected();
                const bar = document.getElementById('bulkBar');
                bar.classList.toggle('show', selected.length > 0);
                document.getElementById('bulkCount').textContent = selected.length + ' selected';
            }
            window.updateBulkBar = updateBulkBar;

            function getSelected() {
                return Array.from(document.querySelectorAll('.job-chk:checked')).map(c => parseInt(c.value));
            }

            window.bulkDo = async function(action) {
                const ids = getSelected();
                if (!ids.length) return;
                if (action === 'delete' && !confirm(`Delete ${ids.length} job(s)? This cannot be undone.`)) return;
                await api('POST', routes.bulk, { action, ids });
                loadJobs(); loadStats();
            };

// ── Quick row actions ──────────────────────────────────────────────────────
            window.doRetry = async function(id) {
                const { ok, data } = await api('POST', R('retry', id));
                if (!ok) alert('❌ ' + (data.error || 'Retry failed'));
                loadJobs(); loadStats();
            };

            window.doCancel = async function(id) {
                const { ok, data } = await api('POST', R('cancel', id));
                if (!ok) alert('❌ ' + (data.error || 'Cancel failed'));
                loadJobs(); loadStats();
            };

            window.doDelete = async function(id) {
                if (!confirm('Delete job #' + id + '?')) return;
                await api('DELETE', R('delete', id));
                loadJobs(); loadStats();
            };

// ── Detail panel ───────────────────────────────────────────────────────────
            const overlay   = document.getElementById('detailOverlay');
            const dpTitle   = document.getElementById('dpTitle');
            const dpPill    = document.getElementById('dpPill');
            const dpBody    = document.getElementById('dpBody');
            const dpMeta    = document.getElementById('dpMeta');
            const dpProgBar = document.getElementById('dpProgBar');
            const dpTerm    = document.getElementById('dpTerminal');
            const dpSaveMeta= document.getElementById('dpSaveMeta');
            const dpMetaFb  = document.getElementById('dpMetaFb');
            const dpSaveBtn = document.getElementById('dpSaveBtn');
            const dpRetryBtn= document.getElementById('dpRetryBtn');
            const dpCancelBtn=document.getElementById('dpCancelBtn');
            const dpDeleteBtn=document.getElementById('dpDeleteBtn');
            const dpClrLogs = document.getElementById('dpClearLogs');
            const dpJson    = document.getElementById('dpJson');
            const dpJsonSec = document.getElementById('dpResultSection');
            const dpTogJson = document.getElementById('dpToggleJson');

            let dpJobId     = null;
            let dpPollT     = null;

            window.openDetail = async function(id) {
                dpJobId = id;
                dpTitle.textContent = 'Job #' + id;
                dpPill.textContent  = '…';
                dpMeta.innerHTML    = '';
                dpTerm.textContent  = 'Loading…';
                dpSaveMeta.disabled = false;
                dpMetaFb.className  = 'aip-feedback';
                dpJsonSec.classList.add('hidden');
                dpJson.classList.add('hidden');

                overlay.classList.add('open');
                document.body.style.overflow = 'hidden';

                await refreshDetail();
                dpPollT = setInterval(refreshDetail, 2500);
            };

            async function refreshDetail() {
                const { ok, data } = await api('GET', R('detail', dpJobId));
                if (!ok) return;

                dpTitle.textContent = `Job #${data.id} — ${escHtml(data.original_filename||'unknown')}`;
                dpPill.textContent  = data.status;
                dpPill.className    = 'aip-pill ' + pillClass(data.status);

                dpMeta.innerHTML = `
        <dt>Filename</dt>   <dd>${escHtml(data.original_filename||'—')}</dd>
        <dt>File size</dt>  <dd>${data.file_size_human}</dd>
        <dt>PDF path</dt>   <dd style="font-size:.75rem">${escHtml(data.pdf_path||'—')}</dd>
        <dt>Year</dt>       <dd>${data.year}</dd>
        <dt>Branch</dt>     <dd>${data.branch}</dd>
        <dt>Uploaded by</dt><dd>${escHtml(data.started_by||'—')}</dd>
        <dt>Queued at</dt>  <dd>${data.created_at||'—'}</dd>
        <dt>Started at</dt> <dd>${data.started_at||'—'}</dd>
        <dt>Finished at</dt><dd>${data.finished_at||'—'}</dd>
        <dt>Duration</dt>   <dd>${fmtDur(data.duration_seconds)}</dd>
        <dt>Attempt</dt>    <dd>${data.attempt} / ${data.max_attempts}</dd>
        <dt>Priority</dt>   <dd>${data.priority}</dd>
        ${data.error_message ? `<dt>Error</dt><dd style="color:#dc2626">${escHtml(data.error_message)}</dd>` : ''}
        ${data.note ? `<dt>Note</dt><dd style="color:#d97706">${escHtml(data.note)}</dd>` : ''}
    `;

                document.getElementById('dpMaxAttempts').value = data.max_attempts || 3;
                document.getElementById('dpPriority').value    = data.priority || 5;
                document.getElementById('dpYear').value        = data.year || 1;
                document.getElementById('dpBranch').value      = data.branch || 'none';
                document.getElementById('dpNote').value        = data.note || '';

                dpProgBar.style.width = data.progress + '%';
                dpProgBar.className   = 'aip-prog-fill' + (data.status==='failed'?' is-failed':data.status==='done'||data.status==='saved'?' is-done':'');

                const { data: ld } = await api('GET', R('logs', dpJobId));
                renderLogs(ld.logs || [], dpTerm);

                dpSaveBtn.classList.toggle('hidden',   data.status !== 'done');
                dpRetryBtn.classList.toggle('hidden',  !data.can_retry);
                dpCancelBtn.classList.toggle('hidden', !data.can_cancel);
                dpDeleteBtn.classList.remove('hidden');

                if (data.status === 'done' || data.status === 'saved') {
                    dpJsonSec.classList.remove('hidden');
                    const { data: sd } = await api('GET', R('status', dpJobId));
                    if (sd.result) dpJson.textContent = JSON.stringify(sd.result, null, 2);
                }

                if (['done','failed','saved','cancelled'].includes(data.status)) {
                    clearInterval(dpPollT);
                }
            }

            function closeDetail() {
                clearInterval(dpPollT);
                overlay.classList.remove('open');
                document.body.style.overflow = '';
                dpJobId = null;
                loadJobs(); loadStats();
            }

            document.getElementById('dpClose').addEventListener('click', closeDetail);
            overlay.addEventListener('click', e => { if (e.target === overlay) closeDetail(); });

            dpSaveMeta.addEventListener('click', async () => {
                if (!dpJobId) return;
                dpSaveMeta.disabled = true;
                const body = {
                    max_attempts: parseInt(document.getElementById('dpMaxAttempts').value),
                    priority:     parseInt(document.getElementById('dpPriority').value),
                    year:         document.getElementById('dpYear').value,
                    branch:       document.getElementById('dpBranch').value,
                    note:         document.getElementById('dpNote').value,
                };
                const { ok, data } = await api('PATCH', R('update', dpJobId), body);
                dpSaveMeta.disabled = false;
                showFb(dpMetaFb, ok ? '✅ Saved.' : '❌ ' + (data.error||'Failed'), ok ? 'ok' : 'error');
            });

            dpSaveBtn.addEventListener('click', async () => {
                dpSaveBtn.disabled = true; dpSaveBtn.textContent = 'Saving…';
                const { ok, data } = await api('POST', routes.store, { job_id: dpJobId });
                dpSaveBtn.disabled = false; dpSaveBtn.textContent = '💾 Save as course';
                if (ok && data.success) { alert('✅ Course saved (ID ' + data.course_id + ')'); closeDetail(); }
                else alert('❌ ' + (data.error||'Save failed'));
            });

            dpRetryBtn.addEventListener('click', async () => {
                const { ok, data } = await api('POST', R('retry', dpJobId));
                if (!ok) { alert('❌ ' + (data.error||'Retry failed')); return; }
                dpPollT = setInterval(refreshDetail, 2500);
                refreshDetail();
            });

            dpCancelBtn.addEventListener('click', async () => {
                const { ok } = await api('POST', R('cancel', dpJobId));
                if (ok) refreshDetail();
            });

            dpDeleteBtn.addEventListener('click', async () => {
                if (!confirm('Delete job #' + dpJobId + '? This cannot be undone.')) return;
                await api('DELETE', R('delete', dpJobId));
                closeDetail();
            });

            dpClrLogs.addEventListener('click', async () => {
                await api('DELETE', R('clearLogs', dpJobId));
                dpTerm.textContent = 'Logs cleared.';
            });

            dpTogJson.addEventListener('click', () => dpJson.classList.toggle('hidden'));

// ── Connection tests ───────────────────────────────────────────────────────
            document.getElementById('testOllamaBtn').addEventListener('click', async function() {
                const fb = document.getElementById('testOllamaFb');
                this.disabled = true; this.textContent = 'Testing…';
                const { ok, data } = await api('POST', routes.test, {});
                showFb(fb, ok && data.ok ? '✅ ' + data.message : '❌ ' + (data.error||'Not reachable'), ok&&data.ok?'ok':'error');
                this.disabled = false; this.textContent = 'Test Ollama';
            });

            document.getElementById('testMinerUBtn').addEventListener('click', async function() {
                const fb = document.getElementById('testMinerUFb');
                this.disabled = true; this.textContent = 'Testing…';
                showFb(fb, '⏳ Checking…', 'warn');
                const { ok, data } = await api('POST', routes.testMineru, {});
                if (ok && data.ok) {
                    showFb(fb, `✅ ${data.message}\nVersion: ${data.version}\nCLI: ${data.cli}`, 'ok');
                } else {
                    showFb(fb, `❌ ${data.error} [step: ${data.step||'?'}]${data.detail?'\n→'+data.detail:''}`, 'error');
                }
                this.disabled = false; this.textContent = 'Test MinerU';
            });

            document.getElementById('testDebugBtn').addEventListener('click', async function() {
                const fb = document.getElementById('testDebugFb');
                this.disabled = true; this.textContent = 'Debugging…';
                const { ok, data } = await api('GET', routes.debugPython);
                const msg = `Python exists: ${data.python_exists}\nExit code: ${data.exit_code}\nStdout: ${data.stdout||'(empty)'}\nStderr: ${data.stderr||'(none)'}`;
                showFb(fb, msg, data.exit_code === 0 ? 'ok' : 'error');
                this.disabled = false; this.textContent = 'Debug Python';
            });

        })();
    </script>
@endsection
