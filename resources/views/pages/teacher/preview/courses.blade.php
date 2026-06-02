@extends('layouts.edditor')
@section('css')

    <link rel="stylesheet" href="{{asset('css/modular-site-preview.css')}}">
    <link rel="stylesheet" href="{{asset('css/preview.css')}}">
@endsection
@section('navigation')
    <div class="navigation">

    </div>
@endsection
@section('main')
    <div class="main-header">
        <h2>My courses</h2>
        <span class="count-badge" id="courseCount">{{ count($courses) }} courses</span>
    </div>

    <div class="blocks-container">
        <div class="blocks" id="blocks-container">
            @foreach($courses as $course)
                <div class="block"
                     data-year="{{ $course->year }}"
                     data-branch="{{ $course->branch }}"
                     data-progress="{{ $course->progressForUser($id) }}"
                     data-title="{{ strtolower($course->title) }}">

                    <div class="block-top">
                        <div style="display:flex; align-items:center; justify-content:space-between;">
                            <span class="year-badge year-{{ $course->year }}">Y{{ $course->year }}</span>
                            <span class="branch-tag">{{ strtoupper($course->branch) }}</span>
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

                    <div class="card-footer">
                        <a href="{{ route('admin.preview.chapters', ['course' => $course->id]) }}">View chapters</a>
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
        document.querySelectorAll('.filter-form').forEach(form => {
            const year = form.querySelector('.year-input');
            const branch = form.querySelector('.branch-input');


            function toggleBranch() {
                if (parseInt(year.value) > 1 || year.value == '') {

                    branch.style.visibility = 'visible';

                } else {
                    branch.value='';
                    branch.style.visibility = 'hidden';

                }
            }

            year.addEventListener('change', toggleBranch);

            // run once on load
            toggleBranch();
        });




        let courses = document.querySelectorAll('.blocks>.block');
        courses.forEach((course,i)=>{
            let progressFill = course.querySelector('.course-progress-fill');
            let progress = progressFill.dataset.progress;

            setTimeout(() => {
                progressFill.style.width = progress + '%';
            }, 50);
        })

        function confirmReset() {
            return confirm("Are you sure you want to reset this chapter's progress? This action cannot be undone.");
        }

        const cards = document.querySelectorAll('.block');
        const countEl = document.getElementById('courseCount');
        let filters = { year: 'all', branch: 'all', status: 'all', search: '' };

        function applyFilters() {
            let visible = 0;
            cards.forEach(card => {
                const year = card.dataset.year;
                const branch = card.dataset.branch;
                const progress = parseInt(card.dataset.progress);
                const title = card.dataset.title;

                const matchYear = filters.year === 'all' || year == filters.year;
                const matchBranch = filters.branch === 'all' || branch === filters.branch;
                const matchSearch = title.includes(filters.search);
                const matchStatus =
                    filters.status === 'all' ||
                    (filters.status === 'done' && progress === 100) ||
                    (filters.status === 'progress' && progress < 100);

                const show = matchYear && matchBranch && matchSearch && matchStatus;
                card.style.display = show ? 'flex' : 'none';
                if (show) visible++;
            });
            countEl.textContent = visible + ' courses';
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

        // Animate progress bars on load
        cards.forEach(card => {
            const fill = card.querySelector('.course-progress-fill');
            setTimeout(() => { fill.style.width = fill.dataset.progress + '%'; }, 50);
        });



    </script>
@endsection

