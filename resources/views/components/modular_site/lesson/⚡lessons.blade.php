<?php

use App\Models\lesson;
use Livewire\Component;

new class extends Component {
    public $chapter;
    public $lesson;
    public $lessons;
    public $currentLesson;
    public $allPublished;

    public $listeners = [
        'lessonCreated' => 'addLesson',
        'LessonUpdated' => 'UpdateLesson',
        'LessonDeleted' => 'DeleteLesson',
        'masterToggle'  => 'refreshlessons',
    ];

    public function mount($chapter, $currentLesson)
    {
        $this->chapter       = $chapter;
        $this->lessons       = $this->chapter->lessons;
        $this->currentLesson = $currentLesson;
        $this->allPublished  = $this->lessons->where('status', '!=', 'published')->count() === 0;
    }

    public function addLesson($id)
    {
        $lesson = lesson::findOrFail($id);
        if ($lesson) $this->lessons->push($lesson);
        $this->refreshlessons();
    }

    public function refreshlessons()
    {
        $this->lessons      = lesson::where('chapter_id', '=', $this->chapter->id)->orderBy('lesson_number')->get();
        $this->allPublished = $this->lessons->where('status', '!=', 'published')->count() === 0;
    }

    public function UpdateLesson($id)
    {
        $updatedLesson = \App\Models\Lesson::find($id);
        if ($updatedLesson) {
            $this->lessons = $this->lessons->map(fn($l) => $l->id === $updatedLesson->id ? $updatedLesson : $l);
        }
        $this->refreshlessons();
    }

    public function DeleteLesson($id)
    {
        $this->lessons = $this->lessons->reject(fn($l) => $l->id === $id);
        $this->refreshlessons();
    }

    public function toggleStatus($id)
    {
        $lesson    = lesson::findOrFail($id);
        $newStatus = $lesson->status === 'published' ? 'draft' : 'published';
        $lesson->update(['status' => $newStatus]);
        $this->lessons = $this->lessons->map(function ($item) use ($id, $newStatus) {
            if ($item->id === $id) $item->status = $newStatus;
            return $item;
        });
        $this->allPublished = $this->lessons->where('status', '!=', 'published')->count() === 0;
    }

    public function masterToggle()
    {
        $isAllPublished = $this->lessons->where('status', '!=', 'published')->count() === 0;
        $newStatus      = $isAllPublished ? 'draft' : 'published';
        lesson::whereIn('id', $this->lessons->pluck('id'))->update(['status' => $newStatus]);
        $this->lessons      = lesson::where('chapter_id', $this->chapter->id)->orderBy('lesson_number')->get();
        $this->allPublished = $this->lessons->where('status', '!=', 'published')->count() === 0;
    }

    public function changeLesson($id)
    {
        $selectedLesson = lesson::findOrFail($id);
        if ($id != $this->currentLesson->id) {
            $this->dispatch('LessonChanged', id: $id, chapterId: $selectedLesson->chapter->id);
            $this->currentLesson = $selectedLesson;
        }
    }
};
?>

<div class="lessons-panel" id="lessons-container-{{ $chapter->id }}">

    @if($lessons->count() > 0)
        {{-- Bulk toggle --}}
        <div class="lessons-toolbar">
            <span class="lessons-count">{{ $lessons->count() }} {{ Str::plural('lesson', $lessons->count()) }}</span>
            <button
                class="bulk-btn {{ $allPublished ? 'bulk-published' : 'bulk-draft' }}"
                wire:click="masterToggle"
                title="{{ $allPublished ? 'Draft all lessons' : 'Publish all lessons' }}"
            >
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M17 1l4 4-4 4M3 11V9a4 4 0 014-4h14M7 23l-4-4 4-4M21 13v2a4 4 0 01-4 4H3"/>
                </svg>
                Toggle all
            </button>
        </div>
    @endif

    {{-- Lesson rows --}}
    @foreach($lessons as $lesson)
        @php $isActive = $currentLesson && $currentLesson->id === $lesson->id; @endphp
        <div
            class="lesson-row{{ $isActive ? ' lesson-active' : '' }}"
            wire:key="lesson-{{ $lesson->id }}"
            wire:click="changeLesson({{ $lesson->id }})"
            x-data="{ openLessonModalId: null }"
        >
            <div class="lesson-left">
                <span class="lesson-num">{{ $lesson->lesson_number }}</span>
                <span class="lesson-title">{{ $lesson->title }}</span>
            </div>
            <div class="lesson-right">
                <button
                    type="button"
                    class="status-pill {{ $lesson->status }}"
                    wire:click.stop="toggleStatus({{ $lesson->id }})"
                >{{ $lesson->status === 'published' ? '✓' : '○' }}</button>
                <button
                    class="icon-btn"
                    wire:click.stop
                    @click.stop="openLessonModalId = {{ $lesson->id }}"
                    title="Edit lesson"
                >
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </button>
            </div>

            <livewire:modular_site.lesson.lessonupdate :lesson="$lesson" :chapter="$chapter" wire:key="lupdate-{{ $lesson->id }}"/>
        </div>
    @endforeach

    {{-- Add lesson --}}
    <livewire:modular_site.lesson.lessoncreate :chapter="$chapter"/>

</div>

<style>
    /* ── Lessons panel ── */
    .lessons-panel {
        display: flex;
        flex-direction: column;
        background: var(--bg-subtle);
        border-top: 1px solid var(--border-mid);
    }

    /* ── Lessons toolbar ── */
    .lessons-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 5px 12px 5px 32px;
        border-bottom: 1px solid var(--border-mid);
        background: var(--bg-subtle);
    }

    .lessons-count {
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: var(--text-faint);
    }

    .bulk-btn {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 5px;
        border: 1px solid var(--border);
        font-size: 10px;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
        transition: all .15s;
    }

    .bulk-btn.bulk-published { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
    .bulk-btn.bulk-published:hover { background: #d1fae5; }
    .bulk-btn.bulk-draft { background: var(--bg); color: var(--accent); border-color: var(--border); }
    .bulk-btn.bulk-draft:hover { background: var(--bg-hover); }

    [data-theme="dark"] .bulk-btn.bulk-published { background: #064e3b; color: #6ee7b7; border-color: #065f46; }

    /* ── Lesson row ── */
    .lesson-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 6px 10px 6px 32px;
        cursor: pointer;
        transition: background .12s;
        gap: 6px;
        border-bottom: 1px solid var(--border-mid);
        position: relative;
    }

    .lesson-row::before {
        content: '';
        position: absolute;
        left: 20px;
        top: 0;
        bottom: 0;
        width: 1px;
        background: var(--border-mid);
    }

    .lesson-row:hover { background: var(--bg-hover); }

    .lesson-row.lesson-active {
        background: color-mix(in srgb, var(--accent) 6%, transparent);
        border-left: 2px solid var(--accent);
        padding-left: 30px;
    }

    .lesson-row.lesson-active .lesson-title { color: var(--accent); font-weight: 500; }

    .lesson-left {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
        min-width: 0;
    }

    .lesson-num {
        font-size: 9px;
        font-weight: 700;
        color: var(--text-faint);
        min-width: 14px;
        text-align: right;
        flex-shrink: 0;
    }

    .lesson-title {
        font-size: 12px;
        color: var(--text-mid);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 1;
        min-width: 0;
    }

    .lesson-right {
        display: flex;
        align-items: center;
        gap: 3px;
        flex-shrink: 0;
    }

    /* reuse status-pill and icon-btn from chapters styles */
</style>
