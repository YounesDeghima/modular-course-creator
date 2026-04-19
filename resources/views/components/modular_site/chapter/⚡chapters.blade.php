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

    protected $listeners = ['chapterCreated' => 'addChapter',
        'ChapterUpdated' => 'updateChapter',
        'ChapterDeleted' => 'deleteChapter'];

    public function mount($course, $chapters, $chapter, $lesson)
    {
        $this->allPublished = $this->chapters->where('status', '=', 'draft')->count() === 0;
        $this->course = $course;
        $this->chapters = $chapters;
        $this->currentChapter = $chapter;
        $this->currentLesson = $lesson;

    }

    public function refreshChapters()
    {
        $this->chapters = chapter::where('course_id', '=', $this->course->id)->orderBy('chapter_number')->get();
    }

    public function addChapter(int $id)
    {

        $chapter = chapter::findOrFail($id);

        if ($chapter) {
            $this->chapters->push($chapter);
        }
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
            // Map through the current collection and replace the matching ID
            $this->chapters = $this->chapters->map(function ($chapter) use ($updatedChapter) {
                return $chapter->id === $updatedChapter->id ? $updatedChapter : $chapter;
            });
        }
        $this->refreshChapters();

    }

    public function deleteChapter($id)
    {
        $this->chapters = $this->chapters->reject(function ($chapter) use ($id) {
            return $chapter->id === $id;
        });
        $this->refreshChapters();
    }

    public function togglestatus($id)
    {
        $chapter = chapter::findOrFail($id);
        $newStatus = ($chapter->status === 'published') ? 'draft' : 'published';
        $chapter->update(['status' => $newStatus]);

        // IMPORTANT: Update the chapter in the local collection
        $this->chapters = $this->chapters->map(function ($item) use ($id, $newStatus) {
            if ($item->id === $id) {
                $item->status = $newStatus;
            }
            return $item;
        });

        // Recalculate if everything is published
        $this->allPublished = $this->chapters->where('status', '!=', 'published')->count() === 0;
    }

    public function updateMasterToggle()
    {
        $this->allPublished = $this->chapters->where('status', '=', 'draft')->count() === 0;
    }

    public function masterToggle()
    {

        $isAllPublished = $this->chapters->where('status', '!=', 'published')->count() === 0;
        $chapterIds = $this->chapters->pluck('id');
        $newStatus  = $isAllPublished ? 'draft' : 'published';

        lesson::whereIn('chapter_id', $chapterIds)->update(['status' => $newStatus]);


        chapter::whereIn('id', $chapterIds)->update(['status' => $newStatus]);

        // Refresh the local collection and the toggle state
        $this->chapters = chapter::where('course_id', $this->course->id)->get();
        $this->allPublished = $this->chapters->where('status', '!=', 'published')->count() === 0;

        $this->dispatch('masterToggle');

    }

};
?>


<div>
    <div class="bulk-actions" style="padding: 10px; border-bottom: 1px solid #ddd;">
        <div id="master-toggle-form">
            <button type="submit"
                    x-data="{allPublished : @entangle('allPublished')}"

                    id="master-toggle-btn"
                    class="btn-publish-all"
                    wire:click="masterToggle"
                    x-on:click="allPublished = !allPublished"
                    x-text="allPublished ? 'Draft All' : 'Publish All'">
            </button>
        </div>
    </div>
    @if($chapters)
        @foreach($chapters as $chapter)
            <div class="chapter-group"
                 x-data="{open : false,open_update_modal : false,open_lesson_update_modal:false,openLessonModalId:null}"
                 wire:key="chapter-{{ $chapter->id }}">

                <div class="chapter-header" @click="open=!open">
                    <div class="header-left">
                        <span class="arrow-icon" x-text="open  ? '▼' : '▶'"></span>
                        <strong class="chapter-title">{{ $chapter->title }}</strong>

                        <button type="button"

                                class="status-toggle-btn {{$chapter->status}}"
                                data-chapter-id="{{ $chapter->id}}"
                                data-status="{{ $chapter->status }}"

                                @click.stop="$wire.togglestatus({{ $chapter->id }})">
                            <span>{{ ucfirst($chapter->status) }}</span>

                        </button>
                    </div>

                    <div class="header-right">
                        <span class="pen-icon" @click.stop="open_update_modal = true">✏️</span>

                    </div>
                </div>
                <livewire:modular_site.lesson.lessons :chapter="$chapter" :currentLesson="$currentLesson"/>


                <livewire:modular_site.chapter.chapterupdate :course="$course" :chapter="$chapter"/>

            </div>

        @endforeach
    @else
        <div class="chapter-header" onclick="toggleLessons('{{$chapter->id}}')">
            <div class="header-left">
                no chapters yet
            </div>


        </div>
    @endif
</div>
