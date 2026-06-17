<?php

namespace App\Livewire\Teacher;

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
        $this->assignedSections = $this->user->assignedSections()->get();

        $studentIds = $this->assignedSections->map(function ($section) {
            return $section->students()->pluck('id');
        })->flatten()->unique();

        $courseIds = $this->assignedSections->pluck('id');
        $studentProgressData = [];

        foreach ($studentIds as $studentId) {
            $totalProgress = 0;
            $courseCount   = 0;

            foreach ($courseIds as $courseId) {
                $course = Course::find($courseId);
                if ($course) {
                    $totalProgress += $course->progressForUser($studentId);
                    $courseCount++;
                }
            }

            if ($courseCount > 0) {
                $student = User::find($studentId);
                $studentProgressData[] = (object) [
                    'user_id'      => $studentId,
                    'avg_progress' => round($totalProgress / $courseCount, 1),
                    'student'      => $student,
                ];
            }
        }

        $this->mostProductiveStudents = collect($studentProgressData)
            ->sortByDesc('avg_progress')
            ->take(5)
            ->values();

        $this->calculateStats();
    }

    protected function calculateStats()
    {
        $totalStudents = $this->assignedSections->sum('students_count');
        $totalLessons  = $this->assignedSections->sum('lessons_count');

        $avgCompletion = $this->mostProductiveStudents->count() > 0
            ? round($this->mostProductiveStudents->avg('avg_progress'), 1)
            : 0;

        $this->stats = [
            'sections'       => $this->assignedSections->count(),
            'students'       => $totalStudents,
            'lessons'        => $totalLessons,
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
        /* ── Stats row ───────────────────────────────────────── */
        .td-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 18px;
        }

        .td-stat {
            background: var(--bg-subtle);
            border-radius: 7px;
            padding: 10px 12px;
        }

        .td-stat.is-highlight {
            background: #EEEDFE;
        }

        .td-stat-val {
            font-size: 20px;
            font-weight: 500;
            color: var(--text);
            line-height: 1.2;
        }

        .td-stat.is-highlight .td-stat-val { color: #3C3489; }

        .td-stat-lbl {
            font-size: 10px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-top: 2px;
        }

        .td-stat.is-highlight .td-stat-lbl { color: #534AB7; }

        /* ── Section label ───────────────────────────────────── */
        .td-eyebrow {
            font-size: 10px;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 7px;
        }

        /* ── Sections list ───────────────────────────────────── */
        .td-sections {
            display: flex;
            flex-direction: column;
            gap: 5px;
            max-height: 136px;
            overflow-y: auto;
            margin-bottom: 16px;
        }

        .td-section-row {
            display: flex;
            align-items: center;
            gap: 9px;
            background: var(--bg-subtle);
            border: 1px solid var(--border);
            border-radius: 7px;
            padding: 7px 10px;
            text-decoration: none;
            transition: background .13s, border-color .13s;
        }

        .td-section-row:hover {
            background: var(--bg-hover);
            border-color: #3C3489;
        }

        .td-section-icon {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            background: #EEEDFE;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 13px;
        }

        .td-section-name {
            font-size: 12px;
            font-weight: 500;
            color: var(--text);
            flex: 1;
        }

        .td-section-pill {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 999px;
            background: #EEEDFE;
            color: #3C3489;
            font-weight: 500;
            flex-shrink: 0;
        }

        .td-section-chevron {
            font-size: 13px;
            color: var(--text-faint);
            flex-shrink: 0;
        }

        /* ── Divider ─────────────────────────────────────────── */
        .td-divider {
            height: 1px;
            background: var(--border);
            margin: 14px 0;
        }

        /* ── Leaderboard ─────────────────────────────────────── */
        .td-leaderboard {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .td-prod-row {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .td-prod-rank {
            font-size: 11px;
            font-weight: 500;
            color: var(--text-muted);
            min-width: 16px;
            text-align: right;
            flex-shrink: 0;
        }

        /* medal colours */
        .td-medal-1 { color: #B87D0A; }
        .td-medal-2 { color: #6B7280; }
        .td-medal-3 { color: #993C1D; }

        .td-avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #EEEDFE;
            color: #3C3489;
            font-size: 10px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            text-transform: uppercase;
        }

        .td-prod-info { flex: 1; min-width: 0; }

        .td-prod-name {
            font-size: 12px;
            font-weight: 500;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .td-prog-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 3px;
        }

        .td-prog-track {
            flex: 1;
            height: 3px;
            background: var(--border);
            border-radius: 2px;
            overflow: hidden;
        }

        .td-prog-fill {
            height: 100%;
            background: #3C3489;
            border-radius: 2px;
        }

        .td-prog-pct {
            font-size: 10px;
            color: var(--text-muted);
            min-width: 28px;
            text-align: right;
            flex-shrink: 0;
        }

        /* ── Empty state ─────────────────────────────────────── */
        .td-empty {
            text-align: center;
            padding: 20px 8px;
            color: var(--text-muted);
            font-size: 12px;
        }

        .td-empty-icon {
            font-size: 26px;
            margin-bottom: 8px;
        }
    </style>

    {{-- Stats row --}}
    <div class="td-stats">
        <div class="td-stat">
            <div class="td-stat-val">{{ $this->stats['sections'] }}</div>
            <div class="td-stat-lbl">Sections</div>
        </div>
        <div class="td-stat">
            <div class="td-stat-val">{{ $this->stats['students'] }}</div>
            <div class="td-stat-lbl">Students</div>
        </div>
        <div class="td-stat">
            <div class="td-stat-val">{{ $this->stats['lessons'] }}</div>
            <div class="td-stat-lbl">Lessons</div>
        </div>
        <div class="td-stat is-highlight">
            <div class="td-stat-val">{{ $this->stats['avg_completion'] }}%</div>
            <div class="td-stat-lbl">Avg progress</div>
        </div>
    </div>

    @if($this->assignedSections->count() > 0)

        {{-- Sections list --}}
        <div class="td-eyebrow">Your sections</div>
        <div class="td-sections">
            @foreach($this->assignedSections as $section)
                <a href="{{ route('teacher.section', $section->id) }}" class="td-section-row">
                    <div class="td-section-icon">📚</div>
                    <span class="td-section-name">Section {{ $section->section_number }}</span>
                    <span class="td-section-pill">{{ $section->students->count() }} students</span>
                    <span class="td-section-chevron">›</span>
                </a>
            @endforeach
        </div>

        {{-- Leaderboard --}}
        @if($this->mostProductiveStudents->count() > 0)
            <div class="td-divider"></div>
            <div class="td-eyebrow">Most productive students</div>
            <div class="td-leaderboard">
                @foreach($this->mostProductiveStudents as $index => $record)
                    @php
                        $initials = collect(explode(' ', $record->student->name ?? 'U'))
                            ->map(fn($w) => strtoupper(substr($w, 0, 1)))
                            ->take(2)
                            ->implode('');

                        $avatarPalette = [
                            ['bg' => '#EEEDFE', 'color' => '#3C3489'],
                            ['bg' => '#E6F1FB', 'color' => '#0C447C'],
                            ['bg' => '#FAECE7', 'color' => '#993C1D'],
                            ['bg' => '#EAF3DE', 'color' => '#3B6D11'],
                            ['bg' => '#FAEEDA', 'color' => '#854F0B'],
                        ];
                        $palette = $avatarPalette[$index % count($avatarPalette)];
                    @endphp

                    <div class="td-prod-row">
                        {{-- Rank / medal --}}
                        <div class="td-prod-rank">
                            @if($index === 0)
                                <span class="td-medal-1" title="1st">🥇</span>
                            @elseif($index === 1)
                                <span class="td-medal-2" title="2nd">🥈</span>
                            @elseif($index === 2)
                                <span class="td-medal-3" title="3rd">🥉</span>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </div>

                        {{-- Avatar --}}
                        <div class="td-avatar"
                             style="background: {{ $palette['bg'] }}; color: {{ $palette['color'] }};">
                            {{ $initials }}
                        </div>

                        {{-- Name + progress bar --}}
                        <div class="td-prod-info">
                            <div class="td-prod-name">{{ $record->student->name ?? 'Unknown' }}</div>
                            <div class="td-prog-wrap">
                                <div class="td-prog-track">
                                    <div class="td-prog-fill"
                                         style="width: {{ $record->avg_progress }}%">
                                    </div>
                                </div>
                                <span class="td-prog-pct">{{ $record->avg_progress }}%</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    @else
        <div class="td-empty">
            <div class="td-empty-icon">📚</div>
            <div>No sections assigned yet</div>
        </div>
    @endif
</div>
