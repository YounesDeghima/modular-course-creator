<?php

use App\Models\chapter;
use Livewire\Component;

new class extends Component {
    public $course;
    public $chapter;


    public $title;
    public $description;
    public $chapter_number;
    public $status;

    public $listeners = ['ChapterDeleted' => 'updateChapterNumber'];


    public function mount($course, $chapter)
    {
        $this->course = $course;
        $this->chapter = $chapter;
        $this->title = $chapter->title;
        $this->description = $chapter->description;
        $this->chapter_number = $chapter->chapter_number;
        $this->status = $chapter->status;
    }


    public function updateChapterNumber()
    {
        $this->chapter = chapter::find($this->chapter->id);

        if ($this->chapter) {
            $this->chapter_number = $this->chapter->chapter_number;
            $this->title = $this->chapter->title;
            $this->description = $this->chapter->description;
            $this->status = $this->chapter->status;
        }
    }

    public function update()
    {

        $this->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'chapter_number' => 'required|integer',
            'status' => 'required',
        ]);

        $this->chapter->update([
            'title' => $this->title,
            'description' => $this->description,
            'chapter_number' => $this->chapter_number,
            'status' => $this->status,
        ]);

        $this->dispatch('ChapterUpdated', id: $this->chapter->id);

    }

    public function delete()
    {
        $deletedNumber = $this->chapter->chapter_number;
        $courseId = $this->chapter->course_id;

        $this->chapter->delete();

        // 🔥 FIX: reorder remaining chapters
        chapter::where('course_id', $courseId)
            ->where('chapter_number', '>', $deletedNumber)
            ->decrement('chapter_number');
        $this->dispatch('ChapterDeleted', id: $this->chapter->id);
    }


};
?>

<div id="chapter-modal-{{$chapter->id}}"
     class="modal-overlay chapter-modal" x-show="open_update_modal"
     wire:key="modal-{{$chapter->id}}" x-cloak>
    <div class="modal-content" @click.away="open_update_modal=false">
        <span class="close-btn" @click="open_update_modal=false">&times;</span>
        <h3>Edit Chapter</h3>

        <div class="modal">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" class="modal-input" wire:model="title">
            </div>

            <div class="form-group">
                <label>Chapter Number</label>

                <input type="number" name="chapter_number" wire:model="chapter_number"
                       class="modal-input">
            </div>

            <div class="form-group">
                <label>Visibility Status</label>
                <select name="status" class="modal-input" wire:model="status">
                    <option value="draft">Draft (Hidden)</option>
                    <option value="published">Published (Live)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="modal-input" wire:model="description"
                          style="height:120px"></textarea>
            </div>
            <button type="submit" class="btn-update" wire:click="update" @click="open_update_modal=false">Update
                Chapter
            </button>
        </div>

        <div class="delete-form">
            <button type="button"
                    class="btn-delete"
                    wire:click="delete"
                    data-chapter-id="{{ $chapter->id }}">
                Delete
            </button>
        </div>
    </div>
</div>
