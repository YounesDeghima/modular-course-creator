<?php

use Livewire\Component;

new class extends Component
{
    public $course;


    public function mount($course){
        $this->course = $course;
    }

    public function save(){
        $this->dispatch('quizSaving');
    }

};
?>

<div class="save-container" wire:click="save">
    <button class="btn-save-all" wire:click="save">Save All Changes</button>
</div>
