@extends('layouts.edditor')

@section('css')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&family=DM+Mono:wght@400;500&display=swap');

        :root {
            --profile-font: 'DM Sans', sans-serif;
            --profile-mono: 'DM Mono', monospace;
            --ring: rgba(79, 70, 229, 0.18);
        }

        .profile-wrap {
            font-family: var(--profile-font);
            max-width: 960px;
            margin: 0 auto;
            padding: 32px 24px 64px;
            display: flex;
            flex-direction: column;
            gap: 28px;
        }

        /* ── Hero card ── */
        .hero-card {
            position: relative;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        .hero-banner {
            height: 90px;
            background: repeating-linear-gradient(
                135deg,
                var(--bg-subtle) 0px,
                var(--bg-subtle) 1px,
                transparent 1px,
                transparent 18px
            ),
            repeating-linear-gradient(
                45deg,
                var(--bg-subtle) 0px,
                var(--bg-subtle) 1px,
                transparent 1px,
                transparent 18px
            );
            background-color: var(--bg);
            border-bottom: 1px solid var(--border);
        }

        .hero-body {
            padding: 0 28px 28px;
            display: flex;
            align-items: flex-end;
            gap: 20px;
            flex-wrap: wrap;
        }

        .profile-avatar-wrap {
            margin-top: -36px;
            flex-shrink: 0;
        }

        .profile-avatar {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: var(--accent);
            color: #fff;
            font-size: 28px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid var(--bg);
            text-transform: uppercase;
            letter-spacing: -1px;
            font-family: var(--profile-font);
        }

        .hero-meta {
            flex: 1;
            padding-top: 16px;
            min-width: 200px;
        }

        .hero-name {
            font-size: 22px;
            font-weight: 600;
            color: var(--text);
            line-height: 1.2;
            margin: 0 0 4px;
        }

        .hero-email {
            font-size: 13px;
            color: var(--text-faint);
            font-family: var(--profile-mono);
            margin: 0;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-top: 16px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            font-size: 13px;
            font-family: var(--profile-font);
            font-weight: 500;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text-mid);
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
            text-decoration: none;
        }

        .btn:hover {
            background: var(--bg-hover);
            border-color: #c9c5f8;
            color: var(--text);
        }

        .btn-danger {
            color: #e53e3e;
            border-color: #fee2e2;
        }

        .btn-danger:hover {
            background: #fff5f5;
            border-color: #fca5a5;
            color: #c81e1e;
        }

        [data-theme="dark"] .btn-danger       { color: #f87171; border-color: #5c2020; }
        [data-theme="dark"] .btn-danger:hover { background: #2a1515; border-color: #7f2222; }

        .btn-primary {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            border-color: var(--accent-hover);
            color: #fff;
        }

        /* ── Stats row ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
        }

        .stat-card {
            background: var(--bg-subtle);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .stat-label {
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--text-faint);
        }

        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: var(--text);
            line-height: 1;
            font-variant-numeric: tabular-nums;
        }

        .stat-sub {
            font-size: 12px;
            color: var(--text-faint);
            margin-top: 2px;
        }

        /* ── Section ── */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .section-title {
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--text-faint);
        }

        /* ── Info card ── */
        .info-card {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .info-row {
            display: flex;
            align-items: center;
            padding: 13px 20px;
            border-bottom: 1px solid var(--border-mid);
            gap: 16px;
        }

        .info-row:last-child { border-bottom: none; }

        .info-key {
            width: 130px;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--text-faint);
            flex-shrink: 0;
        }

        .info-val {
            font-size: 14px;
            color: var(--text-mid);
            font-family: var(--profile-mono);
            flex: 1;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .badge-active   { background: #dcfce7; color: #166534; }
        .badge-inactive { background: #f3f4f6; color: var(--text-faint); }
        [data-theme="dark"] .badge-active   { background: #14532d; color: #86efac; }
        [data-theme="dark"] .badge-inactive { background: #27272a; color: var(--text-faint); }

        /* ── Course progress cards ── */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 14px;
        }

        .course-card {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .course-card:hover {
            border-color: #c9c5f8;
        }

        .course-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        .course-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            line-height: 1.3;
            flex: 1;
        }

        .course-meta {
            font-size: 11px;
            color: var(--text-faint);
            margin-top: 3px;
            font-family: var(--profile-mono);
        }

        .pct-badge {
            font-size: 16px;
            font-weight: 700;
            color: var(--accent);
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
            line-height: 1;
        }

        .pct-badge.done { color: #22c55e; }
        .pct-badge.zero { color: var(--text-faint); }

        /* Progress bar */
        .prog-track {
            height: 6px;
            border-radius: 99px;
            background: var(--bg-subtle);
            border: 1px solid var(--border-mid);
            overflow: hidden;
            position: relative;
        }

        .prog-fill {
            height: 100%;
            border-radius: 99px;
            background: var(--accent);
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 0;
        }

        .prog-fill.done { background: #22c55e; }

        .course-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chapters-count {
            font-size: 12px;
            color: var(--text-faint);
        }

        .reset-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-family: var(--profile-font);
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: none;
            color: var(--text-muted);
            cursor: pointer;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }

        .reset-btn:hover {
            background: #fff5f5;
            border-color: #fca5a5;
            color: #c81e1e;
        }

        [data-theme="dark"] .reset-btn:hover { background: #2a1515; border-color: #7f2222; color: #f87171; }

        /* ── Reset all ── */
        .reset-all-zone {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .reset-all-text { font-size: 14px; color: var(--text-muted); }
        .reset-all-text strong { color: var(--text); font-weight: 600; }

        /* ── Flash toast ── */
        .toast {
            position: fixed;
            bottom: 28px;
            left: 50%;
            transform: translateX(-50%) translateY(60px);
            background: var(--text);
            color: var(--bg);
            font-size: 13px;
            font-family: var(--profile-font);
            padding: 10px 20px;
            border-radius: 99px;
            pointer-events: none;
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
            white-space: nowrap;
            z-index: 9999;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* Empty state */
        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: var(--text-faint);
            font-size: 14px;
            border: 1px dashed var(--border);
            border-radius: 12px;
        }

        /* Divider */
        .divider {
            height: 1px;
            background: var(--border-mid);
            margin: 4px 0;
        }

        /* ════════════════════════════════
           SIDEBAR
        ════════════════════════════════ */
        .sb {
            font-family: var(--profile-font);
            display: flex;
            flex-direction: column;
            gap: 0;
            padding: 16px 0 24px;
            height: 100%;
        }

        /* Mini avatar block at top */
        .sb-identity {
            padding: 0 16px 16px;
            border-bottom: 1px solid var(--border-mid);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sb-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent);
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            text-transform: uppercase;
            font-family: var(--profile-font);
        }

        .sb-identity-text { flex: 1; min-width: 0; }
        .sb-identity-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
        }
        .sb-identity-role {
            font-size: 11px;
            color: var(--text-faint);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 2px;
        }

        /* Section group label */
        .sb-group-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            color: var(--text-faint);
            padding: 12px 16px 4px;
        }

        /* Nav item */
        .sb-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            border-radius: 8px;
            margin: 1px 8px;
            cursor: pointer;
            transition: background 0.13s, color 0.13s;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 13.5px;
            font-weight: 400;
            position: relative;
            border: none;
            background: none;
            width: calc(100% - 16px);
            text-align: left;
            font-family: var(--profile-font);
        }

        .sb-item:hover { background: var(--bg-hover); color: var(--text); }

        .sb-item.active {
            background: var(--bg-hover);
            color: var(--text);
            font-weight: 500;
        }

        .sb-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 18px;
            width: 3px;
            border-radius: 0 3px 3px 0;
            background: var(--accent);
        }

        .sb-icon {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            opacity: 0.6;
        }

        .sb-item.active .sb-icon,
        .sb-item:hover .sb-icon { opacity: 1; }

        .sb-item-label { flex: 1; }

        /* Pill badge on nav item */
        .sb-pill {
            font-size: 10px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 99px;
            background: var(--bg-subtlesidebar);
            color: var(--text-faint);
            font-variant-numeric: tabular-nums;
        }

        .sb-pill.accent {
            background: rgba(79, 70, 229, 0.12);
            color: var(--accent);
        }

        /* Thin divider in sidebar */
        .sb-sep {
            height: 1px;
            background: var(--border-mid);
            margin: 8px 16px;
        }

        /* Mini progress ring (SVG inline) */
        .sb-ring-wrap {
            padding: 14px 16px 6px;
        }

        .sb-ring-label {
            font-size: 11px;
            color: var(--text-faint);
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .sb-course-mini {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 0;
        }

        .sb-course-mini-name {
            font-size: 12px;
            color: var(--text-muted);
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sb-mini-bar-wrap {
            width: 56px;
            flex-shrink: 0;
        }

        .sb-mini-track {
            height: 4px;
            border-radius: 99px;
            background: var(--bg-subtlesidebar);
            overflow: hidden;
        }

        .sb-mini-fill {
            height: 100%;
            border-radius: 99px;
            background: var(--accent);
        }

        .sb-mini-fill.done { background: #22c55e; }
        .sb-mini-fill.zero { background: var(--border); }

        .sb-mini-pct {
            font-size: 10px;
            color: var(--text-faint);
            font-variant-numeric: tabular-nums;
            text-align: right;
            margin-top: 1px;
            font-family: var(--profile-mono);
        }

        /* Big donut at top of sidebar */
        .sb-donut-wrap {
            padding: 16px 16px 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .sb-donut-container {
            position: relative;
            width: 80px;
            height: 80px;
        }

        .sb-donut-svg {
            width: 80px;
            height: 80px;
            transform: rotate(-90deg);
        }

        .sb-donut-track {
            fill: none;
            stroke: var(--border-mid);
            stroke-width: 6;
        }

        .sb-donut-fill {
            fill: none;
            stroke: var(--accent);
            stroke-width: 6;
            stroke-linecap: round;
            transition: stroke-dashoffset 0.9s cubic-bezier(0.4,0,0.2,1);
        }

        .sb-donut-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            pointer-events: none;
        }

        .sb-donut-pct {
            font-size: 17px;
            font-weight: 700;
            color: var(--text);
            line-height: 1;
            font-variant-numeric: tabular-nums;
            font-family: var(--profile-font);
        }

        .sb-donut-sub {
            font-size: 9px;
            color: var(--text-faint);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            line-height: 1;
            margin-top: 2px;
        }

        .sb-donut-caption {
            font-size: 12px;
            color: var(--text-faint);
            text-align: center;
        }

        /* Scrollable bottom of sidebar */
        .sb-scroll {
            flex: 1;


            scrollbar-color: var(--border) transparent;
        }
    </style>
@endsection

@section('sidebar-elements')
    @php
        $sbCourses      = \App\Models\course::where('status','published')->get();
        $sbTotal        = $sbCourses->count();
        $sbProgressList = $sbCourses->map(fn($c) => $c->progressForUser($user->id));
        $sbAvg          = $sbTotal > 0 ? round($sbProgressList->sum() / $sbTotal) : 0;
        $sbDone         = $sbProgressList->filter(fn($p) => $p >= 100)->count();
        $sbStarted      = $sbProgressList->filter(fn($p) => $p > 0 && $p < 100)->count();
        $sbNotStarted   = $sbProgressList->filter(fn($p) => $p === 0)->count();
        $circumference  = round(2 * M_PI * 31, 2);
        $sbOffset       = round($circumference - ($sbAvg / 100) * $circumference, 2);
    @endphp

    <div class="sb">

        {{-- Identity --}}
        <div class="sb-identity">
            <div class="sb-avatar">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
            <div class="sb-identity-text">
                <div class="sb-identity-name">{{ $user->name }}</div>
                <div class="sb-identity-role">{{ ($user->is_admin ?? false) ? 'Admin' : 'Student' }}</div>
                <livewire:userstatus :user="$user"/>

            </div>
        </div>

        {{-- Donut --}}
        <div class="sb-donut-wrap">
            <div class="sb-donut-container">
                <svg class="sb-donut-svg" viewBox="0 0 80 80">
                    <circle class="sb-donut-track" cx="40" cy="40" r="31"/>
                    <circle class="sb-donut-fill"
                            cx="40" cy="40" r="31"
                            stroke-dasharray="{{ $circumference }}"
                            stroke-dashoffset="{{ $sbOffset }}"/>
                </svg>
                <div class="sb-donut-center">
                    <div class="sb-donut-pct">{{ $sbAvg }}%</div>
                    <div class="sb-donut-sub">avg</div>
                </div>
            </div>
            <div class="sb-donut-caption">Overall progress</div>
        </div>

        <div class="sb-sep"></div>

        {{-- Quick nav --}}
        <span class="sb-group-label">Navigate</span>

        <a href="{{ route('admin.dashboard') }}" class="sb-item">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            <span class="sb-item-label">All users</span>
        </a>

        <a href="{{ route('admin.courses.index') }}" class="sb-item">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>
            <span class="sb-item-label">Courses</span>
        </a>

        <a href="{{ route('admin.preview.courses') }}" class="sb-item">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <span class="sb-item-label">Preview site</span>
        </a>

        <div class="sb-sep"></div>

        {{-- Progress summary --}}
        <span class="sb-group-label">Progress summary</span>

        <div class="sb-item" style="cursor:default;pointer-events:none;">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <span class="sb-item-label" style="color:var(--text-mid)">Completed</span>
            <span class="sb-pill" style="background:rgba(34,197,94,0.12);color:#16a34a">{{ $sbDone }}</span>
        </div>

        <div class="sb-item" style="cursor:default;pointer-events:none;">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            <span class="sb-item-label" style="color:var(--text-mid)">In progress</span>
            <span class="sb-pill accent">{{ $sbStarted }}</span>
        </div>

        <div class="sb-item" style="cursor:default;pointer-events:none;">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span class="sb-item-label" style="color:var(--text-mid)">Not started</span>
            <span class="sb-pill">{{ $sbNotStarted }}</span>
        </div>

        <div class="sb-sep"></div>

        {{-- Per-course mini bars --}}
        @if($sbTotal > 0)
            <span class="sb-group-label">Courses</span>
            <div class="sb-scroll">
                <div style="padding: 4px 16px 12px;">
                    @foreach($sbCourses as $i => $sbCourse)
                        @php $sbPct = $sbProgressList[$i]; @endphp
                        <div class="sb-course-mini">
                            <span class="sb-course-mini-name" title="{{ $sbCourse->title }}">{{ $sbCourse->title }}</span>
                            <div class="sb-mini-bar-wrap">
                                <div class="sb-mini-track">
                                    <div class="sb-mini-fill {{ $sbPct >= 100 ? 'done' : ($sbPct === 0 ? 'zero' : '') }}"
                                         style="width:{{ $sbPct }}%"></div>
                                </div>
                                <div class="sb-mini-pct">{{ $sbPct }}%</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Danger at bottom --}}
        <div style="padding: 12px 8px 0; margin-top: auto; flex-shrink:0;">
            <div class="sb-sep" style="margin-bottom:8px"></div>
            <button class="sb-item" style="color:#e53e3e" onclick="confirmDelete()">
                <svg class="sb-icon" style="opacity:1" viewBox="0 0 24 24" fill="none" stroke="#e53e3e" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                <span class="sb-item-label">Delete user</span>
            </button>
        </div>

    </div>
@endsection

@section('main')
    <div class="profile-wrap">

        {{-- ── Hero ── --}}
        <div class="hero-card">
            <div class="hero-banner"></div>
            <div class="hero-body">
                <div class="profile-avatar-wrap">
                    <div class="profile-avatar">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
                </div>
                <div class="hero-meta">
                    <h1 class="hero-name">{{ $user->name }}</h1>
                    <p class="hero-email">{{ $user->email }}</p>
                </div>
                <div class="hero-actions">
                    <form method="POST" action="#" id="form-reset-all" style="display:inline">
                        @csrf
                        @method('DELETE')
                    </form>
                    <form method="POST" action="#" id="form-delete" style="display:inline">
                        @csrf
                        @method('DELETE')
                    </form>

                    <button class="btn btn-primary" onclick="confirmResetAll()">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 101.85-5.02"/></svg>
                        Reset all progress
                    </button>
            </div>
        </div>

        {{-- ── Stats ── --}}
        @php
            $courses = \App\Models\course::where('status','published')->get();
            $totalCourses  = $courses->count();
            $progressList  = $courses->map(fn($c) => $c->progressForUser($user->id));
            $completedCount = $progressList->filter(fn($p) => $p >= 100)->count();
            $inProgressCount= $progressList->filter(fn($p) => $p > 0 && $p < 100)->count();
            $avgProgress   = $totalCourses > 0 ? round($progressList->sum() / $totalCourses) : 0;
        @endphp

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-label">Overall progress</span>
                <span class="stat-value">{{ $avgProgress }}<span style="font-size:14px;font-weight:400;color:var(--text-faint)">%</span></span>
                <span class="stat-sub">across all courses</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Completed</span>
                <span class="stat-value">{{ $completedCount }}</span>
                <span class="stat-sub">of {{ $totalCourses }} courses</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">In progress</span>
                <span class="stat-value">{{ $inProgressCount }}</span>
                <span class="stat-sub">course{{ $inProgressCount === 1 ? '' : 's' }} started</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">User ID</span>
                <span class="stat-value" style="font-size:18px;font-family:var(--profile-mono)">#{{ $user->id }}</span>
                <span class="stat-sub">
                <span class="badge {{ $user->email_verified_at ? 'badge-active' : 'badge-inactive' }}">
                    {{ $user->email_verified_at ? 'Verified' : 'Unverified' }}
                </span>
            </span>
            </div>
        </div>

        {{-- ── Account info ── --}}
        <div>
            <div class="section-header">
                <span class="section-title">Account information</span>
            </div>
            <div class="info-card">
                <div class="info-row">
                    <span class="info-key">Full name</span>
                    <span class="info-val">{{ $user->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-key">Email</span>
                    <span class="info-val">{{ $user->email }}</span>
                </div>
                <div class="info-row">
                    <span class="info-key">Joined</span>
                    <span class="info-val">{{ $user->created_at ? $user->created_at->format('d M Y, H:i') : '—' }}</span>
                </div>
                <livewire:userlastupdated :user="$user"/>
                <div class="info-row">
                    <span class="info-key">Role</span>
                    <span class="info-val">
                    <span class="badge {{ $user->is_admin ?? false ? 'badge-active' : 'badge-inactive' }}">
                        {{ ($user->is_admin ?? false) ? 'Admin' : 'Student' }}
                    </span>
                </span>
                </div>
            </div>
        </div>

        {{-- ── Course progress ── --}}
        <div>
            <div class="section-header">
                <span class="section-title">Course progress</span>
                @if($totalCourses > 0)
                    <button class="btn btn-danger" onclick="confirmResetAll()">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 101.85-5.02"/></svg>
                        Reset all progress
                    </button>
                @endif
            </div>

            @if($totalCourses === 0)
                <div class="empty-state">No published courses found.</div>
            @else
                <div class="courses-grid">
                    @foreach($courses as $course)
                        @php $pct = $course->progressForUser($user->id); @endphp
                        <div class="course-card" id="course-card-{{ $course->id }}">
                            <div class="course-top">
                                <div>
                                    <div class="course-name">{{ $course->title }}</div>
                                    <div class="course-meta">{{ $course->year ?? '—' }} · {{ $course->branch ?? '—' }}</div>
                                </div>
                                <span class="pct-badge {{ $pct >= 100 ? 'done' : ($pct === 0 ? 'zero' : '') }}" id="pct-{{ $course->id }}">
                            {{ $pct }}%
                        </span>
                            </div>
                            <div class="prog-track">
                                <div class="prog-fill {{ $pct >= 100 ? 'done' : '' }}"
                                     style="width: {{ $pct }}%"
                                     id="fill-{{ $course->id }}">
                                </div>
                            </div>
                            <div class="course-footer">
                        <span class="chapters-count">
                            {{ $course->chapters()->where('status','published')->count() }}
                            chapter{{ $course->chapters()->where('status','published')->count() === 1 ? '' : 's' }}
                        </span>
                                @if($pct > 0)
                                    <button class="reset-btn" onclick="resetCourse({{ $course->id }}, '{{ addslashes($course->title) }}')">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 101.85-5.02"/></svg>
                                        Reset
                                    </button>
                                @else
                                    <span style="font-size:12px;color:var(--text-faint)">Not started</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Danger zone ── --}}
        <div>
            <div class="section-header">
                <span class="section-title">Danger zone</span>
            </div>
            <div class="reset-all-zone">
                <div>
                    <strong class="reset-all-text"><strong>Delete this account</strong></div>
                <div class="reset-all-text" style="margin-top:4px;font-size:13px;">Permanently removes the user and all associated data. This cannot be undone.</div>
            </div>
            <button class="btn btn-danger" onclick="confirmDelete()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                Delete user
            </button>
        </div>
    </div>

    </div>

    {{-- Toast --}}
    <div class="toast" id="toast"></div>
@endsection

@section('js')
    <script>
        function showToast(msg) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        function resetCourse(courseId, title) {
            if (!confirm(`Reset progress for "${title}"?\n\nThis will clear all lesson progress for this user in this course.`)) return;

            axios.post(`/admin/users/{{ $user->id }}/reset-course/${courseId}`, {
                _token: '{{ csrf_token() }}'
            }).then(() => {
                const fill = document.getElementById('fill-' + courseId);
                const pct  = document.getElementById('pct-'  + courseId);
                if (fill) { fill.style.width = '0%'; fill.classList.remove('done'); }
                if (pct)  { pct.textContent = '0%'; pct.className = 'pct-badge zero'; }

                const footer = document.querySelector(`#course-card-${courseId} .course-footer`);
                if (footer) {
                    const btn = footer.querySelector('.reset-btn');
                    if (btn) btn.outerHTML = '<span style="font-size:12px;color:var(--text-faint)">Not started</span>';
                }

                showToast(`Progress reset for "${title}"`);
            }).catch(() => showToast('Something went wrong. Try again.'));
        }

        function confirmResetAll() {
            if (!confirm('Reset ALL course progress for {{ addslashes($user->name) }}?\n\nEvery lesson will be marked as incomplete. This cannot be undone.')) return;

            axios.post('/admin/users/{{ $user->id }}/reset-all', {
                _token: '{{ csrf_token() }}'
            }).then(() => {
                document.querySelectorAll('[id^="fill-"]').forEach(el => {
                    el.style.width = '0%';
                    el.classList.remove('done');
                });
                document.querySelectorAll('[id^="pct-"]').forEach(el => {
                    el.textContent = '0%';
                    el.className = 'pct-badge zero';
                });
                document.querySelectorAll('.reset-btn').forEach(btn => {
                    btn.outerHTML = '<span style="font-size:12px;color:var(--text-faint)">Not started</span>';
                });
                showToast('All progress has been reset');
            }).catch(() => showToast('Something went wrong. Try again.'));
        }

        function confirmDelete() {
            if (!confirm('Delete {{ addslashes($user->name) }}?\n\nThis permanently removes the account and cannot be undone.')) return;
            document.getElementById('form-delete').submit();
        }
    </script>
@endsection
