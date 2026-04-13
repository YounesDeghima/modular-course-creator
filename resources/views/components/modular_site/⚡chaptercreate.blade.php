<?php

use App\Models\chapter;
use Livewire\Component;

new class extends Component {
    public $course;
    public $chapter_count;

    public $title;

    public $description;
    public $status = 'draft';
    public $course_id;
    public $chapter;
    public $chapter_number;

    public function mount($course)
    {
        $this->$course = $course;
        $this->chapter_count = chapter::where('course_id', '=', $this->course->id)->count() + 1;
        $this->chapter_number = $this->chapter_count;


    }

    public function store(){
        $this->course_id = $this->course->id;

        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'course_id'=>'required|int',
            'chapter_number'=>'required|int',
            'status' => 'required|in:draft,published',
            'description' => 'required|string',

        ]);


        $this->chapter = chapter::create($validated);


        $this->dispatch('chapterCreated',id:$this->chapter->id);

    }


};
?>
<div id="add-chapter-modal" class="modal-overlay">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('add-chapter-modal')">&times;</span>
        <h3>Create New Chapter</h3>
        <div  id="new-block-form">

            <div class="form-group">
                <label>Title:</label>
                <input class="modal-input" type="text" name="title" wire:model="title" required>
            </div>
            <div class="form-group">
                <label>Chapter Number:</label>
                <input class="modal-input" type="number" name="chapter_number" value="{{$chapter_count}}" wire:model="chapter_number" readonly>
            </div>
            <div class="form-group">
                <label>Description:</label>
                <textarea class="modal-input" name="description" style="height:100px;" wire:model="description"  required></textarea>
            </div>
            <div class="form-group">
                <label>Initial Status:</label>
                <select name="status" class="modal-input" wire:model="status">
                    <option value="draft" selected>Draft (Hidden)</option>
                    <option value="published">Published (Live)</option>
                </select>
            </div>
            <button  class="btn-update" wire:click="store">Create Chapter</button>
        </div>
    </div>
</div>
