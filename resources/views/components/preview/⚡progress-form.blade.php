<?php

use Livewire\Component;

new class extends Component
{
    public $lesson;
    public $lesson_progress;


    public function mount($lesson,$lesson_progress){
        $this->lesson = $lesson;
        $this->lesson_progress = $lesson_progress;
    }


};
?>

<div id="progress-form" method="POST">

    <input type="hidden" name="lesson_id" value="{{ $lesson->id }}">
    <input type="hidden" name="progress" id="progress-input"
           value="{{ $lesson_progress ? $lesson_progress->progress : 0 }}">
    <button type="submit" hidden>Send</button>
</div>
