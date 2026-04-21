<?php

use App\Models\chapter;
use App\Models\lesson;
use App\Models\lesson_progress;
use Livewire\Component;

new class extends Component {
    public $id;
    public $course;
    public $chapters;

    public function mount($id, $course, $chapters)
    {

        $this->id = $id;
        $this->course = $course;
        $this->loadChapter();
    }


    public function loadChapter()
    {
        $this->chapters = chapter::where('course_id', '=', $this->course->id)->get();

    }

    public function resetChapter($chapterId)
    {
        $lesson_ids = lesson::where('chapter_id', $chapterId)->pluck('id');
        $lesson_progress = lesson_progress::wherein('lesson_id', $lesson_ids)
            ->where('user_id', $this->id)
            ->delete();
        $this->loadChapter();


        $this->dispatch('chapterReset');


    }

};
?>
<div class="blocks">
    @foreach($chapters as $i => $chapter)
        @php
            $chProgress = $chapter->progressForUser($id);
        @endphp

        <div class="block" wire:key"> {{-- reuse your card system --}}

        {{-- Header --}}
        <div class="ch-main-header">
            <h2 class="ch-main-title">
                Chapter {{ $i+1 }} — {{ $chapter->title }}
            </h2>

            <div style="display:flex; align-items:center; gap:10px;">
                    <span class="ch-progress-badge">
                        {{ $chProgress }}% complete
                    </span>

                <div>
                    <button class="btn-reset" wire:click="resetChapter({{$chapter->id}})">
                        Reset
                    </button>
                </div>
            </div>
        </div>

        {{-- Lessons --}}
        <div class="lessons-grid">
            @foreach($chapter->lessons as $j => $lesson)
                @if($lesson->status == 'published')
                    @php
                        $progress = $lesson->progressForUser($id);
                        $done = $progress && $progress->progress > 90;
                    @endphp

                    <a class="lesson-card {{ $done ? 'done' : '' }}"
                       href="{{ route('admin.preview.blocks',['course'=>$course,'chapter'=>$chapter,'lesson'=>$lesson]) }}">

                            <span class="lc-num">
                                {{ ($i+1) }}.{{ ($j+1) }}
                            </span>

                        <span class="lc-title">
                                {{ $lesson->title }}
                            </span>

                        <span class="lc-check {{ $done ? 'check-done' : 'check-none' }}">
                                {{ $done ? '✓' : '' }}
                            </span>

                    </a>
                @endif
            @endforeach
        </div>

</div>
@endforeach
</div>

