<?php

use Livewire\Component;

new class extends Component
{
    public $course;
    public $questions = [];

    // This will store user answers
    public $answers = [];

    public function mount($course, $questions)
    {
        $this->course = $course;

        // Normalize like your editor component
        $this->questions = collect($questions)->map(function ($q) {
            return [
                'id' => $q->id,
                'content' => $q->content,
                'questionchoices' => $q->questionchoices->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'content' => $c->content,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    public function submit()
    {
        // $this->answers structure:
        // [question_id => choice_id]

        // Example debug
        // dd($this->answers);

        $this->dispatch('quiz-submitted', answers: $this->answers);
    }
};
?>


<div class="blocks-wrapper">

    <h2>{{ $course->title }}</h2>

    @foreach($questions as $index => $question)
        <div class="block-row" wire:key="preview-question-{{ $question['id'] }}">

            {{-- Question --}}
            <p class="title-style">
                <strong>Q{{ $index + 1 }}:</strong>
                {{ $question['content'] }}
            </p>

            {{-- Choices --}}
            <div class="choices">
                @foreach($question['questionchoices'] as $choice)
                    <label style="display:flex; gap:10px; margin-bottom:8px;">

                        <input
                            type="radio"
                            wire:model="answers.{{ $question['id'] }}"
                            value="{{ $choice['id'] }}"
                        >

                        <span>{{ $choice['content'] }}</span>

                    </label>
                @endforeach
            </div>

        </div>
    @endforeach

    <div class="save-container">
        <button class="btn-save-all" wire:click="submit">
            Submit Quiz
        </button>
    </div>

</div>
