@extends('layouts.edditor')
@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{asset('css/modular-site-preview.css')}}">
@endsection
@section('main')

    <div class="blocks-container" id="blocks-container">

        <div class="block-adder">
            <button id="block-adder">+</button>
            <div class="popup" id="block-popup">

                <form id="new-block-form" method="POST" action="{{route('admin.courses.store')}}">
                    @csrf
                    <label>Title:</label>
                    <input class="value-input" type="text" name="title" required>

                    <label>Year (1-3):</label>
                    <select name="year" class="year-input">
                        <option value="1">Year 1</option>
                        <option value="2">Year 2</option>
                        <option value="3">Year 3</option>
                    </select>
                    <label class="branch-label">branch:</label>
                    <select name="branch" class="branch-input">
                        <option value="mi">mi</option>
                        <option value="st">st</option>
                        <option value="none" style="display: none">none</option>

                    </select>

                    <label>Description:</label>
                    <textarea class="value-input" name="description" required></textarea>

                    <label>Initial Status:</label>

                    <select name="status" class="year-input">
                        <option value="draft" selected>Draft (Hidden)</option>
                        <option value="published">Published (Live)</option>
                    </select>

                    <div style="text-align:right; margin-top:10px;">
                        <button type="submit">Create Course</button>
                        <button type="button" id="close-popup">Cancel</button>
                    </div>
                </form>
            </div>

        </div>

        <div class="bulk-actions-top">
            <form action="{{ route('admin.courses.toggle-everything') }}" method="POST">
                @csrf
                @method('PUT')
                <button type="submit" class="btn-global-toggle">
                    🌍 Toggle All Site Visibility
                </button>
            </form>
        </div>
        <div class="blocks">
            @foreach($courses as $course)
                <div class="block">


                    <div class="block-top">

                        <form class="update-form" action="{{route('admin.courses.update',$course->id)}}" method="post">
                            @csrf
                            @method('PUT')


                            <div>

                                <div class="info-row">
                                    <label for="name">Title</label>
                                    <input class="value-input" type="text" name="title" value="{{$course->title}}">
                                </div>

                                <div class="info-row">
                                    <label for="year">year</label>
                                    <select name="year" class="year-input">
                                        <option value="1" {{ $course->year == 1 ? 'selected' : '' }}>Year 1</option>
                                        <option value="2" {{ $course->year == 2 ? 'selected' : '' }}>Year 2</option>
                                        <option value="3" {{ $course->year == 3 ? 'selected' : '' }}>Year 3</option>
                                    </select>
                                </div>
                            </div>


                            <div class="info-row">
                                <label for="branch" class="branch-label">branch</label>
                                <select name="branch" class="branch-input">
                                    <option value="mi" {{ $course->branch == 'mi' ? 'selected' : '' }}>mi</option>
                                    <option value="st" {{ $course->branch == 'st' ? 'selected' : '' }}>st</option>
                                    <option value="none"
                                            style="display: none" {{ $course->branch == 'none' ? 'selected' : '' }}>none
                                    </option>
                                </select>
                            </div>

                            <div class="info-row">
                                <label for="description"></label>
                                <textarea name="description" class="value-input">{{$course->description}}</textarea>
                            </div>

                            <div class="info-row">
                                <label>Visibility</label>
                                <button type="button"
                                        class="status-toggle-btn {{ $course->status }}"
                                        data-course-id="{{ $course->id }}"
                                        data-status="{{ $course->status }}"
                                        onclick="toggleSingleCourse(this, event)">
                                    {{ ucfirst($course->status) }}
                                </button>
                                <input type="hidden" name="status" value="{{ $course->status }}">
                            </div>

                            <input class="value-input" type="submit" name="update" value="update">

                        </form>


                        <form action="{{route('admin.courses.destroy',$course->id)}}" method="post">
                            @csrf
                            @method('DELETE')
                            <input type="button"
                                   name="course-delete"
                                   class="block-delete"
                                   onclick="deleteCourse({{ $course->id }}, this)"
                                   value="delete">
                        </form>

                        <a href="{{route('admin.courses.chapters.index',$course)}}">manage chapters</a>

                    </div>


                </div>
            @endforeach
        </div>

    </div>

@endsection


@section('js')
    <script>


        axios.defaults.headers.common['X-CSRF-TOKEN'] =
            document.querySelector('meta[name="csrf-token"]').getAttribute('content');


        const addBtn = document.getElementById('block-adder');
        const popup = document.getElementById('block-popup');
        const closeBtn = document.getElementById('close-popup');

        // Show popup
        addBtn.addEventListener('click', () => {
            popup.style.display = 'block';
        });

        // Hide popup
        closeBtn.addEventListener('click', () => {
            popup.style.display = 'none';
        });

        // Optional: click outside to close
        window.addEventListener('click', (e) => {
            if (e.target === popup) {
                popup.style.display = 'none';
            }
        });


        document.querySelectorAll('.update-form').forEach(form => {
            const year = form.querySelector('.year-input');
            const branch = form.querySelector('.branch-input');
            const label = form.querySelector('.branch-label');

            function toggleBranch() {
                if (parseInt(year.value) > 1) {
                    branch.style.visibility = 'visible';
                    label.style.visibility = 'visible';
                } else {
                    branch.style.visibility = 'hidden';
                    label.style.visibility = 'hidden';
                }
            }

            year.addEventListener('change', toggleBranch);

            // run once on load
            toggleBranch();
        });


        // Get all update forms
        const forms = document.querySelectorAll('.update-form');

        forms.forEach(form => {
            const inputs = form.querySelectorAll('input, textarea, select');
            const updateBtn = form.querySelector('input[type="submit"]');

            // Store original values
            const originalValues = [];

            inputs.forEach((input, index) => {
                originalValues[index] = input.value;
            });

            // Hide button initially
            updateBtn.style.visibility = 'hidden';

            // Listen for changes
            inputs.forEach((input, index) => {
                input.addEventListener('input', () => {
                    let changed = false;

                    inputs.forEach((inp, i) => {
                        if (inp.value != originalValues[i]) {
                            changed = true;
                        }
                    });

                    updateBtn.style.visibility = changed ? 'visible' : 'hidden';
                });

                // For select elements (important)
                input.addEventListener('change', () => {
                    let changed = false;

                    inputs.forEach((inp, i) => {
                        if (inp.value != originalValues[i]) {
                            changed = true;
                        }
                    });

                    updateBtn.style.display = changed ? 'inline-block' : 'none';
                });
            });
        });


        //AXIOS
        function toggleSingleCourse(btn, event) {


            console.log('CLICKED');
            console.log(btn.dataset);

            if (event) event.stopPropagation();

            const courseId = btn.dataset.courseId;
            const currentStatus = btn.dataset.status;
            const newStatus = (currentStatus === 'published') ? 'draft' : 'published';

            // UI update
            updateButtonUI(btn, newStatus);

            const form = btn.closest('.update-form');

            const payload = {
                status: newStatus,
                title: form.querySelector('input[name="title"]').value,
                year: form.querySelector('.year-input').value,
                branch: form.querySelector('.branch-input').value,
                description: form.querySelector('textarea').value
            };

            const url = `/admin/courses/${courseId}`;
            console.log("PUT URL:", url);

            axios.put(url, payload)


        }
        // Function to handle the visual flip of the buttons
        function updateButtonUI(btn, status) {
            btn.dataset.status = status;
            btn.innerText = status.charAt(0).toUpperCase() + status.slice(1);

            // Remove old classes and add the new one
            btn.classList.remove('published', 'draft');
            btn.classList.add(status);
        }
        // Function for deleting courses
        function deleteCourse(courseId, btn) {
            if (!confirm("Are you sure you want to delete this course?")) return;

            const block = btn.closest('.block'); // Find it before the request
            const url = `/admin/courses/${courseId}`;

            axios.delete(url)
                .then(response => {
                    block.remove();
                    refreshGlobalUI();
                    updateGlobalButtonUI();
                })
                .catch(error => {
                    // Even if Laravel throws a 404/Error, if the course is gone,
                    // we should remove it from the UI.
                    console.error("DELETE ERROR (but removing UI anyway):", error);
                    block.remove();
                    refreshGlobalUI();
                    updateGlobalButtonUI();
                });
        }

        function refreshGlobalUI() {
            const blocks = document.querySelectorAll('.block');

            const statusButtons = document.querySelectorAll('.status-toggle-btn');

            // 1. Count courses
            const totalCourses = blocks.length;

            // 2. Count drafts / published
            let draftCount = 0;
            let publishedCount = 0;

            statusButtons.forEach(btn => {
                const status = btn.dataset.status;

                if (status === 'draft') draftCount++;
                if (status === 'published') publishedCount++;
            });

            // 3. Update global toggle button
            const globalBtn = document.querySelector('.btn-global-toggle');

            if (globalBtn) {
                if (draftCount > 0) {
                    globalBtn.innerText = `🚀 Publish All (${draftCount} drafts)`;
                    globalBtn.style.background = "#34495e";
                } else {
                    globalBtn.innerText = `📂 Move All to Draft (${publishedCount} published)`;
                    globalBtn.style.background = "#27ae60";
                }
            }

            // 4. Update counters (if you add them in UI)
            const totalEl = document.querySelector('#total-courses');
            if (totalEl) totalEl.innerText = totalCourses;

            const draftEl = document.querySelector('#draft-count');
            if (draftEl) draftEl.innerText = draftCount;

            const publishedEl = document.querySelector('#published-count');
            if (publishedEl) publishedEl.innerText = publishedCount;

            updateGlobalButtonUI();
        }
        // Global UI helper to sync the "Big Switch" (Optional but good for consistency)
        function updateGlobalButtonUI() {
            const globalBtn = document.querySelector('.btn-global-toggle');
            const allCourseBtns = document.querySelectorAll('.status-toggle-btn');

            if(!globalBtn || allCourseBtns.length === 0) return;

            const hasAnyDrafts = Array.from(allCourseBtns).some(btn => btn.dataset.status === 'draft');

            if (hasAnyDrafts) {
                globalBtn.innerText = "🚀 Publish All Courses";
                globalBtn.style.background = "#34495e"; // Neutral Dark
            } else {
                globalBtn.innerText = "📂 Move All to Draft";
                globalBtn.style.background = "#27ae60"; // Solid Green
            }
        }

        // Run it on load to set the initial state of the Big Switch
        document.addEventListener('DOMContentLoaded', updateGlobalButtonUI);





    </script>
@endsection

