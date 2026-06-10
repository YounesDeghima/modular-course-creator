@extends('layouts.app')
@section('css')

    <link rel="stylesheet" href="{{asset('css/modular-site-preview.css')}}">
    <link rel="stylesheet" href="{{asset('css/preview.css')}}">
    <style>
        @if($routePrefix === 'user')
        .enroll-badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 10px; font-weight: 600; letter-spacing: .04em;
            text-transform: uppercase; padding: 3px 8px; border-radius: 99px;
        }
        .badge-enrolled { background: rgba(34,197,94,.12); color: #16a34a; }
        .badge-assigned { background: rgba(79,70,229,.12); color: var(--accent); }
        [data-theme="dark"] .badge-enrolled { background: rgba(34,197,94,.18); color: #4ade80; }
        [data-theme="dark"] .badge-assigned { background: rgba(99,102,241,.22); color: #a5b4fc; }
        .unenroll-btn {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 11px; font-weight: 500; padding: 5px 10px;
            border-radius: 6px; border: 1px solid var(--border);
            background: none; color: var(--text-muted);
            cursor: pointer; transition: background .15s, color .15s, border-color .15s;
        }
        .unenroll-btn:hover { background: #fff5f5; border-color: #fca5a5; color: #c81e1e; }
        [data-theme="dark"] .unenroll-btn:hover { background: #2a1515; border-color: #7f2222; color: #f87171; }
        @endif
        .block-add {
            display: flex; align-items: center; justify-content: center;
            flex-direction: column; gap: 10px; min-height: 180px;
            border: 2px dashed var(--border); border-radius: 12px;
            cursor: pointer; color: var(--text-faint);
            transition: border-color .2s, color .2s, background .2s;
            text-decoration: none;
        }
        .block-add:hover { border-color: var(--accent); color: var(--accent); background: rgba(79,70,229,.04); }
        .block-add-icon {
            width: 40px; height: 40px; border-radius: 50%;
            border: 2px dashed currentColor; display: flex;
            align-items: center; justify-content: center; font-size: 24px; line-height: 1;
        }
        .block-add-label { font-size: 13px; font-weight: 500; }
    </style>
@endsection

@section('navigation')
    <div class="navigation"></div>
@endsection

@section('main')
    <div class="main-header">
        <h2>My Courses</h2>
        <span class="count-badge" id="courseCount">
            {{ count($courses) }} {{ $routePrefix === 'user' ? 'enrolled' : 'courses' }}
        </span>
    </div>

    <div class="blocks-container">
        <div class="blocks" id="blocks-container">

            @php
                $sortedCourses = $routePrefix === 'user'
                    ? $courses->sortBy(function ($course) use ($id) {
                        $p = $course->progressForUser($id);
                        if ($p == 100) return 2;
                        if ($p == 0)   return 1;
                        return 0;
                    })->sortByDesc(function ($course) use ($id) {
                        $p = $course->progressForUser($id);
                        return ($p > 0 && $p < 100) ? $p : -1;
                    })
                    : $courses;
            @endphp

            @foreach($sortedCourses as $course)
                @php $forced = $course->pivot->forced ?? false; @endphp
                <div class="block"
                     data-year="{{ $course->year }}"
                     data-branch="{{ $course->branch }}"
                     data-progress="{{ $course->progressForUser($id) }}"
                     data-title="{{ strtolower($course->title) }}"
                     id="course-block-{{ $course->id }}">

                    <div class="block-top">
                        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:4px;">
                            <span class="year-badge year-{{ $course->year }}">Y{{ $course->year }}</span>
                            <div style="display:flex;gap:5px;align-items:center;">
                                <span class="branch-tag">{{ strtoupper($course->branch) }}</span>
                                @if($routePrefix === 'user')
                                    @if($forced)
                                        <span class="enroll-badge badge-assigned">
                                            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                            Assigned
                                        </span>
                                    @else
                                        <span class="enroll-badge badge-enrolled">
                                            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                            Enrolled
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <div class="info-row">
                            <label>Title</label>
                            <input class="value-input" type="text" value="{{ $course->title }}" readonly>
                        </div>

                        <div class="info-row">
                            <label>Description</label>
                            <textarea class="value-input description" readonly>{{ $course->description }}</textarea>
                        </div>
                    </div>

                    <div>
                        <div class="progress-label">
                            <span>Progress</span>
                            <span>{{ $course->progressForUser($id) }}%</span>
                        </div>
                        <div class="course-progress-bar">
                            <div class="course-progress-fill {{ $course->progressForUser($id) == 100 ? 'done' : '' }}"
                                 data-progress="{{ $course->progressForUser($id) }}">
                            </div>
                        </div>
                    </div>

                    <div class="card-footer" style="display:flex;align-items:center;justify-content:space-between;">
                        <a href="{{ route($routePrefix . '.preview.chapters', ['course' => $course->id]) }}">View chapters</a>
                        @if($routePrefix === 'user' && !$forced)
                            <button class="unenroll-btn" onclick="unenroll({{ $course->id }}, '{{ addslashes($course->title) }}')">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                Leave
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach

            @if($routePrefix === 'user')
                <a href="{{ route('user.courses.browse') }}" class="block-add" title="Browse and enroll in courses">
                    <div class="block-add-icon">+</div>
                    <span class="block-add-label">Browse Courses</span>
                </a>
            @endif

        </div>
    </div>
@endsection

@section('sidebar-elements')
    <div style="padding: 16px 12px; display: flex; flex-direction: column; gap: 16px;">

        <div class="filter-group">
            <label>Search</label>
            <input class="sidebar-search" id="courseSearch" placeholder="Search courses...">
        </div>

        <div class="sidebar-divider"></div>

        <div class="filter-group">
            <label>Status</label>
            <a class="filter-option active" data-filter="status" data-value="all">
                <span class="filter-dot" style="background:#888"></span> All
            </a>
            <a class="filter-option" data-filter="status" data-value="progress">
                <span class="filter-dot" style="background:#378ADD"></span> In progress
            </a>
            <a class="filter-option" data-filter="status" data-value="done">
                <span class="filter-dot" style="background:#639922"></span> Completed
            </a>
        </div>

        <div class="sidebar-divider"></div>

        <div class="filter-group">
            <label>Year</label>
            <a class="filter-option active" data-filter="year" data-value="all">All years</a>
            <a class="filter-option" data-filter="year" data-value="1">Year 1</a>
            <a class="filter-option" data-filter="year" data-value="2">Year 2</a>
            <a class="filter-option" data-filter="year" data-value="3">Year 3</a>
        </div>

        <div class="sidebar-divider"></div>

        <div class="filter-group">
            <label>Branch</label>
            <a class="filter-option active" data-filter="branch" data-value="all">All branches</a>
            <a class="filter-option" data-filter="branch" data-value="st">ST</a>
            <a class="filter-option" data-filter="branch" data-value="mi">MI</a>
        </div>

    </div>
@endsection

@section('js')
    <script>
        const cards   = document.querySelectorAll('.block[data-title]');
        const countEl = document.getElementById('courseCount');
        let filters   = { year: 'all', branch: 'all', status: 'all', search: '' };

        // Animate progress bars
        cards.forEach(card => {
            const fill = card.querySelector('.course-progress-fill');
            if (fill) setTimeout(() => { fill.style.width = fill.dataset.progress + '%'; }, 50);
        });

        function applyFilters() {
            let visible = 0;
            cards.forEach(card => {
                const matchYear   = filters.year   === 'all' || card.dataset.year   == filters.year;
                const matchBranch = filters.branch === 'all' || card.dataset.branch === filters.branch;
                const matchSearch = card.dataset.title.includes(filters.search);
                const pct         = parseInt(card.dataset.progress);
                const matchStatus =
                    filters.status === 'all' ||
                    (filters.status === 'done'     && pct === 100) ||
                    (filters.status === 'progress' && pct > 0 && pct < 100);

                const show = matchYear && matchBranch && matchSearch && matchStatus;
                card.style.display = show ? 'flex' : 'none';
                if (show) visible++;
            });
            countEl.textContent = visible + ' {{ $routePrefix === "user" ? "enrolled" : "courses" }}';
        }

        document.querySelectorAll('.filter-option').forEach(opt => {
            opt.addEventListener('click', e => {
                e.preventDefault();
                const type = opt.dataset.filter;
                document.querySelectorAll(`[data-filter="${type}"]`).forEach(o => o.classList.remove('active'));
                opt.classList.add('active');
                filters[type] = opt.dataset.value;
                applyFilters();
            });
        });

        document.getElementById('courseSearch').addEventListener('input', e => {
            filters.search = e.target.value.toLowerCase();
            applyFilters();
        });

        @if($routePrefix === 'user')
        function unenroll(courseId, title) {
            if (!confirm(`Leave "${title}"?\n\nYour progress will be saved but you will no longer see this course.`)) return;

            fetch(`/user/courses/${courseId}/enroll`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        const block = document.getElementById('course-block-' + courseId);
                        if (block) block.remove();
                        const remaining = document.querySelectorAll('.block[data-title]');
                        countEl.textContent = [...remaining].filter(c => c.style.display !== 'none').length + ' enrolled';
                    } else {
                        alert(data.message || 'Could not leave this course.');
                    }
                })
                .catch(() => alert('Something went wrong.'));
        }
        @endif
    </script>
@endsection
