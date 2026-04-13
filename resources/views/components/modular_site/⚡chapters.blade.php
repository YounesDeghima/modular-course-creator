<?php

use App\Models\chapter;
use Livewire\Component;

new class extends Component {
    public $course;
    public $chapters;



    protected $listeners = ['chapterCreated' => 'addChapter'];

    public function mount($course,$chapters)
    {
        $this->course = $course;
        $this->chapters = $chapters;


    }

    public function addChapter(int $id)
    {

        $chapter = chapter::findOrFail($id);

        if ($chapter) {
            $this->chapters->push($chapter);
        }
    }

};
?>

<div>
    @if($chapters)
        @foreach($chapters as $chapter)
            <div class="chapter-group">

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
                            <form action="{{ route('admin.courses.chapters.lessons.toggle-all', [$course->id, $chapter->id]) }}"
                                  method="POST" style="width: 100%;">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn-bulk-lessons">
                                    ⚡ Toggle All Lessons
                                </button>
                            </form>
                        </div>
                    @endif
                    @foreach($chapter->lessons as $lesson)
                        <div class="lesson-row"
                             data-href='{{ route('admin.courses.chapters.lessons.blocks.index',[$course->id, $chapter->id, $lesson->id]) }}'>
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

                                <form onsubmit="updateLesson(event, this)"
                                      action="{{ route('admin.courses.chapters.lessons.update', [$course->id, $chapter->id, $lesson->id]) }}"
                                      method="POST" class="lesson-form">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-title">
                                        <label>Title</label>
                                        <input type="text" name="title" value="{{ $lesson->title }}" class="modal-input">
                                    </div>
                                    <div class="form-group">
                                        <label>Lesson Number</label>
                                        <input type="number" name="lesson_number" value="{{ $lesson->lesson_number }}"
                                               class="modal-input" min="1" required>
                                    </div>
                                    <div class="form-discription">
                                        <label>Description</label>
                                        <textarea name="description" class="modal-input">{{ $lesson->description }}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Visibility Status</label>
                                        <select name="status" class="modal-input">
                                            <option value="draft" {{ $lesson->status == 'draft' ? 'selected' : '' }}>Draft
                                                (Hidden)
                                            </option>
                                            <option value="published" {{ $lesson->status == 'published' ? 'selected' : '' }}>
                                                Published (Live)
                                            </option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn-update">Update Lesson</button>
                                </form>

                                <form action="{{ route('admin.courses.chapters.lessons.destroy', [$course, $chapter, $lesson]) }}"
                                      method="POST" class="delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                            class="btn-delete"
                                            onclick="deleteLesson(event, '{{ $course->id }}','{{ $chapter->id }}','{{ $lesson->id }}')">
                                        Delete
                                    </button>
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

                            <form onsubmit="createLesson(event, this)"
                                  action="{{ route('admin.courses.chapters.lessons.store', [$course->id, $chapter->id]) }}"
                                  method="POST">
                                @csrf
                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" name="title" class="modal-input" required placeholder="Lesson name...">
                                </div>
                                <div class="form-group">
                                    <label>Lesson Number</label>
                                    <input type="number" name="lesson_number" value="{{ $chapter->lessons->count() + 1 }}"
                                           class="modal-input" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="modal-input" required></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Visibility Status</label>
                                    <select name="status" class="modal-input">
                                        <option value="draft" {{ $lesson->status == 'draft' ? 'selected' : '' }}>Draft (Hidden)
                                        </option>
                                        <option value="published" {{ $lesson->status == 'published' ? 'selected' : '' }}>Published
                                            (Live)
                                        </option>
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

                        <form onsubmit="updateChapter(event, this)"
                              action="{{ route('admin.courses.chapters.update', [$course->id, $chapter->id]) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" value="{{ $chapter->title }}" class="modal-input">
                            </div>

                            <div class="form-group">
                                <label>Chapter Number</label>

                                <input type="number" name="chapter_number" value="{{ $chapter->chapter_number }}"
                                       class="modal-input">
                            </div>

                            <div class="form-group">
                                <label>Visibility Status</label>
                                <select name="status" class="modal-input">
                                    <option value="draft" {{ $chapter->status == 'draft' ? 'selected' : '' }}>Draft (Hidden)
                                    </option>
                                    <option value="published" {{ $chapter->status == 'published' ? 'selected' : '' }}>Published
                                        (Live)
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="modal-input"
                                          style="height:120px">{{ $chapter->description }}</textarea>
                            </div>
                            <button type="submit" class="btn-update">Update Chapter</button>
                        </form>

                        <form action="{{ route('admin.courses.chapters.destroy', [$course, $chapter]) }}" method="POST"
                              class="delete-form">
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

            </div>


        @endforeach
    @else
        <div class="chapter-header" onclick="toggleLessons('{{$chapter->id}}')">
            <div class="header-left">
                no chapters yet
            </div>


        </div>
    @endif
</div>
