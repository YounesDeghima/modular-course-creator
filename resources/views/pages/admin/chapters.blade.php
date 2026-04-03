@if(!request()->ajax())
    @extends('layouts.edditor')
@endif


@section('css')

    <link rel="stylesheet" href="{{asset('css/modular-site.css')}}">


@endsection

@section('back-button')
    <a class="back-button" href="{{route('admin.courses.index')}}">{{$course->title}}</a>
@endsection





@section('main')
    @fragment('main-content')
        <div class="blocks-wrapper">
            <div class="route-header">
                <h2>
                    <span class="course-name">{{$course->title}}</span>
                    <small>></small> {{$chapter->title}}
                    <small>></small> <span class="active-lesson">{{$lesson->title}}</span>
                </h2>
            </div>

            {{-- Single form wrapper for bulk saving --}}
            <form class="block-form" action="{{ route('admin.courses.chapters.lessons.blocks.update-all', [$course->id, $chapter->id, $lesson->id]) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="blocks-list stack-container">
                    @forelse($blocks as $block)
                        <div class="block-row type-{{ $block->type }}">
                            <input type="hidden" name="blocks[{{ $block->id }}][id]" value="{{ $block->id }}">
                            <input type="hidden" name="blocks[{{ $block->id }}][block_number]" value="{{ $block->block_number }}">



                            <div class="block-main-content">
                                @if($block->type == 'header')
                                    <textarea type="text" name="blocks[{{ $block->id }}][content]"
                                       class="input-ghost title-style"
                                       placeholder="Enter Title...">{{ $block->content }}</textarea>

                                @else

                                    @if($block->type == 'exercise')
                                        <div class="exercise-container">
                                            <label>Question:</label>
                                            <textarea name="blocks[{{ $block->id }}][content]"
                                                      class="input-ghost content-style"
                                                      rows="1"
                                                      oninput="this.style.height = '';this.style.height = this.scrollHeight + 'px'">{{ $block->content }}</textarea>





                                            @foreach($block->solutions as $solution)
                                                <label>Solution</label>
                                                <textarea name="blocks[{{ $block->id }}][solutions][{{ $solution->id}}]">{{ $solution->content }}</textarea>
                                            @endforeach
                                        </div>


                                    @else
                                        <textarea name="blocks[{{ $block->id }}][content]"
                                                  class="input-ghost content-style"
                                                  rows="1"
                                                  oninput="this.style.height = '';this.style.height = this.scrollHeight + 'px'">{{ $block->content }}</textarea>
                                    @endif
                                @endif
                            </div>


                            <div class="block-controls">
                                <div class="control-group">
                                    <span class="control-icon" onclick="toggleTypeSelect('{{ $block->id }}')">✏️</span>
                                    <select name="blocks[{{ $block->id }}][type]" id="select-{{ $block->id }}" class="mini-type-select">
                                        <option value="header" {{ $block->type == 'header' ? 'selected' : '' }}>H1</option>
                                        <option value="description" {{ $block->type == 'description' ? 'selected' : '' }}>Text</option>
                                        <option value="note" {{ $block->type == 'note' ? 'selected' : '' }}>Note</option>
                                        <option value="code" {{ $block->type == 'code' ? 'selected' : '' }}>Code</option>
                                        <option value="exercise" {{ $block->type == 'exercise' ? 'selected' : '' }}>exercise</option>
                                    </select>
                                </div>


                                <button type="button" value="{{ $block->id }}:up" class="arrow-btn">∧</button>
                                <button type="button" value="{{ $block->id }}:down" class="arrow-btn">∨</button>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <p>No content here yet. Click the <strong>+</strong> button to add a block.</p>
                        </div>
                    @endforelse
                </div>

                <div class="save-container">
                    <button type="submit" class="btn-save-all">Save All Changes</button>
                </div>

            </form>
        </div>

        <div class="block-adder-container">
            <button id="block-adder" class="fab-button" type="button" onclick="openModal('block-popup')">+</button>
        </div>

        <div id="block-popup" class="modal-overlay">
            <div class="modal-content">
                <span class="close-btn" onclick="closeModal('block-popup')">&times;</span>
                <h3>Add New Content Block</h3>
                <form method="POST" action="{{route('admin.courses.chapters.lessons.blocks.store', [$course->id, $chapter->id, $lesson->id])}}">
                    @csrf
                    <div class="form-group">
                        <label>Internal Name</label>
                        <input class="modal-input" type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Block Type</label>
                        <select name="type" class="modal-input">
                            <option value="header">header</option>
                            <option value="description">Description</option>
                            <option value="note">Note</option>
                            <option value="code">Code</option>
                            <option value="exercise">exercise</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Initial Content</label>
                        <textarea class="modal-input" name="content" rows="4" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Block Number</label>
                        <input style="visibility: hidden" class="modal-input" type="number" name="block_number" value="{{ $lesson->blocks->count() + 1 }}" min="1" required>
                    </div>

                    <button type="submit" class="btn-update">Create Block</button>
                </form>
            </div>
        </div>
    @endfragment
@endsection

@section('sidebar-elements')

    <div class="bulk-actions" style="padding: 10px; border-bottom: 1px solid #ddd;">
        <form action="{{ route('admin.courses.chapters.publish-all', $course->id) }}" method="POST" id="master-toggle-form">
            @csrf
            @method('PUT')
            <button type="submit" id="master-toggle-btn" class="btn-publish-all">
                Loading...
            </button>
        </form>
    </div>

    @if($chapters)
        @foreach($chapters as $chapter)
            <div class="chapter-group" >

                <div class="chapter-header" onclick="toggleLessons('{{$chapter->id}}')">
                    <div class="header-left">
                        <span class="arrow-icon" id="arrow-{{$chapter->id}}">▶</span>
                        <strong class="chapter-title">{{ $chapter->title }}</strong>

                        <button type="button"
                                class="status-toggle-btn {{ $chapter->status }}"
                                data-chapter-id="{{ $chapter->id }}"
                                data-status="{{ $chapter->status }}"
                                onclick="toggleSingleChapter(this)">
                            {{ ucfirst($chapter->status) }}
                        </button>
                    </div>

                    <div class="header-right">
                        <span class="pen-icon" onclick="openModal('chapter-modal-{{$chapter->id}}')">✏️</span>

                    </div>
                </div>

                <div id="lessons-container-{{$chapter->id}}" class="lessons-list" style="display: none;">
                    @if($chapter->lessons->count() > 0)
                        <div class="lesson-row bulk-lesson-action">
                            <form action="{{ route('admin.courses.chapters.lessons.toggle-all', [$course->id, $chapter->id]) }}" method="POST" style="width: 100%;">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn-bulk-lessons">
                                    ⚡ Toggle All Lessons
                                </button>
                            </form>
                        </div>
                    @endif
                    @foreach($chapter->lessons as $lesson)
                        <div class="lesson-row" data-href='{{ route('admin.courses.chapters.lessons.blocks.index',[$course->id, $chapter->id, $lesson->id]) }}'>
                            <div class="lesson-content">
                                <span class="bullet">•</span>
                                <a class="lesson-link">{{ $lesson->title }}</a>

                                <button type="button"
                                        class="status-toggle-btn lesson-status {{ $lesson->status }}"
                                        data-lesson-id="{{ $lesson->id }}"
                                        data-chapter-id="{{ $chapter->id }}"
                                        data-status="{{ $lesson->status }}"
                                        onclick="toggleSingleLesson(this, event)">
                                    {{ ucfirst($lesson->status) }}
                                </button>
                            </div>
                            <span class="pen-icon lesson-pen" onclick="openModal('lesson-modal-{{$lesson->id}}', event)">✏️</span>
                        </div>

                        <div id="lesson-modal-{{$lesson->id}}" class="modal-overlay">
                            <div class="modal-content">
                                <span class="close-btn" onclick="closeModal('lesson-modal-{{$lesson->id}}')">&times;</span>
                                <h3>Edit Lesson: {{ $lesson->lesson_number }}</h3>

                                <form action="{{ route('admin.courses.chapters.lessons.update', [$course->id, $chapter->id, $lesson->id]) }}" method="POST" class="lesson-form">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-title">
                                        <label>Title</label>
                                        <input type="text" name="title" value="{{ $lesson->title }}" class="modal-input">
                                    </div>
                                    <div class="form-group">
                                        <label>Lesson Number</label>
                                        <input type="number" name="lesson_number" value="{{ $lesson->lesson_number }}" class="modal-input" min="1" required>
                                    </div>
                                    <div class="form-discription">
                                        <label>Description</label>
                                        <textarea name="description" class="modal-input">{{ $lesson->description }}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Visibility Status</label>
                                        <select name="status" class="modal-input">
                                            <option value="draft" {{ $lesson->status == 'draft' ? 'selected' : '' }}>Draft (Hidden)</option>
                                            <option value="published" {{ $lesson->status == 'published' ? 'selected' : '' }}>Published (Live)</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn-update">Update Lesson</button>
                                </form>

                                <form action="{{ route('admin.courses.chapters.lessons.destroy', [$course, $chapter, $lesson]) }}" method="POST" class="delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                            class="btn-delete"
                                            onclick="deleteLesson('{{ $course->id }}','{{ $chapter->id }}','{{ $lesson->id }}')">Delete</button>
                                </form>
                            </div>
                        </div>

                    @endforeach
                    <div class="lesson-row add-lesson-row" onclick="openModal('add-lesson-modal-{{$chapter->id}}')">
                        <span class="plus-icon">+</span>
                        <span class="lesson-link">Add Lesson</span>
                    </div>
                    <div id="add-lesson-modal-{{$chapter->id}}" class="modal-overlay">
                        <div class="modal-content">
                            <span class="close-btn" onclick="closeModal('add-lesson-modal-{{$chapter->id}}')">&times;</span>
                            <h3>New Lesson for {{ $chapter->title }}</h3>

                            <form action="{{ route('admin.courses.chapters.lessons.store', [$course->id, $chapter->id]) }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" name="title" class="modal-input" required placeholder="Lesson name...">
                                </div>
                                <div class="form-group">
                                    <label>Lesson Number</label>
                                    <input type="number" name="lesson_number" value="{{ $chapter->lessons->count() + 1 }}" class="modal-input" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="modal-input" required></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Visibility Status</label>
                                    <select name="status" class="modal-input">
                                        <option value="draft" {{ $lesson->status == 'draft' ? 'selected' : '' }}>Draft (Hidden)</option>
                                        <option value="published" {{ $lesson->status == 'published' ? 'selected' : '' }}>Published (Live)</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn-update">Create Lesson</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div id="chapter-modal-{{$chapter->id}}" class="modal-overlay">
                    <div class="modal-content">
                        <span class="close-btn" onclick="closeModal('chapter-modal-{{$chapter->id}}')">&times;</span>
                        <h3>Edit Chapter</h3>

                        <form action="{{ route('admin.courses.chapters.update', [$course->id, $chapter->id]) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" value="{{ $chapter->title }}" class="modal-input">
                            </div>

                            <div class="form-group" style="visibility: hidden">
                                <label >Chapter Number</label>
                                <input type="number" name="chapter_number" value="{{ $chapter->chapter_number }}" class="modal-input">
                            </div>

                            <div class="form-group">
                                <label>Visibility Status</label>
                                <select name="status" class="modal-input">
                                    <option value="draft" {{ $chapter->status == 'draft' ? 'selected' : '' }}>Draft (Hidden)</option>
                                    <option value="published" {{ $chapter->status == 'published' ? 'selected' : '' }}>Published (Live)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="modal-input" style="height:120px">{{ $chapter->description }}</textarea>
                            </div>
                            <button type="submit" class="btn-update">Update Chapter</button>
                        </form>

                        <form action="{{ route('admin.courses.chapters.destroy', [$course, $chapter]) }}" method="POST" class="delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="button"
                                    class="btn-delete"
                                    onclick="deleteChapter(event, this)"
                                    data-url="{{ route('admin.courses.chapters.destroy', [$course, $chapter]) }}"
                                    data-chapter-id="{{ $chapter->id }}">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>

            </div> @endforeach
    @else
        <div class="chapter-header" onclick="toggleLessons('{{$chapter->id}}')">
            <div class="header-left">
                no chapters yet
            </div>


        </div>
    @endif


            <div class="chapter-group add-chapter-trigger">
                <div class="chapter-header add-header" onclick="openModal('add-chapter-modal')">
                    <div class="header-left">
                        <span class="plus-icon-lg">+</span>
                        <strong class="chapter-title">Add New Chapter</strong>
                    </div>
                </div>
            </div>


    <div id="add-chapter-modal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('add-chapter-modal')">&times;</span>
            <h3>Create New Chapter</h3>
            <form id="new-block-form" method="POST" action="{{route('admin.courses.chapters.store', $course)}}">
                @csrf
                <div class="form-group">
                    <label>Title:</label>
                    <input class="modal-input" type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Chapter Number:</label>
                    <input class="modal-input" type="number" name="chapter_number" value="{{$chapter_count+1}}" readonly>
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea class="modal-input" name="description" style="height:100px;" required></textarea>
                </div>
                <div class="form-group">
                    <label>Initial Status:</label>
                    <select name="status" class="modal-input">
                        <option value="draft" selected>Draft (Hidden)</option>
                        <option value="published">Published (Live)</option>
                    </select>
                </div>
                <button type="submit" class="btn-update">Create Chapter</button>
            </form>
        </div>
    </div>
@endsection


@section('js')
    <script>

        document.addEventListener('DOMContentLoaded', function() {

            // --- 1. CORE UTILITIES ---

            // Auto-resize textareas based on content
            function initAutoResize() {
                document.querySelectorAll('textarea.input-ghost').forEach(el => {
                    el.style.height = 'auto'; // Reset first
                    el.style.height = el.scrollHeight + 'px';
                });
            }

            // Initialize UI elements for the block editor
            function initBlockEditor() {
                initAutoResize();

                // Setup Floating Action Button
                const btn = document.getElementById('block-adder');
                const popup = document.getElementById('block-popup');
                const close = document.getElementById('close-popup');

                if (btn && popup) btn.onclick = () => popup.style.display = 'flex';
                if (close && popup) close.onclick = () => popup.style.display = 'none';
            }

            // --- 2. AJAX LESSON LOADING ---
            document.querySelectorAll('.lesson-row').forEach(row => {
                row.addEventListener('click', e => {
                    if (e.target.closest('.pen-icon')) return; // ignore edit button
                    const url = row.dataset.href;
                    if (!url) return;
                    e.preventDefault();
                    loadLesson(url);
                });
            });


            function loadLesson(url) {
                const mainContainer = document.querySelector('main');
                mainContainer.style.opacity = '0.5';

                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(response => response.text())
                    .then(html => {
                        mainContainer.innerHTML = html;
                        mainContainer.style.opacity = '1';
                        history.pushState(null, '', url);

                        // Re-run setup for the new HTML content
                        initBlockEditor();

                        initBlockReordering();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        mainContainer.style.opacity = '1';
                    });
            }

            // --- 3. GLOBAL WINDOW FUNCTIONS ---

            // Toggle the sidebar lesson lists
            window.toggleLessons = function(id) {
                const container = document.getElementById('lessons-container-' + id);
                const arrow = document.getElementById('arrow-' + id);
                if (!container) return;

                const isHidden = container.style.display === "none" || container.style.display === "";
                container.style.display = isHidden ? "flex" : "none";
                if (arrow) arrow.style.transform = isHidden ? "rotate(90deg)" : "rotate(0deg)";
            }

            // Open/Close Modals
            window.openModal = function(id) {
                const modal = document.getElementById(id);
                if (modal) modal.style.display = 'flex';
            }

            window.closeModal = function(id) {
                const modal = document.getElementById(id);
                if (modal) modal.style.display = 'none';
            }

            // The Pen button toggles the type dropdown (Using ID)
            window.toggleTypeSelect = function(id) {
                const el = document.getElementById('select-' + id);
                if (el) el.classList.toggle('active');
            }

            // Close modals on background click
            window.onclick = function(event) {
                if (event.target.classList.contains('modal-overlay')) {
                    event.target.style.display = 'none';
                }
            }

            // --- 4. INITIALIZATION ---
            initBlockEditor();
            window.onpopstate = () => loadLesson(window.location.href);
        });

        function updateBlockNumbers() {
            document.querySelectorAll('.blocks-list .block-row').forEach((block, index) => {
                const id = block.querySelector('input[name*="[id]"]').value;
                const blockNumberInput = block.querySelector(`input[name="blocks[${id}][block_number]"]`);
                if (blockNumberInput) blockNumberInput.value = index + 1;
            });
        }

        // --- 5. CLIENT-SIDE BLOCK REORDERING ---
        const blocksList = document.querySelector('.blocks-list');

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.arrow-btn');
            if (!btn) return;

            e.preventDefault(); // stop form submit

            const blockRow = btn.closest('.block-row');
            if (!blockRow) return;

            const parent = blockRow.parentNode;

            if (btn.value.endsWith(':up')) {
                const prev = blockRow.previousElementSibling;
                if (prev && prev.classList.contains('block-row')) {
                    parent.insertBefore(blockRow, prev); // swap
                }
            } else if (btn.value.endsWith(':down')) {
                const next = blockRow.nextElementSibling;
                if (next && next.classList.contains('block-row')) {
                    parent.insertBefore(next, blockRow); // swap
                }
            }

            // Update block numbers
            updateBlockNumbers();
        });


        function initBlockReordering() {
            document.querySelectorAll('.arrow-btn').forEach(btn => {
                btn.onclick = function(e) {
                    e.preventDefault();
                    const blockRow = btn.closest('.block-row');
                    const parent = blockRow.parentNode;

                    if (btn.value.endsWith(':up')) {
                        const prev = blockRow.previousElementSibling;
                        if (prev && prev.classList.contains('block-row')) {
                            parent.insertBefore(blockRow, prev);
                        }
                    } else if (btn.value.endsWith(':down')) {
                        const next = blockRow.nextElementSibling;
                        if (next && next.classList.contains('block-row')) {
                            parent.insertBefore(next, blockRow);
                        }
                    }

                    updateBlockNumbers();
                }
            });
        }


        function toggleSingleChapter(btn, event) {

            if (event) {
                event.stopPropagation();
            }


            const chapterId = btn.dataset.chapterId;
            const currentStatus = btn.dataset.status;
            const newStatus = (currentStatus === 'published') ? 'draft' : 'published';
            const courseId = "{{ $course->id }}"; // Blade variable

            // 1. Immediate UI Feedback (Oogabooga speed)
            updateButtonUI(btn, newStatus);

            // 2. Send to Server
            axios.put(`/admin/courses/${courseId}/chapters/${chapterId}`, {
                status: newStatus,
                title: btn.closest('.chapter-group').querySelector('.chapter-title').innerText,
                description: "Updated via toggle",
            })
                .then(() => {
                    checkMasterToggle();
                })
                .catch(() => {
                    updateButtonUI(btn, currentStatus);
                    alert('Failed to update status');
                });
        }

        function updateButtonUI(btn, status) {
            btn.dataset.status = status;
            btn.innerText = status.charAt(0).toUpperCase() + status.slice(1);
            btn.classList.remove('published', 'draft');
            btn.classList.add(status);
        }

        function checkMasterToggle() {
            const allBtns = document.querySelectorAll('.status-toggle-btn');
            const masterBtn = document.querySelector('.btn-publish-all');

            // Check if EVERY button has the 'published' class
            const allPublished = Array.from(allBtns).every(btn => btn.classList.contains('published'));

            if (allPublished) {
                masterBtn.classList.add('all-green');
                masterBtn.innerText = "🚀 All Published";
                masterBtn.style.background = "#2ecc71";
            } else {
                masterBtn.classList.remove('all-green');
                masterBtn.innerText = "📂 Publish All";
                masterBtn.style.background = "#95a5a6";
            }
        }

        // Run on page load
        document.addEventListener('DOMContentLoaded', checkMasterToggle);

        function updateMasterButtonUI() {
            const masterBtn = document.getElementById('master-toggle-btn');
            // Select all status buttons (Chapters + Lessons)
            const allBtns = document.querySelectorAll('.status-toggle-btn');

            if (!masterBtn || allBtns.length === 0) return;

            // Is there at least one "draft" button visible?
            const hasAnyDrafts = Array.from(allBtns).some(btn => btn.dataset.status === 'draft');

            if (hasAnyDrafts) {
                masterBtn.innerText = "🚀 Publish Everything";
                masterBtn.style.background = "#95a5a6"; // Gray-ish
                masterBtn.className = "btn-publish-all has-drafts";
            } else {
                masterBtn.innerText = "📂 Draft Everything";
                masterBtn.style.background = "#2ecc71"; // Green
                masterBtn.className = "btn-publish-all all-published";
            }
        }

        // Run it immediately on page load
        document.addEventListener('DOMContentLoaded', updateMasterButtonUI);

        function toggleSingleLesson(btn, event) {
            if (event) event.stopPropagation();

            const lessonId = btn.dataset.lessonId;
            const chapterId = btn.dataset.chapterId;
            const currentStatus = btn.dataset.status;
            const newStatus = currentStatus === 'published' ? 'draft' : 'published';
            const courseId = "{{ $course->id }}";

            updateButtonUI(btn, newStatus);

            axios.put(`/admin/courses/${courseId}/chapters/${chapterId}/lessons/${lessonId}`, {
                status: newStatus,
                title: btn.closest('.lesson-row').querySelector('.lesson-link').innerText,
                lesson_number: 1,
                description: "Status update"
            })
                .then(() => {
                    // sync UI if needed
                })
                .catch(() => {
                    updateButtonUI(btn, currentStatus);
                    alert('Sync failed');
                });
        }

        // If you use the AJAX toggle from the previous step,
        // make sure to call updateMasterButtonUI() inside the .then() block!

        function closeModal() {
            document.querySelector('.modal').classList.remove('active');
        }
        function closeLessonModal() {
            document.querySelector('.modal-overlay').classList.remove('active');
        }

        function deleteLesson(courseId, chapterId, lessonId) {

            const url = `/admin/courses/${courseId}/chapters/${chapterId}/lessons/${lessonId}`;

            axios.delete(url)
                .then(() => {
                    document.querySelector(`[data-lesson-id="${lessonId}"]`)
                        ?.closest('.lesson-row')
                        ?.remove();

                    closeModal(`lesson-modal-${lessonId}`);
                })
                .catch(() => {
                    document.querySelector(`[data-lesson-id="${lessonId}"]`)
                        ?.closest('.lesson-row')
                        ?.remove();

                    closeModal(`lesson-modal-${lessonId}`);
                });
        }

        function deleteChapter(event, btn) {
            event.stopPropagation();
            const url = btn.dataset.url;
            const chapterId = btn.dataset.chapterId;

            axios.delete(url)
                .then(() => {
                    const el = document.querySelector(`[data-chapter-id="${chapterId}"]`)
                        ?.closest('.chapter-group');

                    if (el) el.remove();

                    closeModal('chapter-modal-' + chapterId);
                })
                .catch(() => {
                    const el = document.querySelector(`[data-chapter-id="${chapterId}"]`)
                        ?.closest('.chapter-group');

                    if (el) el.remove();

                    closeModal('chapter-modal-' + chapterId);
                });
        }


    </script>
@endsection

