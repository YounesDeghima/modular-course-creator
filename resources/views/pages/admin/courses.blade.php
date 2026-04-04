@extends('layouts.edditor')
@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{asset('css/modular-site-preview.css')}}">
    <link rel="stylesheet" href="{{asset('css/modular-site-courses.css')}}">
@endsection


{{--// TODO: fix this whole vew--}}

@section('main')
    <div class="admin-main-top">
        <h2>Courses</h2>
    </div>

    {{-- New course popup --}}
    <div class="block-adder">
        <div id="block-popup">
            <form id="new-block-form" method="POST" action="{{ route('admin.courses.store') }}">
                @csrf
                <div>
                    <label>Title</label>
                    <input class="value-input" type="text" name="title" required>
                </div>
                <div style="display:flex;gap:10px;">
                    <div style="flex:1;">
                        <label>Year</label>
                        <select name="year" class="year-input">
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3">Year 3</option>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label class="branch-label">Branch</label>
                        <select name="branch" class="branch-input">
                            <option value="mi">MI</option>
                            <option value="st">ST</option>
                            <option value="none" style="display:none">None</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label>Description</label>
                    <textarea name="description" required style="min-height:80px;"></textarea>
                </div>
                <div>
                    <label>Status</label>
                    <select name="status">
                        <option value="draft" selected>Draft (hidden)</option>
                        <option value="published">Published (live)</option>
                    </select>
                </div>
                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:4px;">
                    <button type="button" id="close-popup">Cancel</button>
                    <button type="submit">Create course</button>
                </div>
            </form>
        </div>
    </div>

    <div class="blocks" id="blocks-container">
        @foreach($courses as $course)
            <div class="block"
                 data-status="{{ $course->status }}"
                 data-year="{{ $course->year }}">

                <div class="block-meta-row">
                    <span class="year-badge year-{{ $course->year }}">Year {{ $course->year }}</span>
                    <button type="button"
                            class="status-toggle-btn {{ $course->status }}"
                            data-course-id="{{ $course->id }}"
                            data-status="{{ $course->status }}"
                            onclick="toggleSingleCourse(this, event)">
                        {{ ucfirst($course->status) }}
                    </button>
                </div>

                <form class="update-form" action="{{ route('admin.courses.update', $course->id) }}" method="post">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="{{ $course->status }}">

                    <div class="info-row">
                        <label>Title</label>
                        <input class="value-input" type="text" name="title" value="{{ $course->title }}">
                    </div>

                    <div style="display:flex;gap:8px;">
                        <div class="info-row" style="flex:1;">
                            <label>Year</label>
                            <select name="year" class="year-input">
                                <option value="1" {{ $course->year==1?'selected':'' }}>Year 1</option>
                                <option value="2" {{ $course->year==2?'selected':'' }}>Year 2</option>
                                <option value="3" {{ $course->year==3?'selected':'' }}>Year 3</option>
                            </select>
                        </div>
                        <div class="info-row" style="flex:1;">
                            <label class="branch-label">Branch</label>
                            <select name="branch" class="branch-input">
                                <option value="mi" {{ $course->branch=='mi'?'selected':'' }}>MI</option>
                                <option value="st" {{ $course->branch=='st'?'selected':'' }}>ST</option>
                                <option value="none" style="display:none" {{ $course->branch=='none'?'selected':'' }}>None</option>
                            </select>
                        </div>
                    </div>

                    <div class="info-row">
                        <label>Description</label>
                        <textarea name="description" class="value-input" style="min-height:70px;">{{ $course->description }}</textarea>
                    </div>

                    <input class="value-input" type="submit" value="Save changes">
                </form>

                <div class="block-actions">
                    <a class="btn-card-action" href="{{ route('admin.courses.chapters.index', $course) }}">
                        Manage chapters
                    </a>
                    <form action="{{ route('admin.courses.destroy', $course->id) }}" method="post" style="margin-left:auto;">
                        @csrf
                        @method('DELETE')
                        <input type="button"
                               class="btn-card-action danger"
                               onclick="deleteCourse({{ $course->id }}, this)"
                               value="Delete">
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endsection


@section('sidebar-elements')
    <div class="admin-sb-section">
        <button class="admin-new-btn" id="open-popup-btn">+ New course</button>
    </div>

    <div class="admin-sb-divider"></div>

    <div class="admin-sb-section">
        <div class="admin-sb-label">Overview</div>
        <div class="admin-stat-row">
            <span>Total</span>
            <span class="admin-stat-val" id="total-courses">{{ count($courses) }}</span>
        </div>
        <div class="admin-stat-row">
            <span>Published</span>
            <span class="admin-stat-val" id="published-count" style="color:#065f46">
            {{ $courses->where('status','published')->count() }}
        </span>
        </div>
        <div class="admin-stat-row">
            <span>Draft</span>
            <span class="admin-stat-val" id="draft-count" style="color:#6b7280">
            {{ $courses->where('status','draft')->count() }}
        </span>
        </div>
    </div>

    <div class="admin-sb-divider"></div>

    <div class="admin-sb-section">
        <div class="admin-sb-label">Filter</div>
        <button class="admin-filter-btn active" data-filter="all">All courses</button>
        <button class="admin-filter-btn" data-filter="published">Published</button>
        <button class="admin-filter-btn" data-filter="draft">Draft</button>
        <button class="admin-filter-btn" data-filter="year-1">Year 1</button>
        <button class="admin-filter-btn" data-filter="year-2">Year 2</button>
        <button class="admin-filter-btn" data-filter="year-3">Year 3</button>
    </div>

    <div class="admin-sb-divider"></div>

    <div class="admin-sb-section">
        <div class="admin-sb-label">Bulk actions</div>
        <form action="{{ route('admin.courses.toggle-everything') }}" method="POST">
            @csrf
            @method('PUT')
            <button type="submit" class="btn-global-toggle" id="btn-global-toggle">
                🌍 Toggle all visibility
            </button>
        </form>
    </div>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        axios.defaults.headers.common['X-CSRF-TOKEN'] =
            document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const adder = document.getElementById('block-popup');
        const openBtn = document.getElementById('open-popup-btn');
        const closeBtn = document.getElementById('close-popup');

        openBtn.addEventListener('click', () => {
            adder.style.visibility = 'visible';
            adder.style.opacity = 1;
        });

        // ── Branch visibility per form ──
        document.querySelectorAll('.update-form').forEach(form => {
            const year   = form.querySelector('.year-input');
            const branch = form.querySelector('.branch-input');
            const label  = form.querySelector('.branch-label');

            function toggleBranch() {
                const show = parseInt(year.value) > 1;
                branch.style.visibility = show ? 'visible' : 'hidden';
                label.style.visibility  = show ? 'visible' : 'hidden';
            }

            year.addEventListener('change', toggleBranch);
            toggleBranch();
        });

        // Also for the new course popup
        const newYearSelect = document.querySelector('#new-block-form .year-input');
        const newBranchSelect = document.querySelector('#new-block-form .branch-input');
        const newBranchLabel = document.querySelector('#new-block-form .branch-label');

        if (newYearSelect) {
            function toggleNewBranch() {
                const show = parseInt(newYearSelect.value) > 1;
                newBranchSelect.style.visibility = show ? 'visible' : 'hidden';
                newBranchLabel.style.visibility  = show ? 'visible' : 'hidden';
            }
            newYearSelect.addEventListener('change', toggleNewBranch);
            toggleNewBranch();
        }

        // ── Show save button only when form changed ──
        document.querySelectorAll('.update-form').forEach(form => {
            const inputs    = form.querySelectorAll('input, textarea, select');
            const updateBtn = form.querySelector('input[type="submit"]');
            const originals = Array.from(inputs).map(i => i.value);

            updateBtn.style.visibility = 'hidden';

            inputs.forEach((input, idx) => {
                ['input', 'change'].forEach(evt => {
                    input.addEventListener(evt, () => {
                        const changed = Array.from(inputs).some((inp, i) => inp.value !== originals[i]);
                        updateBtn.style.visibility = changed ? 'visible' : 'hidden';
                    });
                });
            });
        });

        // ── Status toggle (FIXED) ──
        function toggleSingleCourse(btn, event) {
            if (event) event.stopPropagation();

            const courseId    = btn.dataset.courseId;
            const currentStatus = btn.dataset.status;
            const newStatus   = currentStatus === 'published' ? 'draft' : 'published';

            updateButtonUI(btn, newStatus);

            // Fix: go up to .block first, then find the form
            const block  = btn.closest('.block');
            const form   = block.querySelector('.update-form');

            // Keep hidden input + data-status in sync for filters
            const hiddenStatus = form.querySelector('input[name="status"]');
            if (hiddenStatus) hiddenStatus.value = newStatus;
            block.dataset.status = newStatus;

            const payload = {
                status:      newStatus,
                title:       form.querySelector('input[name="title"]').value,
                year:        form.querySelector('.year-input').value,
                branch:      form.querySelector('.branch-input').value,
                description: form.querySelector('textarea').value,
            };

            axios.put(`/admin/courses/${courseId}`, payload)
                .then(() => refreshGlobalUI())
                .catch(err => console.error('Toggle failed:', err));
        }

        function updateButtonUI(btn, status) {
            btn.dataset.status = status;
            btn.innerText = status.charAt(0).toUpperCase() + status.slice(1);
            btn.classList.remove('published', 'draft');
            btn.classList.add(status);
        }

        // ── Delete ──
        function deleteCourse(courseId, btn) {
            if (!confirm('Are you sure you want to delete this course?')) return;

            const block = btn.closest('.block');

            axios.delete(`/admin/courses/${courseId}`)
                .finally(() => {
                    block.remove();
                    refreshGlobalUI();
                });
        }

        // ── Refresh sidebar counters ──
        function refreshGlobalUI() {
            const allBtns   = document.querySelectorAll('.status-toggle-btn');
            const allBlocks = document.querySelectorAll('.block');
            let published = 0, draft = 0;

            allBtns.forEach(b => {
                if (b.dataset.status === 'published') published++;
                else draft++;
            });

            const totalEl     = document.getElementById('total-courses');
            const pubEl       = document.getElementById('published-count');
            const draftEl     = document.getElementById('draft-count');

            if (totalEl) totalEl.innerText = allBlocks.length;
            if (pubEl)   pubEl.innerText   = published;
            if (draftEl) draftEl.innerText = draft;

            updateGlobalButtonUI();
        }

        function updateGlobalButtonUI() {
            const globalBtn = document.querySelector('.btn-global-toggle');
            const allBtns   = document.querySelectorAll('.status-toggle-btn');
            if (!globalBtn || !allBtns.length) return;

            const hasAnyDrafts = Array.from(allBtns).some(b => b.dataset.status === 'draft');
            globalBtn.innerText = hasAnyDrafts ? '🚀 Publish all courses' : '📂 Move all to draft';
        }

        document.addEventListener('DOMContentLoaded', updateGlobalButtonUI);

        // ── Popup open/close (FIXED) ──
        document.getElementById('open-popup-btn').addEventListener('click', () => {
            document.getElementById('block-popup').classList.toggle('open');
        });
        document.getElementById('close-popup').addEventListener('click', () => {
            document.getElementById('block-popup').classList.remove('open');
        });

        // ── Sidebar filters (FIXED — reads live data-status) ──
        document.querySelectorAll('.admin-filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.admin-filter-btn')
                    .forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const filter = btn.dataset.filter;

                document.querySelectorAll('.block').forEach(block => {
                    const status = block.dataset.status;
                    const year   = block.dataset.year;

                    const show =
                        filter === 'all' ||
                        (filter === 'published'    && status === 'published') ||
                        (filter === 'draft'        && status === 'draft') ||
                        (filter === `year-${year}`);

                    block.style.display = show ? 'flex' : 'none';
                });
            });
        });
    </script>
@endsection
