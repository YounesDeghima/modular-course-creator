<?php

use App\Models\block;
use App\Models\chapter;
use App\Models\lesson;
use Livewire\Component;

new class extends Component {
    public $course;
    public $chapter;
    public $lesson;

    public $listeners = ['LessonChanged' => 'updateNavigation'];

    public function mount($course, $chapter, $lesson)
    {
        $this->course = $course;
        $this->chapter = $chapter;
        $this->lesson = $lesson;
    }

    public function updateNavigation($id, $chapterId)
    {
        $this->lesson = lesson::findOrFail($id);
        $this->chapter = chapter::findOrFail($chapterId);
    }

};
?>

<div class="route-header">
    <h2>
        <span class="course-name">{{$course->title}}</span>
        <small>></small> {{$chapter->title}}
        <small>></small> <span class="active-lesson">{{$lesson->title}}</span>
    </h2>
</div>
