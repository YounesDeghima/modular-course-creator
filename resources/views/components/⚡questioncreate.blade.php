<?php

use App\Models\coursequestion;
use App\Models\questionchoice;
use Livewire\Component;

new class extends Component {
    public $course;
    public $content;
    public $value = false;
    public $course_id;

    public $coursequestion;
    public $questionchoice;
    public $coursequestion_id;

    public function mount($course)
    {
        $this->course = $course;

        $this->course_id = $this->course->id;

    }

    public function store()
    {
        $this->content ='enter the question here';
        $validated = $this->validate([
            'course_id' => 'required|int',
            'content' => 'required|string|max:1000',
        ]);


        $this->coursequestion = coursequestion::create($validated);

        $i = 4;
        while ($i != 0) {
            $this->content ='enter the choice here';
            $this->coursequestion_id = $this->coursequestion->id;


            $validated = $this->validate([
                'coursequestion_id' => 'required|int',
                'content' => 'required|string|max:1000',
                'value' => 'required|boolean'
            ]);


            $this->questionchoice = questionchoice::create($validated);
            $this->dispatch('choice_created', id: $this->questionchoice->id);
            $i--;
        }
        $this->dispatch('questionCreated', id: $this->coursequestion->id);





    }

};
?>

<div class="block-adder-container">
    <button id="block-adder" class="fab-button"

            type="button" wire:click="store()">+
    </button>
</div>
