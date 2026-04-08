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
    <livewire:coursecreate/>

    <livewire:courses :courses="$courses"/>
@endsection


@section('sidebar-elements')
    <div class="admin-sb-section">
        <button class="admin-new-btn" id="open-popup-btn">+ New course</button>
    </div>

    <div class="admin-sb-divider"></div>

    <livewire:overviewstats/>

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
            <button type="button" class="btn-global-toggle" id="btn-global-toggle">
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
        // document.querySelectorAll('.update-form').forEach(form => {
        //     const year   = form.querySelector('.year-input');
        //     const branch = form.querySelector('.branch-input');
        //     const label  = form.querySelector('.branch-label');
        //
        //     function toggleBranch() {
        //         const show = parseInt(year.value) > 1;
        //         branch.style.visibility = show ? 'visible' : 'hidden';
        //         label.style.visibility  = show ? 'visible' : 'hidden';
        //     }
        //
        //     year.addEventListener('change', toggleBranch);
        //     toggleBranch();
        // });

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
        // document.querySelectorAll('.update-form').forEach(form => {
        //     const inputs    = form.querySelectorAll('input, textarea, select');
        //     const updateBtn = form.querySelector('input[type="submit"]');
        //     const originals = Array.from(inputs).map(i => i.value);
        //
        //     updateBtn.style.visibility = 'hidden';
        //
        //     inputs.forEach((input, idx) => {
        //         ['input', 'change'].forEach(evt => {
        //             input.addEventListener(evt, () => {
        //                 const changed = Array.from(inputs).some((inp, i) => inp.value !== originals[i]);
        //                 updateBtn.style.visibility = changed ? 'visible' : 'hidden';
        //             });
        //         });
        //     });
        // });

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
        // function deleteCourse(courseId, btn) {
        //     if (!confirm('Are you sure you want to delete this course?')) return;
        //
        //     const block = btn.closest('.block');
        //
        //     axios.delete(`/admin/courses/${courseId}`)
        //         .finally(() => {
        //             block.remove();
        //             refreshGlobalUI();
        //         });
        // }

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




        const globalBtn = document.getElementById('btn-global-toggle');

        globalBtn.addEventListener('click', () => {
            const allBlocks = document.querySelectorAll('.block');
            const allBtns   = document.querySelectorAll('.status-toggle-btn');

            if (!allBlocks.length) return;

            // Check if any draft exists
            const hasDraft = Array.from(allBtns).some(b => b.dataset.status === 'draft');

            // Decide target state
            const newStatus = hasDraft ? 'published' : 'draft';

            globalBtn.innerText = "Updating...";
            globalBtn.disabled = true;

            axios.put('/admin/courses/toggle-everything', {
                status: newStatus
            })
                .then(() => {
                    // 🔥 Update ALL UI instantly
                    allBlocks.forEach(block => {
                        const btn  = block.querySelector('.status-toggle-btn');
                        const form = block.querySelector('.update-form');

                        // Update button
                        updateButtonUI(btn, newStatus);

                        // Update dataset (for filters)
                        block.dataset.status = newStatus;

                        // Update hidden input
                        const hidden = form.querySelector('input[name="status"]');
                        if (hidden) hidden.value = newStatus;
                    });

                    refreshGlobalUI();
                })
                .catch(err => {
                    console.error('Bulk toggle failed:', err);
                })
                .finally(() => {
                    globalBtn.disabled = false;
                    updateGlobalButtonUI();
                });
        });
    </script>
@endsection
