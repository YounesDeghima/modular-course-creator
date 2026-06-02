<?php

use App\Models\lesson_progress;
use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component {
    public $lesson;
    public $lesson_progress;


    public function mount($lesson, $lesson_progress)
    {
        $this->lesson = $lesson;
        $this->lesson_progress = $lesson_progress;
    }
    #[On('progressReached')]
    public function saveProgress($progress)
    {
        // 1. Ensure user is authenticated
        if (!auth()->check()) {
            return;
        }

        $userId = auth()->id();

        // 2. Safeguard against lowering progress (only update if it's an improvement)
        if ($this->lesson_progress && $this->lesson_progress->progress >= $progress) {
            return;
        }

        // 3. Logic to update or create the database record.
        // Adjust the model and column names below to match your actual database architecture.
        $this->lesson_progress = Lesson_Progress::updateOrCreate(
            [
                'user_id' => $userId,
                'lesson_id' => $this->lesson->id,
            ],
            [
                'progress' => $progress,
                'updated_at' => now(),
            ]
        );

        // 4. Optional: Dispatch a browser event if you want your frontend UI to update immediately
        $this->dispatch('progress-saved');
    }

};
?>
<div></div>


