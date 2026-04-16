<?php

use App\Models\block;
use Livewire\Component;

new class extends Component {
    public $lesson;
    public $type;
    public $lesson_id;
    public $content;
    public $block_number;

    public function mount($lesson)
    {
        $this->lesson = $lesson;
        $this->type = 'header';
        $this->lesson_id =$this->lesson->id;
        $this->content = 'header';

    }

    public function store()
    {
        $this->block_number = block::where('lesson_id', $this->lesson_id)->max('block_number') + 1;
        $validated=$this->validate([
            'type'=>'required|string',
            'lesson_id'=>'required|int',
            'content'=>'required|string',
            'block_number'=>'required|int',
        ]);

        $block=block::create($validated);

        $this->dispatch('BlockCreated',id:$block->id);

    }
};
?>

<div class="block-adder-container">
    <button id="block-adder" class="fab-button" type="button" wire:click="store">+</button>
</div>
