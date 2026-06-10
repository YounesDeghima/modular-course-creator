@extends('layouts.app')

@section('css')
    <style>
        .browser-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px 24px 64px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--text-faint);
            text-decoration: none;
            transition: color .15s;
        }
        .back-link:hover { color: var(--text); }

        .page-heading { font-size: 20px; font-weight: 600; color: var(--text); margin: 0; }
        .page-sub     { font-size: 13px; color: var(--text-faint); margin: 4px 0 0; }

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
            transition: border-color .15s;
        }
        .course-card:hover { border-color: #c9c5f8; }
        .course-card.already { opacity: .7; }

        .course-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }
        .course-name { font-size: 14px; font-weight: 600; color: var(--text); line-height: 1.3; }
        .course-meta { font-size: 11px; color: var(--text-faint); margin-top: 3px; font-family: monospace; }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 99px;
            text-transform: uppercase;
            letter-spacing: .04em;
            white-space: nowrap;
        }
        .tag-enrolled { background: rgba(34,197,94,.12); color: #16a34a; }
        [data-theme="dark"] .tag-enrolled { background: rgba(34,197,94,.18); color: #4ade80; }

        .card-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .enroll-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 500;
            padding: 6px 14px;
            border-radius: 7px;
            border: 1px solid var(--accent);
            background: var(--accent);
            color: #fff;
            cursor: pointer;
            transition: background .15s, border-color .15s;
        }
        .enroll-btn:hover    { background: var(--accent-hover); border-color: var(--accent-hover); }
        .enroll-btn:disabled { opacity: .5; cursor: default; }
        .enroll-btn.enrolled {
            background: none;
            border-color: var(--border);
            color: var(--text-faint);
            cursor: default;
        }

        .toast {
            position: fixed;
            bottom: 28px;
            left: 50%;
            transform: translateX(-50%) translateY(60px);
            background: var(--text);
            color: var(--bg);
            font-size: 13px;
            padding: 10px 20px;
            border-radius: 99px;
            pointer-events: none;
            opacity: 0;
            transition: transform .3s, opacity .3s;
            white-space: nowrap;
            z-index: 9999;
        }
        .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

        .search-bar {
            width: 100%;
            max-width: 320px;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--bg);
            color: var(--text);
            font-size: 13px;
        }
        .search-bar:focus { outline: none; border-color: var(--accent); }
    </style>
@endsection

@section('main')
<div class="browser-wrap">

    <div>
        <a href="{{ route('admin.userProfile', ['userid' => $targetUser->id]) }}" class="back-link">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Back to {{ $targetUser->name }}'s profile
        </a>
    </div>

    <div>
        <h1 class="page-heading">Add Course for {{ $targetUser->name }}</h1>
        <p class="page-sub">Courses you assign are marked as "Assigned" and the student cannot unenroll from them.</p>
    </div>

    <input class="search-bar" id="searchInput" placeholder="Search courses…" oninput="filterCards()">

    <div class="courses-grid" id="grid">
        @foreach($courses as $course)
            @php $alreadyEnrolled = in_array($course->id, $enrolledIds); @endphp
            <div class="course-card {{ $alreadyEnrolled ? 'already' : '' }}"
                 data-title="{{ strtolower($course->title) }}"
                 id="admin-card-{{ $course->id }}">

                <div class="course-top">
                    <div>
                        <div class="course-name">{{ $course->title }}</div>
                        <div class="course-meta">Y{{ $course->year }} · {{ strtoupper($course->branch) }}</div>
                    </div>
                    @if($alreadyEnrolled)
                        <span class="tag tag-enrolled" id="tag-{{ $course->id }}">
                            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Enrolled
                        </span>
                    @else
                        <span class="tag tag-enrolled" id="tag-{{ $course->id }}" style="display:none;">
                            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Enrolled
                        </span>
                    @endif
                </div>

                @if($course->description)
                    <p style="font-size:12px;color:var(--text-faint);margin:0;line-height:1.5;">{{ Str::limit($course->description, 80) }}</p>
                @endif

                <div class="card-footer">
                    <button
                        class="enroll-btn {{ $alreadyEnrolled ? 'enrolled' : '' }}"
                        id="enroll-btn-{{ $course->id }}"
                        onclick="adminEnroll({{ $course->id }}, '{{ addslashes($course->title) }}')"
                        {{ $alreadyEnrolled ? 'disabled' : '' }}>
                        @if($alreadyEnrolled)
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Enrolled
                        @else
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Assign
                        @endif
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="toast" id="toast"></div>
@endsection

@section('js')
<script>
    function filterCards() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('#grid .course-card').forEach(card => {
            card.style.display = card.dataset.title.includes(q) ? 'flex' : 'none';
        });
    }

    function showToast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }

    function adminEnroll(courseId, title) {
        const btn = document.getElementById('enroll-btn-' + courseId);
        const tag = document.getElementById('tag-' + courseId);
        if (!btn || btn.disabled) return;

        btn.disabled = true;
        btn.innerHTML = 'Assigning…';

        fetch(`/admin/users/{{ $targetUser->id }}/courses/${courseId}/enroll`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                btn.className = 'enroll-btn enrolled';
                btn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Enrolled';
                if (tag) tag.style.display = 'inline-flex';
                showToast(`"${title}" assigned to {{ $targetUser->name }}`);
            } else {
                btn.disabled = false;
                btn.innerHTML = 'Assign';
                showToast('Something went wrong.');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = 'Assign';
            showToast('Something went wrong.');
        });
    }
</script>
@endsection
