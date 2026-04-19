<?php

use App\Models\lesson;
use Livewire\Component;

new class extends Component {
    public $chapter;
    public $lesson;
    public $lessons;
    public $currentLesson;

    public $listeners = ['lessonCreated' => 'addLesson',
                         'LessonUpdated' => 'UpdateLesson',
                         'LessonDeleted' => 'DeleteLesson',
                         'masterToggle' => 'refreshlessons'];

    public $allPublished;

    public function mount($chapter,$currentLesson)
    {
        $this->chapter = $chapter;
        $this->lessons = $this->chapter->lessons;
        $this->currentLesson = $currentLesson;
    }

    public function addLesson($id)
    {
        $lesson = lesson::findOrFail($id);
        if($lesson){
            $this->lessons->push($lesson);
        }
        $this->refreshlessons();

    }

    public function refreshlessons(){
        $this->lessons = lesson::where('chapter_id','=',$this->chapter->id)->orderBy('lesson_number')->get();
    }

    public function UpdateLesson($id){
        $updatedLesson = \App\Models\Lesson::find($id);

        if ($updatedLesson) {
            // Map through the current collection and replace the matching ID
            $this->lessons = $this->lessons->map(function ($lesson) use ($updatedLesson) {
                return $lesson->id === $updatedLesson->id ? $updatedLesson : $lesson;
            });
        }
        $this->refreshlessons();

    }

    public function DeleteLesson($id){
        $this->lessons = $this->lessons->reject(function ($lesson) use ($id) {
            return $lesson->id === $id;});

        $this->refreshlessons();

    }

    public function toggleStatus($id){
        $lesson = lesson::findOrFail($id);
        $newStatus = ($lesson->status === 'published') ? 'draft' : 'published';
        $lesson->update(['status' => $newStatus]);

        // IMPORTANT: Update the lesson in the local collection
        $this->lessons = $this->lessons->map(function ($item) use ($id, $newStatus) {
            if ($item->id === $id) {
                $item->status = $newStatus;
            }
            return $item;
        });

        // Recalculate if everything is published
        $this->allPublished = $this->lessons->where('status', '!=', 'published')->count() === 0;
    }

    public function masterToggle(){

        $isAllPublished = $this->lessons->where('status', '!=', 'published')->count() === 0;

        if ($isAllPublished) {
            // Use the Model Query, not the collection, to avoid the Exception
            lesson::whereIn('id', $this->lessons->pluck('id'))->update(['status' => 'draft']);
        } else {
            lesson::whereIn('id', $this->lessons->pluck('id'))->update(['status' => 'published']);
        }

        // Refresh the local collection and the toggle state
        $this->lessons = lesson::where('chapter_id', $this->chapter->id)->get();
        $this->allPublished = $this->lessons->where('status', '!=', 'published')->count() === 0;

    }

    public function changeLesson($id){

        $selectedLesson = lesson::findOrFail($id);


        if($id!=$this->currentLesson->id)
        {

            $this->dispatch('LessonChanged',id:$id ,chapterId:$selectedLesson->chapter->id);
            $this->currentLesson = $selectedLesson;

        }


    }


};
?>

<div id="lessons-container-{{$chapter->id}}" class="lessons-list" x-show="open"
     @open-chapter-modal-{{$chapter->id}}.window="open = true"
     style="display:none;">

    @if($chapter->lessons->count() > 0)
        <div class="lesson-row bulk-lesson-action">
            <div style="width: 100%;">

                <button type="submit" class="btn-bulk-lessons" wire:click="masterToggle">
                    ⚡ Toggle All Lessons
                </button>
            </div>
        </div>
    @endif
    @foreach($lessons as $lesson)
        <div class="lesson-row" wire:key="lesson-{{$lesson->id}}" wire:click="changeLesson({{$lesson->id}})">
            <div class="lesson-content">
                <span class="bullet">•</span>
                <a class="lesson-link">{{ $lesson->title }}</a>

                <button type="button"
                        class="status-toggle-btn lesson-status {{ $lesson->status }}"
                        data-lesson-id="{{ $lesson->id }}"
                        data-chapter-id="{{ $chapter->id }}"
                        data-status="{{ $lesson->status }}"
                        wire:click="toggleStatus({{$lesson->id}})">
                    {{ ucfirst($lesson->status) }}
                </button>
            </div>
            <span class="pen-icon lesson-pen" @click.stop="openLessonModalId = {{$lesson->id}}">✏️</span>
        </div>

        <livewire:modular_site.lesson.lessonupdate :lesson="$lesson" :chapter="$chapter"/>

    @endforeach
    <livewire:modular_site.lesson.lessoncreate :chapter="$chapter"/>


</div>
