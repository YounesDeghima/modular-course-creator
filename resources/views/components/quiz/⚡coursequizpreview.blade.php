<?php

use Livewire\Component;

new class extends Component
{
    public $course;
    public $questions = [];
    public $score = 0;
    public $total = 100;

    public $submitted = false;
    public $results = [];

    // This will store user answers
    public $answers = [];

    public function mount($course, $questions)
    {
        $this->course = $course;

        $this->questions = collect($questions)->map(function ($q) {

            // initialize empty array for each question
            $this->answers[$q->id] = [];

            return [
                'id' => $q->id,
                'content' => $q->content,
                'questionchoices' => $q->questionchoices->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'content' => $c->content,
                        'is_correct' => $c->value, // fix from before
                    ];
                })->toArray(),
            ];
        })->toArray();
    }



    public function submit()
    {
        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($this->questions as $question) {

            $correctAnswers = collect($question['questionchoices'])
                ->where('is_correct', true)
                ->pluck('id')
                ->toArray();

            $userAnswers = $this->answers[$question['id']] ?? [];

            // total possible points = number of correct answers
            $totalPoints += count($correctAnswers);

            // count how many correct answers user selected
            $correctSelected = count(array_intersect($correctAnswers, $userAnswers));

            // OPTIONAL: penalize wrong selections
            $wrongSelected = count(array_diff($userAnswers, $correctAnswers));

            // basic scoring (no penalty)
            $earnedPoints += $correctSelected;

            // If you want penalty, use this instead:
            // $earnedPoints += max(0, $correctSelected - $wrongSelected);

            $this->results[$question['id']] = [
                'correct_answers' => $correctAnswers,
                'user_answers' => $userAnswers,
                'correct_selected' => $correctSelected,
                'wrong_selected' => $wrongSelected,
            ];
        }

        $this->score = $earnedPoints/count($correctAnswers);
        $this->total = count($this->questions);

        $this->submitted = true;
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

                    @php
                        $result = $results[$question['id']] ?? null;

                        $isCorrectChoice = $result && in_array($choice['id'], $result['correct_answers']);
                        $isSelected = $result && in_array($choice['id'], $result['user_answers']);

                        $isWrong = $isSelected && !$isCorrectChoice;


                    @endphp

                    <div style="
                        @if($submitted)
                            @if($isCorrectChoice) background: #d4edda;
                            @elseif($isWrong) background: #f8d7da;

                            @endif
                        @endif">
                        <input
                            type="checkbox"
                            wire:model="answers.{{ $question['id']}}"
                            value="{{ $choice['id'] }}"
                            @disabled($submitted)

                            >

                        <span>{{ $choice['content']}}</span>
                    </div>
                @endforeach
            </div>

        </div>
    @endforeach

    <div class="save-container">
        <button class="btn-save-all" wire:click="submit">
            Submit Quiz
        </button>
    </div>


    @if($submitted)
        <div class="score-box">
            Score: {{ $score }} / {{ $total }}
        </div>
    @endif

</div>
