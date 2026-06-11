<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Collection;
use Livewire\Component;

new class extends Component
{
    public User $user;
    public Collection $assignedSections;
    public Collection $mostProductiveStudents;
    public array $stats = [];

    public function mount(User $user)
    {
        $this->user = $user;
        $this->loadData();
    }

    public function loadData()
    {
        // Get assigned sections for this teacher
        $this->assignedSections = $this->user->assignedSections()

            ->get();

        // Get all students from assigned sections
        $studentIds = $this->assignedSections->map(function($section) {
            return $section->students()->pluck('id');
        })->flatten()->unique();

        // Calculate progress for each student using actual course progress method
        $courseIds = $this->assignedSections->pluck('id');
        $studentProgressData = [];

        foreach ($studentIds as $studentId) {
            $totalProgress = 0;
            $courseCount = 0;

            foreach ($courseIds as $courseId) {
                $course = Course::find($courseId);
                if ($course) {
                    $totalProgress += $course->progressForUser($studentId);
                    $courseCount++;
                }
            }

            if ($courseCount > 0) {
                $student = User::find($studentId);
                $studentProgressData[] = (object)[
                    'user_id' => $studentId,
                    'avg_progress' => round($totalProgress / $courseCount, 1),
                    'student' => $student
                ];
            }
        }

        // Sort by progress descending and take top 5
        $this->mostProductiveStudents = collect($studentProgressData)
            ->sortByDesc('avg_progress')
            ->take(5)
            ->values();

        // Calculate stats
        $this->calculateStats();
    }

    protected function calculateStats()
    {
        $totalStudents = $this->assignedSections->sum('students_count');
        $totalLessons = $this->assignedSections->sum('lessons_count');

        // Calculate average completion across all students
        $avgCompletion = $this->mostProductiveStudents->count() > 0
            ? round($this->mostProductiveStudents->avg('avg_progress'), 1)
            : 0;

        $this->stats = [
            'sections' => $this->assignedSections->count(),
            'students' => $totalStudents,
            'lessons' => $totalLessons,
            'avg_completion' => $avgCompletion,
        ];
    }

    #[\Livewire\Attributes\On('studentProgressUpdated')]
    public function refresh()
    {
        $this->loadData();
    }
};
?>
<div>
    <style>
        .teacher-stats-mini {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }

        .stat-mini {
            background: var(--bg-subtle);
            border-radius: 6px;
            padding: 10px;
            text-align: center;
        }

        .stat-mini-val {
            font-size: 18px;
            font-weight: 600;
            color: var(--text);
        }

        .stat-mini-label {
            font-size: 10px;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-top: 2px;
        }

        .sections-grid {
            display: grid;
            gap: 8px;
            margin-bottom: 16px;
            max-height: 120px;
            overflow-y: auto;
        }

        .section-item {
            background: var(--bg-subtle);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: background .13s, border-color .13s;
        }

        .section-item:hover {
            background: var(--bg-hover);
            border-color: var(--accent, #3C3489);
        }

        .section-name { font-weight: 500; color: var(--text); }
        .section-count { font-size: 11px; color: var(--text-muted); }

        .productivity-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }

        .productive-list { display: flex; flex-direction: column; gap: 8px; }

        .productive-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 8px;
            background: var(--bg-subtle);
            border-radius: 5px;
            font-size: 11px;
        }

        .prod-rank {
            font-weight: 600;
            color: var(--accent, #3C3489);
            min-width: 18px;
        }

        .prod-name { color: var(--text); font-weight: 500; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .prod-progress { color: var(--text-muted); font-size: 10px; }

        .completion-bar {
            width: 100%;
            height: 4px;
            background: var(--border);
            border-radius: 2px;
            margin-top: 4px;
            overflow: hidden;
        }

        .completion-fill {
            height: 100%;
            background: linear-gradient(90deg, #10B981, #6366F1);
            border-radius: 2px;
        }

        .empty-state {
            text-align: center;
            padding: 16px 8px;
            color: var(--text-muted);
            font-size: 12px;
        }

        .empty-state-icon { font-size: 24px; margin-bottom: 8px; }
    </style>

    <div class="teacher-stats-mini">
        <div class="stat-mini">
            <div class="stat-mini-val">{{ $this->stats['sections'] }}</div>
            <div class="stat-mini-label">Sections</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-val">{{ $this->stats['students'] }}</div>
            <div class="stat-mini-label">Students</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-val">{{ $this->stats['lessons'] }}</div>
            <div class="stat-mini-label">Lessons</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-val">{{ $this->stats['avg_completion'] }}%</div>
            <div class="stat-mini-label">Avg Progress</div>
        </div>
    </div>

    @if($this->assignedSections->count() > 0)
        <div>
            <div style="font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">
                Your sections
            </div>

            <div class="sections-grid">
                @foreach($this->assignedSections as $section)

                    <a href="#" class="section-item">
                        <div>
                            <div class="section-name">{{ $section->section_number }}</div>
                            <div class="section-count">{{ $section->students->count() }}</div>
                        </div>
                        <span style="color: var(--text-faint); font-size: 13px;">›</span>
                    </a>
                @endforeach
            </div>
        </div>

        @if($this->mostProductiveStudents->count() > 0)
            <div style="margin-top: 16px;">
                <div class="productivity-title">Most Productive Students</div>
                <div class="productive-list">
                    @foreach($this->mostProductiveStudents as $index => $record)
                        <div class="productive-item">
                            <div class="prod-rank">#{{ $index + 1 }}</div>
                            <div style="flex: 1; min-width: 0;">
                                <div class="prod-name">{{ $record->student->name ?? 'Unknown' }}</div>
                                <div class="prod-progress">{{ $record->avg_progress }}% progress</div>
                                <div class="completion-bar">
                                    <div class="completion-fill" style="width: {{ $record->avg_progress }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <div class="empty-state">
            <div class="empty-state-icon">📚</div>
            <div>No sections assigned yet</div>
        </div>
    @endif
</div>
