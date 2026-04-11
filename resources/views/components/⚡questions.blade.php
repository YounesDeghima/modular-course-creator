<?php

use App\Models\coursequestion;
use App\Models\questionchoice;
use Livewire\Component;

new class extends Component {
    public $questions = [];
    public $course;

    protected $listeners = ['questionCreated' => 'addQuestion',
                            'quizSaving'=>'save'];


    public function mount($course, $questions)
    {
        $this->course = $course;

        // Convert everything to arrays (IMPORTANT)
        $this->questions = collect($questions)->map(function ($q) {
            return [
                'id' => $q->id,
                'content' => $q->content,
                'questionchoices' => $q->questionchoices->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'content' => $c->content,
                        'value' => $c->value,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    public function delete($id)
    {
        coursequestion::find($id)?->delete();

        $this->questions = array_values(
            array_filter($this->questions, function ($q) use ($id) {
                return ($q['id'] ?? null) != $id;
            })
        );

        $this->dispatch('question-deleted');
    }

    public function save()
    {
        foreach ($this->questions as $q) {

            $question = coursequestion::find($q['id']);

            if ($question) {
                $question->update([
                    'content' => $q['content'],
                ]);
            }

            foreach ($q['questionchoices'] as $choicedata) {
                $choice = questionchoice::find($choicedata['id']);

                if ($choice) {
                    $choice->update([
                        'content' => $choicedata['content'],
                        'value' => $choicedata['value'],
                    ]);
                }
            }
        }

        $this->dispatch('saved');
    }

    public function addQuestion(int $id)
    {

        $q = coursequestion::with('questionchoices')->find($id);

        if (!$q) return;

        $new = [
            'id' => $q->id,
            'content' => $q->content,
            'questionchoices' => $q->questionchoices->map(function ($c) {
                return [
                    'id' => $c->id,
                    'content' => $c->content,
                    'value' => $c->value,
                ];
            })->toArray(),
        ];

        $this->questions = collect($this->questions)
            ->prepend($new)
            ->values()
            ->toArray();
    }


}
?>
<div class="blocks-wrapper">
    <div class="route-header">
        <h2>
            <span class="course-name">{{ $course->title }}</span>
        </h2>
    </div>

    <div class="blocks-list stack-container">
        @forelse($questions as $qIndex => $question)
            <div class="block-row" wire:key="question-{{ $question['id'] }}-{{ $qIndex }}">

                <div class="block-main-content">

                    <textarea
                        class="input-ghost title-style"
                        placeholder="Enter question"
                        wire:model="questions.{{ $qIndex }}.content">
                    </textarea>

                    <div class="choices">
                        @foreach($question['questionchoices'] as $cIndex => $choice)
                            <div wire:key="choice-{{ $choice['id'] }}-{{ $cIndex }}">

                                <textarea
                                    class="input-ghost content-style"
                                    wire:model="questions.{{ $qIndex }}.questionchoices.{{ $cIndex }}.content">
                                </textarea>

                                <select
                                    wire:model="questions.{{ $qIndex }}.questionchoices.{{ $cIndex }}.value">
                                    <option value="0">true</option>
                                    <option value="1">false</option>
                                </select>

                            </div>
                        @endforeach
                    </div>

                </div>

                {{-- FIX: pass ID --}}
                <button wire:click="delete({{ $question['id'] }})">delete</button>
            </div>
        @empty
            <div class="empty-state">
                <p>No content here yet.</p>
            </div>
        @endforelse
    </div>

{{--    <div class="save-container">--}}
{{--        <button class="btn-save-all" wire:click="save">Save All Changes</button>--}}
{{--    </div>--}}
</div>
