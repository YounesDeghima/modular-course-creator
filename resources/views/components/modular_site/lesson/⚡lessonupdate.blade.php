<?php

use App\Models\lesson;
use Livewire\Component;

new class extends Component {
    public $chapter;
    public $lesson;
    public $title;
    public $description;
    public $status = 'draft';
    public $lesson_number;
    public $chapter_id;

    public $listeners = ['LessonDeleted' => 'updateLessonNumber'];


    public function mount($chapter, $lesson)
    {

        $this->lesson = $lesson;
        $this->chapter = $chapter;
        $this->chapter_id = $this->chapter->id;


        $this->title = $lesson->title;
        $this->description = $lesson->description;
        $this->lesson_number = $lesson->lesson_number;
        $this->status = $lesson->status;
    }


    public function updateLessonNumber(){
        $this->lesson = lesson::find($this->lesson->id);

        if ($this->lesson) {
            $this->lesson_number = $this->lesson->lesson_number;
            $this->title = $this->lesson->title;
            $this->description = $this->lesson->description;
            $this->status = $this->lesson->status;
        }
    }


    public function update(){
        $this->validate([
            'title' => 'required|string',
            'description'=>'required|string',
            'lesson_number' => 'required|integer',
            'status' => 'required',
        ]);

        $this->lesson->update([
            'title'=>$this->title,
            'description'=>$this->description,
            'lesson_number'=>$this->lesson_number,
            'status'=>$this->status,
        ]);

        $this->dispatch('LessonUpdated',id:$this->lesson->id);

    }


    public function delete(){
        $deletedNumber = $this->lesson->lesson_number;
        $chapterId = $this->lesson->chapter_id;

        $this->lesson->delete();

        // 🔥 FIX: reorder remaining lessons
        lesson::where('chapter_id', $chapterId)
            ->where('lesson_number', '>', $deletedNumber)
            ->decrement('lesson_number');

        $this->dispatch('LessonDeleted', id:$this->lesson->id);

        $this->resetExcept(['lesson','chapter','lesson_number','status']);
    }

};
?>

<div id="lesson-modal-{{$lesson->id}}" class="modal-overlay chapter-modal"
     x-show="openLessonModalId === {{$lesson->id}}" wire:key="lesson-{{ $lesson->id }}">
    <div class="modal-content" @click.away="openLessonModalId = null">
        <span class="close-btn" @click="openLessonModalId = null">&times;</span>
        <h3>Edit Lesson: {{ $lesson->lesson_number }}</h3>

        <div class="lesson-form">

            <div class="form-title">
                <label>Title</label>
                <input type="text" name="title" class="modal-input" wire:model="title">
            </div>
            <div class="form-group">
                <label>Lesson Number</label>
                <input type="number" name="lesson_number"
                       class="modal-input" min="1" required wire:model="lesson_number">
            </div>
            <div class="form-discription">
                <label>Description</label>
                <textarea name="description" class="modal-input" wire:model="description"></textarea>
            </div>
            <div class="form-group">
                <label>Visibility Status</label>
                <select name="status" class="modal-input" wire:model="status">
                    <option value="draft">Draft
                        (Hidden)
                    </option>
                    <option value="published">
                        Published (Live)
                    </option>
                </select>
            </div>
            <button type="submit" class="btn-update" wire:click="update" @click="openLessonModalId = null">Update Lesson</button>
        </div>

        <div class="delete-form">

            <button type="button"
                    class="btn-delete" wire:click="delete" @click="openLessonModalId = null">
                Delete
            </button>
        </div>
    </div>
</div>
