<?php

use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component {
    public $id;
    public $chapter;
    public $lesson;
    public $course;
    public $prevlesson;
    public $nextlesson;
    public $prevchapter;
    public $nextchapter;

    public function mount($id, $chapter, $lesson, $course, $prevlesson, $nextlesson, $prevchapter, $nextchapter)
    {
        $this->id = $id;
        $this->chapter = $chapter;
        $this->lesson = $lesson;
        $this->course = $course;
        $this->prevlesson = $prevlesson;
        $this->nextlesson = $nextlesson;
        $this->prevchapter = $prevchapter;
        $this->nextchapter = $nextchapter;
    }

    #[On('reloadProgress')]
    public function reloadProgress()
    {
        // Refresh the chapter and lesson to get updated progress data
        $this->chapter = $this->chapter->fresh();
        $this->lesson = $this->lesson->fresh();
    }
};
?>

<style>
    /* ── Sidebar styles ── */
    .sb-course-head {
        padding: 16px;
        border-bottom: 1px solid var(--border, #e5e7eb);
    }

    .sb-course-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--text-muted, #6b7280);
        margin-bottom: 4px;
    }

    .sb-chapter-name {
        font-size: 15px;
        font-weight: 600;
        color: var(--text, #000);
        margin-bottom: 12px;
    }

    .sb-ch-progress {
        margin-top: 12px;
    }

    .sb-ch-prog-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
        margin-bottom: 6px;
        color: var(--text-muted, #6b7280);
    }

    .sb-ch-bar {
        width: 100%;
        height: 6px;
        background: var(--bg-subtle, #f3f4f6);
        border-radius: 3px;
        overflow: hidden;
    }

    .sb-ch-fill {
        height: 100%;
        background: #10b981;
        transition: width 0.4s ease;
        border-radius: 3px;
    }

    .lesson-nav-list {
        display: flex;
        flex-direction: column;
        gap: 0;
        padding: 0;
        margin: 0;
        border-bottom: 1px solid var(--border, #e5e7eb);
        flex: 1;
        overflow-y: auto;
    }

    .lesson-nav-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
        color: var(--text-muted, #6b7280);
        text-decoration: none;
        border-left: 3px solid transparent;
        transition: all 0.15s ease;
        border-bottom: 1px solid var(--border, #e5e7eb);
    }

    .lesson-nav-item:hover {
        background: var(--bg-subtle, #f9fafb);
        color: var(--text, #000);
    }

    .lesson-nav-item.active {
        background: var(--bg-subtle, #f9fafb);
        border-left-color: #4f46e5;
        color: var(--text, #000);
        font-weight: 500;
    }

    .lesson-nav-num {
        font-size: 11px;
        font-weight: 600;
        color: var(--text-muted, #9ca3af);
        min-width: 40px;
    }

    .lesson-nav-title {
        flex: 1;
        font-size: 13px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .lesson-nav-check {
        font-size: 14px;
        font-weight: 700;
        min-width: 16px;
        text-align: center;
        transition: all 0.2s ease;
    }

    .lesson-nav-check.lnc-done {
        color: #10b981;
    }

    .lesson-nav-check.lnc-none {
        color: transparent;
    }

    .sb-lesson-nav {
        display: flex;
        gap: 8px;
        padding: 12px 16px;
        margin-top: auto;
    }

    .sb-nav-btn {
        flex: 1;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 500;
        border: 1px solid var(--border, #e5e7eb);
        border-radius: 6px;
        background: var(--bg, #fff);
        color: var(--text, #000);
        text-decoration: none;
        cursor: pointer;
        transition: all 0.15s ease;
        text-align: center;
    }

    .sb-nav-btn:hover:not(.disabled) {
        background: var(--bg-hover, #f3f4f6);
        border-color: #9ca3af;
    }

    .sb-nav-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        color: var(--text-muted, #9ca3af);
    }

    /* Sidebar wrapper - make it flex container for bottom-sticking buttons */
    div > .sb-course-head {
        flex-shrink: 0;
    }

    [data-theme="dark"] .sb-course-head {
        border-bottom-color: #374151;
    }

    [data-theme="dark"] .sb-chapter-name {
        color: #f3f4f6;
    }

    [data-theme="dark"] .sb-ch-bar {
        background: #374151;
    }

    [data-theme="dark"] .lesson-nav-item:hover {
        background: #1f2937;
        color: #f3f4f6;
    }

    [data-theme="dark"] .lesson-nav-item.active {
        background: #1f2937;
        color: #f3f4f6;
    }

    [data-theme="dark"] .sb-nav-btn {
        background: #1f2937;
        color: #f3f4f6;
        border-color: #374151;
    }

    [data-theme="dark"] .sb-nav-btn:hover:not(.disabled) {
        background: #374151;
    }
</style>

<div style="display: flex; flex-direction: column; height: 100%;">
    <div class="sb-course-head">
        <div class="sb-course-label">Chapter</div>
        <div class="sb-chapter-name">{{ $chapter->title }}</div>
        <div class="sb-ch-progress">
            <div class="sb-ch-prog-label">
                <span>Chapter progress</span>
                <span>{{ $chapter->progressForUser($id) }}%</span>
            </div>
            <div class="sb-ch-bar">
                <div class="sb-ch-fill" style="width: {{ $chapter->progressForUser($id) }}%"></div>
            </div>
        </div>
    </div>

    <nav class="lesson-nav-list">
        @foreach($chapter->lessons as $i => $lesson_item)
            @if($lesson_item->status === 'published')
                @php
                    $lp     = $lesson_item->progressForUser($id);
                    $isDone = $lp && $lp->progress >= 90;
                @endphp
                <a class="lesson-nav-item {{ $lesson_item->id === $lesson->id ? 'active' : '' }}"
                   href="{{ route('admin.preview.blocks', ['course'=>$course,'chapter'=>$chapter,'lesson'=>$lesson_item]) }}">
                    <span class="lesson-nav-num">{{ $chapter->chapter_number }}.{{ $i+1 }}</span>
                    <span class="lesson-nav-title">{{ $lesson_item->title }}</span>
                    <span class="lesson-nav-check {{ $isDone ? 'lnc-done' : 'lnc-none' }}">
                        {{ $isDone ? '✓' : '' }}
                    </span>
                </a>
            @endif
        @endforeach
    </nav>

    <div class="sb-lesson-nav">
        @if($prevlesson)
            <a class="sb-nav-btn"
               href="{{ route('admin.preview.blocks', ['course'=>$course,'chapter'=>$chapter,'lesson'=>$prevlesson]) }}">
                ‹ Prev
            </a>
        @elseif($prevchapter)
            <a class="sb-nav-btn"
               href="{{ route('admin.preview.lessons', ['course'=>$course,'chapter'=>$prevchapter]) }}">
                ‹ Prev chapter
            </a>
        @else
            <span class="sb-nav-btn disabled">‹ Prev</span>
        @endif

        @if($nextlesson)
            <a class="sb-nav-btn"
               href="{{ route('admin.preview.blocks', ['course'=>$course,'chapter'=>$chapter,'lesson'=>$nextlesson]) }}">
                Next ›
            </a>
        @elseif($nextchapter)
            <a class="sb-nav-btn"
               href="{{ route('admin.preview.lessons', ['course'=>$course,'chapter'=>$nextchapter]) }}">
                Next chapter ›
            </a>
        @else
            <a class="sb-nav-btn"
               href="{{ route('admin.preview.chapters', ['course'=>$course]) }}">
                Back to course ›
            </a>
        @endif
    </div>
</div>
