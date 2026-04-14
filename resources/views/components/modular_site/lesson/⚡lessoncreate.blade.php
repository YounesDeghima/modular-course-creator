<?php

use App\Models\lesson;
use Livewire\Component;

new class extends Component {
    public $chapter;
    public $lesson;
    public $title;
    public $description;
    public $status='draft';
    public $lesson_number;
    public $chapter_id;
    public $lessons;
    public $lessonCount;

    public $listeners = ['LessonDeleted' => 'updateLessonNumber'];


    public function mount($chapter)
    {
        $this->chapter = $chapter;
        $this->chapter_id = $this->chapter->id;
        $this->lessonCount = lesson::where('chapter_id','=',$chapter->id)->count()+1;
        $this->lesson_number = $this->lessonCount;
        $this->lessons = $this->chapter->lessons;

    }

    public function updateLessonNumber($id){
        $this->lessonCount = lesson::where('chapter_id','=',$this->chapter->id)->count()+1;
        $this->lesson_number = $this->lessonCount;
    }

    public function store()
    {

        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'chapter_id' => 'required|int',
            'lesson_number' => 'required|int',
            'status' => 'required|in:draft,published',
            'description' => 'required|string',

        ]);
        $this->lesson = lesson::create($validated);

        $this->dispatch('lessonCreated',id:$this->lesson->id);
        $this->resetExcept(['chapter','lesson_number','status']);


    }
};
?>

<div x-data="{open_lesson_create_modal:false}">
    <div class="lesson-row add-lesson-row" @click="open_lesson_create_modal = true">
        <span class="plus-icon">+</span>
        <span class="lesson-link">Add Lesson</span>
    </div>
    <div id="add-lesson-modal-{{$chapter->id}}" class="modal-overlay chapter-modal" x-show="open_lesson_create_modal">
        <div class="modal-content" @click.away="open_lesson_create_modal = false">
            <span class="close-btn" @click="open_lesson_create_modal = false">&times;</span>
            <h3>New Lesson for {{ $chapter->title }}</h3>

            <div>
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" class="modal-input" required placeholder="Lesson name..."
                           wire:model="title">
                </div>
                <div class="form-group">
                    <label>Lesson Number</label>
                    <input type="number" name="lesson_number" value="{{ $chapter->lessons->count() + 1 }}"
                           class="modal-input" wire:model="lesson_number" readonly>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="modal-input" wire:model="description" required></textarea>
                </div>

                <div class="form-group">
                    <label>Visibility Status</label>
                    <select name="status" class="modal-input" wire:model="status">
                        <option value="draft">Draft (Hidden)
                        </option>
                        <option value="published">Published
                            (Live)
                        </option>
                    </select>
                </div>
                <button type="submit" class="btn-update" wire:click="store" @click="open_lesson_create_modal = false">Create Lesson</button>
            </div>
        </div>
    </div>
</div>
