<?php

use App\Models\chapter;
use App\Models\lesson;
use Livewire\Component;

new class extends Component {
    public $course;
    public $chapters;
    public $openChapters = [];
    public $allPublished;
    public $currentChapter;
    public $currentLesson;

    protected $listeners = [
        'chapterCreated' => 'addChapter',
        'ChapterUpdated' => 'updateChapter',
        'ChapterDeleted' => 'deleteChapter',
    ];

    public function mount($course, $chapters, $chapter, $lesson)
    {
        $this->course = $course;
        $this->chapters = $chapters;
        $this->currentChapter = $chapter;
        $this->currentLesson = $lesson;
        $this->allPublished = $this->chapters->where('status', '=', 'draft')->count() === 0;
    }

    public function refreshChapters()
    {
        $this->chapters = chapter::where('course_id', '=', $this->course->id)->orderBy('chapter_number')->get();
    }

    public function addChapter(int $id)
    {
        $chapter = chapter::findOrFail($id);
        if ($chapter) $this->chapters->push($chapter);
        $this->refreshChapters();
    }

    public function toggleLessons($chapterId)
    {
        if (in_array($chapterId, $this->openChapters)) {
            $this->openChapters = array_diff($this->openChapters, [$chapterId]);
        } else {
            $this->openChapters[] = $chapterId;
        }
    }

    public function updateChapter($id)
    {
        $updatedChapter = \App\Models\Chapter::find($id);
        if ($updatedChapter) {
            $this->chapters = $this->chapters->map(fn($c) => $c->id === $updatedChapter->id ? $updatedChapter : $c);
        }
        $this->refreshChapters();
    }

    public function deleteChapter($id)
    {
        $this->chapters = $this->chapters->reject(fn($c) => $c->id === $id);
        $this->refreshChapters();
    }

    public function togglestatus($id)
    {
        $chapter = chapter::findOrFail($id);
        $newStatus = $chapter->status === 'published' ? 'draft' : 'published';
        $chapter->update(['status' => $newStatus]);
        $this->chapters = $this->chapters->map(function ($item) use ($id, $newStatus) {
            if ($item->id === $id) $item->status = $newStatus;
            return $item;
        });
        $this->allPublished = $this->chapters->where('status', '!=', 'published')->count() === 0;
    }

    public function masterToggle()
    {
        $isAllPublished = $this->chapters->where('status', '!=', 'published')->count() === 0;
        $chapterIds = $this->chapters->pluck('id');
        $newStatus = $isAllPublished ? 'draft' : 'published';

        lesson::whereIn('chapter_id', $chapterIds)->update(['status' => $newStatus]);
        chapter::whereIn('id', $chapterIds)->update(['status' => $newStatus]);

        $this->chapters = chapter::where('course_id', $this->course->id)->orderBy('chapter_number')->get();
        $this->allPublished = $this->chapters->where('status', '!=', 'published')->count() === 0;

        $this->dispatch('masterToggle');
    }
};
?>
<div>

    <div class="chapters-panel">


        <div>

        </div>

        {{-- ── Master toggle ── --}}
        <div class="panel-toolbar">
            @placeholder
            <div>
                <div class="panel-toolbar">
                    <div
                        style="width: 60px; height: 12px; background: var(--border); border-radius: 4px; animation: pulse 2s infinite;"></div>
                    <div
                        style="width: 80px; height: 24px; background: var(--border); border-radius: 6px; animation: pulse 2s infinite;"></div>
                </div>

                @foreach(range(1, 5) as $i)
                    <div class="chapter-header"
                         style="border-bottom: 1px solid var(--border-mid); pointer-events: none;">
                        <div class="header-left">
                            <div
                                style="width: 10px; height: 10px; background: var(--border); border-radius: 2px;"></div>
                            <div
                                style="width: 25px; height: 18px; background: var(--border); border-radius: 4px;"></div>
                            <div
                                style="width: 120px; height: 14px; background: var(--border); border-radius: 4px; animation: pulse 2s infinite;"></div>
                        </div>
                        <div class="header-right">
                            <div
                                style="width: 60px; height: 18px; background: var(--border); border-radius: 20px;"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            @endplaceholder
            <span class="panel-label">
            {{ $chapters->count() }} {{ Str::plural('chapter', $chapters->count()) }}
        </span>
            <button
                class="master-toggle-btn {{ $allPublished ? 'is-published' : 'is-draft' }}"
                wire:click="masterToggle"
                title="{{ $allPublished ? 'Unpublish all' : 'Publish all' }}"
            >
                @if($allPublished)
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5">
                        <path d="M17 1l4 4-4 4M3 11V9a4 4 0 014-4h14M7 23l-4-4 4-4M21 13v2a4 4 0 01-4 4H3"/>
                    </svg>
                    Draft all
                @else
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5">
                        <path
                            d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                    </svg>
                    Publish all
                @endif
            </button>
        </div>

        {{-- ── Chapter list ── --}}



        @forelse($chapters as $chapter)
            @php $isActive = $currentChapter && $currentChapter->id === $chapter->id; @endphp
            <div
                class="chapter-group{{ $isActive ? ' chapter-active' : '' }}"
                x-data="{ open: {{ $isActive ? 'true' : 'false' }}, open_update_modal: false }"
                wire:key="chapter-{{ $chapter->id }}"
            >
                {{-- Chapter header --}}
                <div class="chapter-header" @click="open = !open">
                    <div class="header-left">
                        <svg class="ch-arrow" :class="open ? 'ch-arrow-open' : ''"
                             width="10" height="10" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                        <span class="chapter-number">{{ $chapter->chapter_number }}</span>
                        <strong class="chapter-title">{{ $chapter->title }}</strong>
                    </div>
                    <div class="header-right">
                        <button
                            type="button"
                            class="status-pill {{ $chapter->status }}"
                            wire:click.stop="togglestatus({{ $chapter->id }})"
                        >{{ $chapter->status === 'published' ? '✓' : '○' }} {{ ucfirst($chapter->status) }}</button>
                        <button class="icon-btn" @click.stop="open_update_modal = true" title="Edit chapter">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Lessons --}}
                <div x-show="open" x-transition:enter="transition-open" style="display:none;">
                    <livewire:modular_site.lesson.lessons :chapter="$chapter" :currentLesson="$currentLesson"
                                                          wire:key="lessons-{{ $chapter->id }}"/>
                </div>

                <livewire:modular_site.chapter.chapterupdate :course="$course" :chapter="$chapter"
                                                             wire:key="chupdate-{{ $chapter->id }}"/>
            </div>
        @empty
            <div class="panel-empty">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                     style="opacity:.3;margin-bottom:8px;">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M3 9h18M9 21V9"/>
                </svg>
                <p>No chapters yet.</p>
            </div>
        @endforelse


    </div>
</div>


<style>
    /* ── Panel shell ── */
    .chapters-panel {
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    /* ── Toolbar ── */
    .panel-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 12px;
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
        background: var(--bg);
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .panel-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--text-faint);
    }

    .master-toggle-btn {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 6px;
        border: 1px solid var(--border);
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
        transition: all .15s;
    }

    .master-toggle-btn.is-published {
        background: #ecfdf5;
        color: #065f46;
        border-color: #a7f3d0;
    }

    .master-toggle-btn.is-published:hover {
        background: #d1fae5;
    }

    .master-toggle-btn.is-draft {
        background: var(--bg-subtle);
        color: var(--text-muted);
        border-color: var(--border);
    }

    .master-toggle-btn.is-draft:hover {
        background: var(--bg-hover);
        color: var(--text);
    }

    [data-theme="dark"] .master-toggle-btn.is-published {
        background: #064e3b;
        color: #6ee7b7;
        border-color: #065f46;
    }

    /* ── Chapter group ── */
    .chapter-group {
        border-bottom: 1px solid var(--border-mid);
    }

    .chapter-group.chapter-active > .chapter-header {
        background: var(--bg-hover);
    }

    .chapter-group.chapter-active > .chapter-header .chapter-title {
        color: var(--accent);
    }

    /* ── Chapter header ── */
    .chapter-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 10px 8px 12px;
        cursor: pointer;
        user-select: none;
        gap: 6px;
        transition: background .12s;
    }

    .chapter-header:hover {
        background: var(--bg-hover);
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 7px;
        flex: 1;
        min-width: 0;
    }

    .ch-arrow {
        color: var(--text-faint);
        flex-shrink: 0;
        transition: transform .2s, color .15s;
    }

    .ch-arrow-open {
        transform: rotate(90deg);
        color: var(--accent);
    }

    .chapter-number {
        font-size: 10px;
        font-weight: 700;
        color: var(--text-faint);
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: 4px;
        padding: 1px 5px;
        flex-shrink: 0;
        min-width: 20px;
        text-align: center;
    }

    .chapter-title {
        font-size: 12.5px;
        font-weight: 500;
        color: var(--text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 1;
        min-width: 0;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-shrink: 0;
    }

    /* ── Status pill ── */
    .status-pill {
        font-size: 10px;
        font-weight: 600;
        padding: 2px 7px;
        border-radius: 20px;
        border: none;
        cursor: pointer;
        font-family: inherit;
        letter-spacing: .02em;
        transition: filter .15s;
        flex-shrink: 0;
        white-space: nowrap;
    }

    .status-pill.published {
        background: #d1fae5;
        color: #065f46;
    }

    .status-pill.draft {
        background: #f3f4f6;
        color: #6b7280;
    }

    .status-pill:hover {
        filter: brightness(.93);
    }

    [data-theme="dark"] .status-pill.published {
        background: #064e3b;
        color: #6ee7b7;
    }

    [data-theme="dark"] .status-pill.draft {
        background: #2a2a2a;
        color: #9ca3af;
    }

    /* ── Icon button ── */
    .icon-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        border: none;
        background: none;
        border-radius: 5px;
        cursor: pointer;
        color: var(--text-faint);
        transition: background .13s, color .13s;
    }

    .icon-btn:hover {
        background: var(--bg-hover);
        color: var(--text);
    }

    /* ── Open transition ── */
    .transition-open {
        transition: opacity .15s, transform .15s;
    }

    /* ── Empty state ── */
    .panel-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 40px 20px;
        color: var(--text-faint);
        font-size: 13px;
        text-align: center;
    }
</style>
