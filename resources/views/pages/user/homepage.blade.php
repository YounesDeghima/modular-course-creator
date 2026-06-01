@extends('layouts.user-base')

@section('css')
    <style>
        /* ── Page shell ── */
        .dash { display:flex;flex-direction:column;gap:28px;max-width:1100px; }

        /* ── Welcome banner ── */
        .dash-banner {
            background: var(--bg-subtle);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .dash-banner-left h1 {
            font-size: 20px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 4px;
        }
        .dash-banner-left p {
            font-size: 13px;
            color: var(--text-muted);
        }
        .dash-banner-stats {
            display: flex;
            gap: 28px;
            flex-shrink: 0;
        }
        .bstat {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
        }
        .bstat-val {
            font-size: 24px;
            font-weight: 600;
            color: var(--text);
            line-height: 1;
        }
        .bstat-lbl {
            font-size: 11px;
            color: var(--text-faint);
            text-transform: uppercase;
            letter-spacing: .05em;
            white-space: nowrap;
        }
        .bstat-divider {
            width: 1px;
            height: 36px;
            background: var(--border);
            align-self: center;
        }

        /* ── Two-col row ── */
        .dash-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* ── Section headers ── */
        .sec-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .sec-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .sec-link {
            font-size: 12px;
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }
        .sec-link:hover { color: var(--accent-hover); }

        /* ── Event list ── */
        .ev-list { display:flex;flex-direction:column;gap:8px; }
        .ev-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            text-decoration: none;
            transition: border-color .13s, background .13s;
            background: var(--bg);
            position: relative;
            overflow: hidden;
        }
        .ev-item:hover { border-color: var(--accent); background: var(--bg-hover); }
        .ev-accent-bar {
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 3px;
            border-radius: 0;
        }
        .ev-body { flex:1;min-width:0;padding-left:4px; }
        .ev-title {
            font-size: 13px;
            font-weight: 500;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 3px;
        }
        .ev-date { font-size: 11px; color: var(--text-faint); }
        .ev-badge {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 999px;
            font-weight: 500;
            flex-shrink: 0;
        }
        .ev-ongoing {
            font-size: 10px;
            padding: 1px 7px;
            border-radius: 999px;
            font-weight: 500;
            flex-shrink: 0;
        }
        .ev-empty {
            padding: 20px;
            text-align: center;
            border: 1px dashed var(--border);
            border-radius: 10px;
            font-size: 13px;
            color: var(--text-faint);
        }

        /* ── Quick access ── */
        .quick-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        .qcard {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            text-decoration: none;
            transition: border-color .13s, background .13s;
            cursor: pointer;
        }
        .qcard:hover:not(.qcard-disabled) { border-color: var(--accent); background: var(--bg-hover); }
        .qcard-disabled { opacity: .4; cursor: not-allowed; pointer-events: none; }
        .qcard-icon {
            width: 32px;
            height: 32px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        .qcard-title { font-size: 12.5px; font-weight: 500; color: var(--text); }
        .qcard-desc  { font-size: 11px; color: var(--text-muted); line-height: 1.4; }
        .qcard-soon  { font-size: 10px; color: var(--text-faint); margin-top: auto; }

        /* ── Course cards ── */
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
        }
        .course-card {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            text-decoration: none;
            transition: border-color .15s;
        }
        .course-card:hover { border-color: var(--accent); }
        .cc-top { display:flex;align-items:flex-start;justify-content:space-between;gap:8px; }
        .cc-title { font-size: 13.5px; font-weight: 500; color: var(--text); line-height: 1.4; }
        .y-badge { font-size: 10px; padding: 2px 7px; border-radius: 999px; font-weight: 500; flex-shrink: 0; }
        .year-1  { background: #E6F1FB; color: #0C447C; }
        .year-2  { background: #EEEDFE; color: #3C3489; }
        .year-3  { background: #FAEEDA; color: #633806; }
        [data-theme="dark"] .year-1 { background: #0C2D4E; color: #93C5FD; }
        [data-theme="dark"] .year-2 { background: #2A2660; color: #C4B5FD; }
        [data-theme="dark"] .year-3 { background: #3D2200; color: #FCD34D; }
        .cc-desc { font-size: 12px; color: var(--text-muted); line-height: 1.5;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .prog-wrap { display:flex;flex-direction:column;gap:4px; }
        .prog-label { display:flex;justify-content:space-between;font-size:11px;color:var(--text-faint); }
        .prog-bar   { height:4px;background:var(--bg-subtle);border-radius:999px;overflow:hidden; }
        .prog-fill  { height:100%;border-radius:999px;background:var(--accent);width:0%;transition:width .6s ease; }
        .prog-fill.done { background: #639922; }
        .cc-foot { display:flex;align-items:center;justify-content:space-between;padding-top:8px;border-top:1px solid var(--border); }
        .branch-tag { font-size:11px;color:var(--text-muted);background:var(--bg-subtle);padding:2px 7px;border-radius:4px; }
        .cc-link    { font-size:12px;color:var(--accent);font-weight:500; }
    </style>
@endsection

@section('sidebar-elements')
    <div style="padding:16px 12px;display:flex;flex-direction:column;gap:16px;">

        {{-- User card --}}
        <div style="display:flex;align-items:center;gap:10px;padding:12px;background:var(--bg-hover);border-radius:8px;">
            <div style="width:38px;height:38px;border-radius:50%;background:#EEEDFE;color:#3C3489;
                    font-size:15px;font-weight:600;display:flex;align-items:center;
                    justify-content:center;flex-shrink:0;text-transform:uppercase;">
                {{ strtoupper(substr($name, 0, 1)) }}
            </div>
            <div style="min-width:0;">
                <div style="font-size:13px;font-weight:500;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ $name }} {{ $last_name }}
                </div>
                <div style="font-size:11px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ $email }}
                </div>
            </div>
        </div>

        <div style="height:1px;background:var(--border);"></div>

        {{-- Progress --}}
        <div>
            <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-faint);margin-bottom:8px;">My progress</div>

            {{-- Overall bar --}}
            @php $overallPct = $total > 0 ? round(($completed / $total) * 100) : 0; @endphp
            <div style="margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-faint);margin-bottom:4px;">
                    <span>Overall</span><span>{{ $overallPct }}%</span>
                </div>
                <div style="height:5px;background:var(--bg-subtle);border-radius:999px;overflow:hidden;">
                    <div style="height:100%;border-radius:999px;background:var(--accent);width:{{ $overallPct }}%;transition:width .6s;"></div>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:2px;">
                @foreach([
                    ['label'=>'Total',       'val'=>$total,      'color'=>'var(--text)'],
                    ['label'=>'Completed',   'val'=>$completed,  'color'=>'#3B6D11'],
                    ['label'=>'In progress', 'val'=>$inProgress, 'color'=>'var(--accent)'],
                ] as $row)
                    <div style="display:flex;justify-content:space-between;font-size:12px;
                        padding:5px 6px;border-radius:6px;color:var(--text-muted);">
                        <span>{{ $row['label'] }}</span>
                        <span style="font-weight:600;color:{{ $row['color'] }};">{{ $row['val'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div style="height:1px;background:var(--border);"></div>

        {{-- Nav --}}
        <div>
            <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-faint);margin-bottom:8px;">Navigate</div>
            <div style="display:flex;flex-direction:column;gap:2px;">
                @foreach([
                    ['icon'=>'📚','label'=>'Courses',  'route'=>route('user.preview.courses')],
                    ['icon'=>'📅','label'=>'Calendar', 'route'=>route('user.calendar')],
                ] as $nav)
                    <a href="{{ $nav['route'] }}"
                       style="padding:7px 8px;border-radius:6px;font-size:13px;color:var(--text-muted);
                      text-decoration:none;display:flex;align-items:center;gap:8px;transition:background .13s;"
                       onmouseover="this.style.background='var(--bg-hover)'"
                       onmouseout="this.style.background=''">
                        <span>{{ $nav['icon'] }}</span> {{ $nav['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

    </div>
@endsection

@section('main')
    <div class="dash">

        {{-- ── Banner ── --}}
        <div class="dash-banner">
            <div class="dash-banner-left">
                <h1>Welcome back, {{ $name }} 👋</h1>
                <p>{{ now()->format('l, F d Y') }} &nbsp;·&nbsp; Pick up where you left off.</p>
            </div>
            <div class="dash-banner-stats">
                <div class="bstat">
                    <span class="bstat-val">{{ $total }}</span>
                    <span class="bstat-lbl">Courses</span>
                </div>
                <div class="bstat-divider"></div>
                <div class="bstat">
                    <span class="bstat-val" style="color:#3B6D11;">{{ $completed }}</span>
                    <span class="bstat-lbl">Done</span>
                </div>
                <div class="bstat-divider"></div>
                <div class="bstat">
                    <span class="bstat-val" style="color:var(--accent);">{{ $inProgress }}</span>
                    <span class="bstat-lbl">Active</span>
                </div>
            </div>
        </div>

        {{-- ── Two-col: Schedule + Quick access ── --}}
        <div class="dash-row">
            {{-- Quick access --}}

            <div>
                <div class="sec-head">
                    <span class="sec-title">Quick access</span>
                </div>
                <div class="quick-grid">
                    <a class="qcard" href="{{ route('user.preview.courses') }}">
                        <div class="qcard-icon" style="background:#EEEDFE;">📚</div>
                        <div class="qcard-title">Courses</div>
                        <div class="qcard-desc">Browse all published courses</div>
                    </a>
                    <a class="qcard" href="{{ route('user.calendar') }}">
                        <div class="qcard-icon" style="background:#E1F5EE;">📅</div>
                        <div class="qcard-title">Calendar</div>
                        <div class="qcard-desc">Events, exams, deadlines</div>
                    </a>
                    <div class="qcard qcard-disabled">
                        <div class="qcard-icon" style="background:#F1EFE8;">🔔</div>
                        <div class="qcard-title">Notifications</div>
                        <div class="qcard-desc">Stay in the loop</div>
                        <span class="qcard-soon">Coming soon</span>
                    </div>
                    <div class="qcard qcard-disabled">
                        <div class="qcard-icon" style="background:#E1F5EE;">📊</div>
                        <div class="qcard-title">My progress</div>
                        <div class="qcard-desc">Track your learning</div>
                        <span class="qcard-soon">Coming soon</span>
                    </div>
                    <div class="qcard qcard-disabled">
                        <div class="qcard-icon" style="background:#FAEEDA;">👤</div>
                        <div class="qcard-title">Profile</div>
                        <div class="qcard-desc">Manage your account</div>
                        <span class="qcard-soon">Coming soon</span>
                    </div>
                </div>
            </div>

            {{-- Schedule --}}
            <div>
                <div class="sec-head">
                    <span class="sec-title">Upcoming schedule</span>
                    <a class="sec-link" href="{{ route('user.calendar') }}">View calendar ›</a>
                </div>

                @if($upcomingEvents->isEmpty())
                    <div class="ev-empty">No upcoming events — enjoy the calm 🎉</div>
                @else
                    <div class="ev-list">
                        @foreach($upcomingEvents as $event)
                            @php
                                $today     = now()->toDateString();
                                $start     = $event->start_date->toDateString();
                                $end       = $event->end_date?->toDateString();
                                $happening = $start <= $today && ($end >= $today || (!$end && $start === $today));
                                $palette   = [
                                    'exam'       => ['bg'=>'#FCEBEB','text'=>'#A32D2D','bar'=>'#E24B4A'],
                                    'vacation'   => ['bg'=>'#EAF3DE','text'=>'#27500A','bar'=>'#639922'],
                                    'project'    => ['bg'=>'#EEEDFE','text'=>'#3C3489','bar'=>'#7F77DD'],
                                    'assignment' => ['bg'=>'#FAEEDA','text'=>'#633806','bar'=>'#BA7517'],
                                    'personal'   => ['bg'=>'#E6F1FB','text'=>'#0C447C','bar'=>'#378ADD'],
                                ];
                                $c = $palette[$event->type];
                            @endphp
                            <a class="ev-item" href="{{ route('user.calendar') }}">
                                <div class="ev-accent-bar" style="background:{{ $c['bar'] }};"></div>
                                <div class="ev-body">
                                    <div style="display:flex;align-items:center;gap:7px;">
                                        <div class="ev-title">{{ $event->title }}</div>
                                        @if($happening)
                                            <span class="ev-ongoing" style="background:{{ $c['bg'] }};color:{{ $c['text'] }};">Ongoing</span>
                                        @endif
                                    </div>
                                    <div class="ev-date">
                                        @if($end && $end !== $start)
                                            {{ $event->start_date->format('M d') }} – {{ $event->end_date->format('M d, Y') }}
                                        @else
                                            {{ $event->start_date->format('M d, Y') }}
                                        @endif
                                    </div>
                                </div>
                                <span class="ev-badge" style="background:{{ $c['bg'] }};color:{{ $c['text'] }};">
                                {{ ucfirst($event->type) }}
                            </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

        {{-- ── Course cards ── --}}
        <div>
            <div class="sec-head">
                <span class="sec-title">Continue learning</span>
                <a class="sec-link" href="{{ route('user.preview.courses') }}">See all ›</a>
            </div>

            <div class="course-grid">
                @php
                    $courses = $courses->sortBy(function ($course) use ($id) {
                        $p = $course->progressForUser($id);
                        if ($p == 100) return 2;
                        if ($p == 0)   return 1;
                        return 0;
                    })->sortByDesc(function ($course) use ($id) {
                        $p = $course->progressForUser($id);
                        return ($p > 0 && $p < 100) ? $p : -1;
                    });
                @endphp
                @foreach($courses as $course)
                    @php $progress = $course->progressForUser($id); $isDone = $progress == 100; @endphp
                    <a class="course-card" href="{{ route('user.preview.chapters', ['course' => $course->id]) }}">
                        <div class="cc-top">
                            <span class="cc-title">{{ $course->title }}</span>
                            <span class="y-badge year-{{ $course->year }}">Y{{ $course->year }}</span>
                        </div>
                        <p class="cc-desc">{{ $course->description }}</p>
                        <div class="prog-wrap">
                            <div class="prog-label">
                                <span>Progress</span><span>{{ $progress }}%</span>
                            </div>
                            <div class="prog-bar">
                                <div class="prog-fill {{ $isDone ? 'done' : '' }}" data-progress="{{ $progress }}"></div>
                            </div>
                        </div>
                        <div class="cc-foot">
                            @if($course->branch !== 'none')
                                <span class="branch-tag">{{ strtoupper($course->branch) }}</span>
                            @else
                                <span></span>
                            @endif
                            <span class="cc-link">{{ $isDone ? 'Review ›' : 'Continue ›' }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

    </div>
@endsection

@section('js')
    <script>
        document.querySelectorAll('.prog-fill').forEach(fill => {
            setTimeout(() => { fill.style.width = fill.dataset.progress + '%'; }, 50);
        });
    </script>
@endsection
