@extends('layouts.edditor')

@section('css')
    <style>
        .dash-welcome { margin-bottom: 24px; }
        .dash-welcome h1 { font-size: 20px; font-weight: 500; color: var(--text); }
        .dash-welcome p  { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

        .dash-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--bg-subtle);
            border-radius: 8px;
            padding: 14px 16px;
        }

        .stat-label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 6px;
        }

        .stat-val {
            font-size: 26px;
            font-weight: 500;
            color: var(--text);
        }

        .stat-sub {
            font-size: 11px;
            color: var(--text-faint);
            margin-top: 4px;
        }

        .dash-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
        }

        .dash-card {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px;
        }

        .dash-card-title {
            font-size: 13px;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        /* Quick links */
        .quick-links { display: flex; flex-direction: column; gap: 8px; }

        .qlink {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 7px;
            text-decoration: none;
            transition: background .13s;
        }

        .qlink:hover { background: var(--bg-hover); }

        .qlink-icon {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 14px;
        }

        .qlink-title { font-size: 13px; font-weight: 500; color: var(--text); }
        .qlink-desc  { font-size: 11px; color: var(--text-muted); }
        .qlink-arrow { font-size: 14px; color: var(--text-faint); margin-left: auto; }

        /* User list */
        .user-list { display: flex; flex-direction: column; }

        .user-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-mid);
        }

        .user-row:last-child { border-bottom: none; }

        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #EEEDFE;
            color: #3C3489;
            font-size: 11px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            text-transform: uppercase;
        }

        [data-theme="dark"] .user-avatar { background: #3C3489; color: #CECBF6; }

        .user-name  { font-size: 12.5px; font-weight: 500; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-email { font-size: 11px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .role-badge {
            font-size: 10px;
            padding: 2px 7px;
            border-radius: 999px;
            font-weight: 500;
            background: #E6F1FB;
            color: #0C447C;
            flex-shrink: 0;
        }

        [data-theme="dark"] .role-badge { background: #0C447C; color: #B5D4F4; }
    </style>
@endsection

@section('main')
    <div class="dash-welcome">
        <h1>Good morning, {{ $name }}</h1>
        <p>Here's what's happening on your platform.</p>
    </div>

    <livewire:dashboardstats :user="$user"/>

    <div class="dash-grid">
        <div class="dash-card">
            <div class="dash-card-title">Quick actions</div>
            <div class="quick-links">
                <a class="qlink" href="{{ route('admin.courses.index') }}">
                    <div class="qlink-icon" style="background:#EEEDFE;">📚</div>
                    <div>
                        <div class="qlink-title">Manage courses</div>
                        <div class="qlink-desc">Create, edit, publish courses</div>
                    </div>
                    <span class="qlink-arrow">›</span>
                </a>
                <a class="qlink" href="{{ route('admin.dashboard') }}">
                    <div class="qlink-icon" style="background:#E6F1FB;">👥</div>
                    <div>
                        <div class="qlink-title">Manage users</div>
                        <div class="qlink-desc">View and delete accounts</div>
                    </div>
                    <span class="qlink-arrow">›</span>
                </a>
                <a class="qlink" href="{{ route('admin.preview.courses') }}">
                    <div class="qlink-icon" style="background:#EAF3DE;">👁</div>
                    <div>
                        <div class="qlink-title">Preview site</div>
                        <div class="qlink-desc">See the student-facing view</div>
                    </div>
                    <span class="qlink-arrow">›</span>
                </a>
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-card-title">Recent users</div>
            <div class="user-list">
                @foreach($recentUsers as $user)
                    <div class="user-row">
                        <div class="user-avatar">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div class="user-name">{{ $user->name }}</div>
                            <div class="user-email">{{ $user->email }}</div>
                        </div>
                        <span class="role-badge">{{ ucfirst($user->role ?? 'student') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@section('sidebar-elements')
    <div style="padding:16px 12px;display:flex;flex-direction:column;gap:16px;">

        <div>
            <div style="font-size:10px;font-weight:500;text-transform:uppercase;letter-spacing:.06em;color:var(--text-faint);margin-bottom:8px;">
                Platform overview
            </div>
            <div style="display:flex;flex-direction:column;gap:3px;" x-data="{
                        totalUsers: '{{ $totalUsers }}',
                        totalCourses: '{{ $totalCourses }}',
                        pubLessons: '{{ $pubLessons }}',
                        draftLessons: '{{ $draftLessons }}'
                    }"
                             @stats-updated.window="
                        totalUsers = $event.detail[0].totalUsers;
                        totalCourses = $event.detail[0].totalCourses;
                        pubLessons = $event.detail[0].pubLessons;
                        draftLessons = $event.detail[0].draftLessons;">

                <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 6px;color:var(--text-muted);" >
                    <span>Users</span><span style="font-weight:500;color:var(--text);" x-text="totalUsers">{{ $totalUsers }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 6px;color:var(--text-muted);">
                    <span>Courses</span><span style="font-weight:500;color:var(--text);" x-text="totalCourses">{{ $totalCourses }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 6px;color:var(--text-muted);">
                    <span>Published</span><span style="font-weight:500;color:#065f46;" x-text="pubLessons">{{ $pubLessons }} lessons</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 6px;color:var(--text-muted);">
                    <span>Drafts</span><span style="font-weight:500;color:#6b7280;" x-text="draftLessons">{{ $draftLessons }} lessons</span>
                </div>
            </div>
        </div>

        <div style="height:1px;background:var(--border);"></div>

        <div>
            <div style="font-size:10px;font-weight:500;text-transform:uppercase;letter-spacing:.06em;color:var(--text-faint);margin-bottom:8px;">
                Navigate
            </div>
            <div style="display:flex;flex-direction:column;gap:3px;">
                <a href="{{ route('admin.courses.index') }}" style="padding:6px 8px;border-radius:6px;font-size:13px;color:var(--text-muted);text-decoration:none;transition:background .13s;display:block;" onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">Courses</a>
                <a href="{{ route('admin.dashboard') }}"    style="padding:6px 8px;border-radius:6px;font-size:13px;color:var(--text-muted);text-decoration:none;transition:background .13s;display:block;" onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">Users</a>
                <a href="{{ route('admin.preview.courses') }}" style="padding:6px 8px;border-radius:6px;font-size:13px;color:var(--text-muted);text-decoration:none;transition:background .13s;display:block;" onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">Preview</a>
                <a href="{{ route('admin.calendar') }}"
                   style="padding:6px 8px;border-radius:6px;font-size:13px;color:var(--text-muted);text-decoration:none;display:flex;align-items:center;gap:8px;transition:background .13s;display:block;"
                   onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">
                    <span style="font-size:14px;">📅</span> Calendar
                </a>

            </div>

        </div>

    </div>
@endsection
