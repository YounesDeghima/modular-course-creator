<?php

use Livewire\Component;

new class extends Component
{
    public $id;
    public $course;
    public $courseProgress;

    public $listeners = ['chapterReset','reloadProgress'];

    public function mount($id,$course){
        $this->id = $id;
        $this->course = $course;
        $this->reloadProgress();
    }

    public function reloadProgress(){
        $this->courseProgress = $this->course->progressForUser($this->id);

    }

};
?>

<div class="sb-course-head" wire:poll.10ms="reloadProgress">
    <div class="sb-course-label">Course</div>
    <div class="sb-course-name">{{ $course->title }}</div>
    <div class="sb-overall-progress">
        <div class="sb-progress-label">
            <span>Overall progress</span>
            <span id="overall-pct">{{$courseProgress}}%</span>
        </div>
        <div class="sb-progress-bar">
            <div class="sb-progress-fill" id="overall-fill" style="width: {{$courseProgress}}%; border-radius: 5px"></div>
        </div>
    </div>
</div>
