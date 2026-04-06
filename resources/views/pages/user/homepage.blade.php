@extends('layouts.user-base')

@section('css')
    <style>
        /* ── Welcome ── */
        .dash-welcome { margin-bottom: 24px; }
        .dash-welcome h1 { font-size: 20px; font-weight: 500; color: var(--text); }
        .dash-welcome p  { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

        /* ── Stats ── */
        .dash-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 28px;
        }

        .stat-card { background: var(--bg-subtle); border-radius: 8px; padding: 14px 16px; }
        .stat-label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px; }
        .stat-val   { font-size: 26px; font-weight: 500; color: var(--text); }
        .stat-sub   { font-size: 11px; color: var(--text-faint); margin-top: 4px; }

        /* ── Section header ── */
        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .section-title { font-size: 14px; font-weight: 500; color: var(--text); }
        .see-all { font-size: 12px; color: var(--accent); text-decoration: none; }
        .see-all:hover { color: var(--accent-hover); }

        /* ── Feature grid ── */
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 10px;
            margin-bottom: 32px;
        }

        .feature-card {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            text-decoration: none;
            transition: border-color .13s;
            cursor: pointer;
        }

        .feature-card:hover { border-color: var(--accent); }
        .feature-card.disabled { opacity: .4; cursor: not-allowed; pointer-events: none; }

        .feat-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .feat-title { font-size: 13px; font-weight: 500; color: var(--text); }
        .feat-desc  { font-size: 11px; color: var(--text-muted); line-height: 1.4; }
        .feat-arrow { font-size: 13px; color: var(--text-faint); margin-top: auto; }

        /* ── Course grid ── */
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

        .cc-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
        }

        .cc-title { font-size: 13.5px; font-weight: 500; color: var(--text); line-height: 1.4; }

        .y-badge { font-size: 10px; padding: 2px 7px; border-radius: 999px; font-weight: 500; flex-shrink: 0; }
        .year-1  { background: #E6F1FB; color: #0C447C; }
        .year-2  { background: #EEEDFE; color: #3C3489; }
        .year-3  { background: #FAEEDA; color: #633806; }

        [data-theme="dark"] .year-1 { background: #0C447C; color: #B5D4F4; }
        [data-theme="dark"] .year-2 { background: #3C3489; color: #CECBF6; }
        [data-theme="dark"] .year-3 { background: #633806; color: #FAC775; }

        .cc-desc {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .prog-wrap { display: flex; flex-direction: column; gap: 4px; }
        .prog-label { display: flex; justify-content: space-between; font-size: 11px; color: var(--text-faint); }

        .prog-bar { height: 4px; background: var(--bg-subtle); border-radius: 999px; overflow: hidden; }
        .prog-fill { height: 100%; border-radius: 999px; background: var(--accent); width: 0%; transition: width .6s ease; }
        .prog-fill.done { background: #639922; }

        .cc-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 8px;
            border-top: 1px solid var(--border);
        }

        .branch-tag { font-size: 11px; color: var(--text-muted); background: var(--bg-subtle); padding: 2px 7px; border-radius: 4px; }
        .cc-link    { font-size: 12px; color: var(--accent); font-weight: 500; }
    </style>
@endsection

@section('sidebar-elements')
    <div style="padding:16px 12px;display:flex;flex-direction:column;gap:16px;">

        {{-- User info --}}
        <div style="display:flex;align-items:center;gap:10px;padding:12px;background:var(--bg-hover);border-radius:8px;">
            <div style="width:36px;height:36px;border-radius:50%;background:#EEEDFE;color:#3C3489;font-size:14px;font-weight:500;display:flex;align-items:center;justify-content:center;flex-shrink:0;text-transform:uppercase;">
                {{ strtoupper(substr($name, 0, 1)) }}
            </div>
            <div style="min-width:0;">
                <div style="font-size:13px;font-weight:500;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $name }} {{ $last_name }}</div>
                <div style="font-size:11px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $email }}</div>
            </div>
        </div>

        <div style="height:1px;background:var(--border);"></div>

        {{-- Progress summary --}}
        <div>
            <div style="font-size:10px;font-weight:500;text-transform:uppercase;letter-spacing:.06em;color:var(--text-faint);margin-bottom:8px;">My progress</div>
            <div style="display:flex;flex-direction:column;gap:3px;">
                <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 6px;color:var(--text-muted);">
                    <span>Total courses</span>
                    <span style="font-weight:500;color:var(--text);">{{ $total }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 6px;color:var(--text-muted);">
                    <span>Completed</span>
                    <span style="font-weight:500;color:#3B6D11;">{{ $completed }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 6px;color:var(--text-muted);">
                    <span>In progress</span>
                    <span style="font-weight:500;color:var(--accent);">{{ $inProgress }}</span>
                </div>
            </div>
        </div>

        <div style="height:1px;background:var(--border);"></div>

        {{-- Quick nav --}}
        <div>
            <div style="font-size:10px;font-weight:500;text-transform:uppercase;letter-spacing:.06em;color:var(--text-faint);margin-bottom:8px;">Navigate</div>
            <div style="display:flex;flex-direction:column;gap:3px;">
                <a href="{{ route('user.preview.courses') }}"
                   style="padding:7px 8px;border-radius:6px;font-size:13px;color:var(--text-muted);text-decoration:none;display:flex;align-items:center;gap:8px;transition:background .13s;"
                   onmouseover="this.style.background='var(--bg-hover)'"
                   onmouseout="this.style.background=''">
                    <span style="font-size:14px;">📚</span> Courses
                </a>

                <a href="{{ route('admin.calendar') }}"
                   style="padding:6px 8px;border-radius:6px;font-size:13px;color:var(--text-muted);text-decoration:none;display:flex;align-items:center;gap:8px;transition:background .13s;display:block;"
                   onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">
                    <span style="font-size:14px;">📅</span> Calendar
                </a>
                {{-- Add more nav items here as you build new features --}}
            </div>
        </div>

    </div>
@endsection

@section('main')
    <div class="dash-welcome">
        <h1>Welcome back, {{ $name }}</h1>
        <p>Pick up where you left off, or explore something new.</p>
    </div>

    {{-- Stats --}}
    <div class="dash-stats">
        <div class="stat-card">
            <div class="stat-label">Total courses</div>
            <div class="stat-val">{{ $total }}</div>
            <div class="stat-sub">published courses</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Completed</div>
            <div class="stat-val">{{ $completed }}</div>
            <div class="stat-sub">fully finished</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">In progress</div>
            <div class="stat-val">{{ $inProgress }}</div>
            <div class="stat-sub">keep going</div>
        </div>
    </div>

    <div class="section-head" style="margin-top:24px;">
        <span class="section-title">Schedule & Activity</span>
        <a class="see-all" href="{{ route('user.calendar') }}">View Calendar ›</a>
    </div>

    <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 32px;">

        {{-- 1. THINGS HAPPENING NOW --}}
        {{-- 1. THINGS HAPPENING TODAY --}}

        <div style="display none ">
            <div>
            @foreach($currentEvents as $event)
                <div style="display: flex; align-items: center; gap: 12px; background: #f0f7ff; border: 1px solid #cfe2ff; padding: 12px 16px; border-radius: 10px; margin-bottom: 8px;">
                    {{-- Changed icon/color to blue for "Today" schedule feel --}}
                    <div style="font-size: 18px;">📅</div>

                    <div style="flex-grow: 1;">
                        <div style="font-size: 10px; font-weight: 700; color: #084298; text-transform: uppercase; letter-spacing: 0.5px;">Scheduled Today</div>
                        <div style="font-size: 14px; font-weight: 600; color: var(--text);">{{ $event->title }}</div>
                    </div>

                    <div style="text-align: right;">
                <span style="font-size: 11px; color: var(--text-muted); font-weight: 500;">
                    {{ $event->start_date->format('H:i') }} - {{ $event->end_date->format('H:i') }}
                </span>
                    </div>
                </div>
            @endforeach
            </div>
            {{-- 2. UPCOMING EVENTS (The Next 5) --}}
            <div style="background: var(--bg-subtle); border-radius: 10px; padding: 4px 0;">
                @forelse($upcomingEvents as $event)
                    <div style="display: flex; align-items: center; gap: 15px; padding: 12px 16px; {{ !$loop->last ? 'border-bottom: 1px solid var(--border);' : '' }}">
                        <div style="background: var(--bg); border-radius: 6px; padding: 4px 8px; min-width: 45px; text-align: center; border: 1px solid var(--border);">
                            <div style="font-size: 9px; font-weight: 700; color: var(--text-faint); text-transform: uppercase;">{{ $event->start_date->format('M') }}</div>
                            <div style="font-size: 15px; font-weight: 600; color: var(--text);">{{ $event->start_date->format('d') }}</div>
                        </div>

                        <div style="flex-grow: 1;">
                            <div style="font-size: 13px; font-weight: 500; color: var(--text);">{{ $event->title }}</div>
                            <div style="font-size: 11px; color: var(--text-muted);">
                                {{ $event->start_date->format('H:i') }} • {{ $event->location ?? 'General' }}
                            </div>
                        </div>

                        <span style="font-size: 14px; color: var(--text-faint);">›</span>
                    </div>
                @empty
                    @if($currentEvents->isEmpty())
                        <div style="padding: 20px; text-align: center; font-size: 12px; color: var(--text-faint);">
                            No events scheduled for today.
                        </div>
                    @endif
                @endforelse
            </div>
        </div>

    </div>

    {{-- Feature cards --}}
    <div class="section-head">
        <span class="section-title">Quick access</span>
    </div>
    <div class="feature-grid">
        <a class="feature-card" href="{{ route('user.preview.courses') }}">
            <div class="feat-icon" style="background:#EEEDFE;">📚</div>
            <div class="feat-title">Courses</div>
            <div class="feat-desc">Browse all published courses</div>
            <span class="feat-arrow">›</span>
        </a>
        {{-- Placeholder cards for future features --}}

        <a class="feature-card" href="{{ route('user.calendar') }}">
            <div class="feat-icon" style="background:#E1F5EE;">📅</div>
            <div class="feat-title">Calendar</div>
            <div class="feat-desc">Events, exams, deadlines</div>
            <span class="feat-arrow">›</span>
        </a>

        <div class="feature-card disabled">
            <div class="feat-icon" style="background:#F1EFE8;">🔔</div>
            <div class="feat-title">Notifications</div>
            <div class="feat-desc">Coming soon</div>
            <span class="feat-arrow">›</span>
        </div>
        <div class="feature-card disabled">
            <div class="feat-icon" style="background:#E1F5EE;">📊</div>
            <div class="feat-title">My progress</div>
            <div class="feat-desc">Coming soon</div>
            <span class="feat-arrow">›</span>
        </div>
        <div class="feature-card disabled">
            <div class="feat-icon" style="background:#FAEEDA;">👤</div>
            <div class="feat-title">Profile</div>
            <div class="feat-desc">Coming soon</div>
            <span class="feat-arrow">›</span>
        </div>
    </div>

    {{-- Course cards --}}
    <div class="section-head" style="margin-top:8px;">
        <span class="section-title">Continue learning</span>
        <a class="see-all" href="{{ route('user.preview.courses') }}">See all ›</a>
    </div>

    <div class="course-grid">
        @foreach($courses as $course)
            @php
                $progress = $course->progressForUser($id);
                $isDone   = $progress == 100;
            @endphp
            <a class="course-card"
               href="{{ route('user.preview.chapters', ['course' => $course->id]) }}">
                <div class="cc-top">
                    <span class="cc-title">{{ $course->title }}</span>
                    <span class="y-badge year-{{ $course->year }}">Y{{ $course->year }}</span>
                </div>
                <p class="cc-desc">{{ $course->description }}</p>
                <div class="prog-wrap">
                    <div class="prog-label">
                        <span>Progress</span>
                        <span>{{ $progress }}%</span>
                    </div>
                    <div class="prog-bar">
                        <div class="prog-fill {{ $isDone ? 'done' : '' }}"
                             data-progress="{{ $progress }}">
                        </div>
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
@endsection

@section('js')
    <script>
        document.querySelectorAll('.prog-fill').forEach(fill => {
            setTimeout(() => {
                fill.style.width = fill.dataset.progress + '%';
            }, 50);
        });
    </script>
@endsection
