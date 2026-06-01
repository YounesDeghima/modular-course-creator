<?php

use App\Models\course;
use Livewire\Component;

new class extends Component {

    public $title='title';
    public $description='description';
    public $year='1';
    public $branch='none';
    public $status='draft';

    public $course;

    public function updatedYear($value)
    {
        if ($value == 1) {
            $this->branch = 'none';
        } elseif ($this->branch == 'none') {
            // Set a default branch when moving away from Year 1
            $this->branch = 'mi';
        }
    }


    public function store()
    {

        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'year' => 'required|in:1,2,3',
            'status' => 'required|in:draft,published',
            'description' => 'required|string',
            'branch' => 'required|in:mi,st,none',
        ]);

        if ($this->year == 1) {
            $validated['branch'] = 'none';
        }


        $this->course = course::create($validated);
        $this->dispatch('courseCreated',id:$this->course->id);


        $this->reset();
    }
};
?>
<div id="add-course-modal" class="modal-overlay" x-data="{
            year:@entangle('year'),
            branch:@entangle('branch')}"
            x-show="open_course_modal" :class="{ 'open': open_course_modal }">
    <div id="block-popup" class="modal-content" :class="{ 'open': open_course_modal }"  @click.away="open_course_modal=false" >
        <form id="new-block-form" wire:submit.prevent="store" >

            @if ($errors->any())
                <div style="background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label>Title</label>
                <input class="value-input" type="text" name="title" wire:model="title" required>
            </div>
            <div style="display:flex;gap:10px;">
                <div style="flex:1;">
                    <label>Year</label>
                    <select name="year"
                            class="year-input"
                            wire:model="year"
                            x-model="year">
                        <option value="1">Year 1</option>
                        <option value="2">Year 2</option>
                        <option value="3">Year 3</option>
                    </select>
                </div>
                <div style="flex:1;">
                    <label class="branch-label">Branch</label>

                    <select name="branch"
                            class="branch-input"
                            wire:model="branch"
                            x-model="branch"
                            :disabled="year == 1">

                        <option value="mi" selected>MI</option>
                        <option value="st">ST</option>

                    </select>
                </div>
            </div>
            <div>
                <label>Description</label>
                <textarea name="description" required style="min-height:80px;" wire:model="description"></textarea>
            </div>
            <div>
                <label>Status</label>
                <select name="status" wire:model="status">
                    <option value="draft" selected>Draft (hidden)</option>
                    <option value="published">Published (live)</option>
                </select>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:4px;">
                <button type="button" id="close-popup" @click="open_course_modal = false">Cancel</button>
                <button type="submit">Create course</button>
            </div>
        </form>
    </div>
</div>

