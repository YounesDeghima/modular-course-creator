@extends('layouts.user-base')
@section('css')
    <link rel="stylesheet" href="{{asset('css/modular-site-preview.css')}}">
    <link rel="stylesheet" href="{{asset('css/preview.css')}}">
    <style>
        /* Enrolled tag */
        .enrolled-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 99px;
            background: rgba(34,197,94,.12);
            color: #16a34a;
        }
        [data-theme="dark"] .enrolled-tag { background: rgba(34,197,94,.18); color: #4ade80; }

        /* Enroll button */
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
            transition: background .15s, border-color .15s, opacity .15s;
        }
        .enroll-btn:hover    { background: var(--accent-hover); border-color: var(--accent-hover); }
        .enroll-btn:disabled { opacity: .5; cursor: default; }
        .enroll-btn.enrolled {
            background: none;
            border-color: var(--border);
            color: var(--text-faint);
            cursor: default;
        }
    </style>
@endsection

@section('navigation')
    <div class="navigation"></div>
@endsection

@section('main')
    <div class="main-header">
        <h2>Browse Courses</h2>
        <span class="count-badge" id="courseCount">{{ count($courses) }} available</span>
    </div>

    <div class="blocks-container">
        <div class="blocks" id="blocks-container">
            @foreach($courses as $course)
                @php $isEnrolled = isset($enrolledMap[$course->id]); @endphp
                <div class="block"
                     data-year="{{ $course->year }}"
                     data-branch="{{ $course->branch }}"
                     data-title="{{ strtolower($course->title) }}"
                     id="browser-card-{{ $course->id }}">

                    <div class="block-top">
                        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:4px;">
                            <span class="year-badge year-{{ $course->year }}">Y{{ $course->year }}</span>
                            <div style="display:flex;gap:5px;align-items:center;">
                                <span class="branch-tag">{{ strtoupper($course->branch) }}</span>
                                @if($isEnrolled)
                                    <span class="enrolled-tag" id="tag-{{ $course->id }}">
                                        <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        Enrolled
                                    </span>
                                @else
                                    <span class="enrolled-tag" id="tag-{{ $course->id }}" style="display:none;">
                                        <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        Enrolled
                                    </span>
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

                    <div class="card-footer" style="display:flex; align-items:center; justify-content:space-between;">
                        <span style="font-size:12px;color:var(--text-faint)">
                            Y{{ $course->year }} · {{ strtoupper($course->branch) }}
                        </span>
                        <button
                            class="enroll-btn {{ $isEnrolled ? 'enrolled' : '' }}"
                            id="enroll-btn-{{ $course->id }}"
                            onclick="enrollIn({{ $course->id }}, '{{ addslashes($course->title) }}')"
                            {{ $isEnrolled ? 'disabled' : '' }}>
                            @if($isEnrolled)
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Enrolled
                            @else
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Enroll
                            @endif
                        </button>
                    </div>
                </div>
            @endforeach
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
            <label>Enrollment</label>
            <a class="filter-option active" data-filter="enrolled" data-value="all">
                <span class="filter-dot" style="background:#888"></span> All
            </a>
            <a class="filter-option" data-filter="enrolled" data-value="no">
                <span class="filter-dot" style="background:#378ADD"></span> Not enrolled
            </a>
            <a class="filter-option" data-filter="enrolled" data-value="yes">
                <span class="filter-dot" style="background:#639922"></span> Already enrolled
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
    // Track enrolled state in JS so filters work after enrolling without reload
    const enrolledIds = new Set({!! json_encode($enrolledMap->keys()->toArray()) !!});

    const cards   = document.querySelectorAll('.block[data-title]');
    const countEl = document.getElementById('courseCount');
    let filters   = { year: 'all', branch: 'all', enrolled: 'all', search: '' };

    function applyFilters() {
        let visible = 0;
        cards.forEach(card => {
            const courseId    = card.id.replace('browser-card-', '');
            const matchYear   = filters.year   === 'all' || card.dataset.year   == filters.year;
            const matchBranch = filters.branch === 'all' || card.dataset.branch === filters.branch;
            const matchSearch = card.dataset.title.includes(filters.search);
            const isEnrolled  = enrolledIds.has(parseInt(courseId));
            const matchEnroll =
                filters.enrolled === 'all' ||
                (filters.enrolled === 'yes' && isEnrolled) ||
                (filters.enrolled === 'no'  && !isEnrolled);

            const show = matchYear && matchBranch && matchSearch && matchEnroll;
            card.style.display = show ? 'flex' : 'none';
            if (show) visible++;
        });
        countEl.textContent = visible + ' available';
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

    function enrollIn(courseId, title) {
        const btn = document.getElementById('enroll-btn-' + courseId);
        const tag = document.getElementById('tag-' + courseId);
        if (!btn || btn.disabled) return;

        btn.disabled = true;
        btn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Enrolling…';

        fetch(`/user/courses/${courseId}/enroll`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                // Update button
                btn.className = 'enroll-btn enrolled';
                btn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Enrolled';
                btn.disabled = true;
                // Show tag
                if (tag) tag.style.display = 'inline-flex';
                // Track in JS set
                enrolledIds.add(courseId);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Enroll';
                alert('Something went wrong. Try again.');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = 'Enroll';
            alert('Something went wrong.');
        });
    }
</script>
@endsection
