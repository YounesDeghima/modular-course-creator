<?php

use App\Models\course;
use Livewire\Component;

new class extends Component {

    public course $course;
    public $title;
    public $year;
    public $branch;
    public $description;
    public $status;

    public function mount(Course $course)
    {
        $this->course = $course;
        $this->title = $course->title;
        $this->year = $course->year;
        $this->branch = $course->branch;
        $this->description = $course->description;
        $this->status = $course->status;
    }

    public function togglestatus(){
        $this->course->status =($this->course->status === 'published') ? 'draft' : 'published';
        $this->status=$this->course->status;
        $this->course->update(['status'=>$this->status]);
    }

    public function save()
    {
        $this->course->update([
            'title'       => $this->title,
            'year'        => $this->year,
            'branch'      => $this->branch,
            'description' => $this->description,
            'status'=>$this->status,
        ]);
        $this->dispatch('notify', 'Course updated!');
        $this->dispatch('stats-updated');
    }


    public function delete()
    {

        $this->course->delete();
        $this->dispatch('course-deleted');
    }

};
?>

<div class="block"
     data-status="{{ $status }}"
     data-year="{{ $year }}"
     wire:key="course-{{$course->id}}"
     x-data="{ isDeleted: false }"
     x-show="!isDeleted"


    >

    <div class="block-meta-row">
        <span class="year-badge year-{{ $year }}">Year {{ $year }}</span>
        <button type="button"
                class="status-toggle-btn {{ $status }}"
                data-status="{{$status}}"

                wire:click="togglestatus"
                >
            {{ ucfirst($status) }}
        </button>
    </div>

    <form class="update-form" wire:submit.prevent="save">



        <div class="info-row">
            <label>Title</label>
            <input class="value-input" type="text" name="title" wire:model.live="title">
        </div>

        <div style="display:flex;gap:8px;">
            <div class="info-row" style="flex:1">
                <label>Year</label>
                <select name="year" class="year-input" wire:model.live="year">
                    <option value="1">Year 1</option>
                    <option value="2">Year 2</option>
                    <option value="3">Year 3</option>
                </select>
            </div>
            @if($year!=1)
            <div class="info-row" style="flex:1;">
                <label class="branch-label">Branch</label>
                <select name="branch" class="branch-input" wire:model.live="branch">
                    @if($year!=1)
                    <option value="mi">MI</option>
                    <option value="st">ST</option>

                    @endif

                    <option value="none" style="display:none" {{ $branch=='none'?'selected':'' }}>None</option>
                </select>
            </div>
            @endif
        </div>

        <div class="info-row">
            <label>Description</label>
            <textarea name="description" class="value-input"
                      style="min-height:70px;" wire:model.live="description"></textarea>
        </div>

        <button class="value-input" type="submit">
            <span wire:loading.remove wire:target="save">Save changes</span>
            <span wire:loading wire:target="save">Saving...</span>
        </button>
    </form>

    <div class="block-actions">
        <a class="btn-card-action" href="{{ route('admin.courses.chapters.index',['course'=>$course->id]) }}">
            Manage chapters
        </a>
        <a class="btn-card-action" href="{{route('admin.courses.quiz.index',['course'=>$course->id])}}">manage quiz</a>
       <button class="btn-card-action danger"
               wire:click="delete"
               wire:confirm="are you sure you want to delete"
               @click="isDeleted = true">delete</button>
    </div>
</div>
