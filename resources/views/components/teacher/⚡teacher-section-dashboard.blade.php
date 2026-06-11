<?php

namespace App\Livewire\Teacher;

use Livewire\Component;
use App\Models\user;
use App\Models\section;

new class extends Component
{
    public $user;
    public $selectedSection = null;
    public $search          = '';
    public $sortBy          = 'name';
    public $sortDir         = 'asc';
    public $filterProgress  = 'all'; // all | at_risk | on_track | completed
    public int $page        = 1;
    public int $perPage     = 15;

    // Plain-array public properties — Livewire can serialize these fine
    public array $sections     = [];
    public array $students     = [];
    public int   $totalStudents = 0;
    public array $sectionStats = [
        'total'       => 0,
        'avgProgress' => 0,
        'completed'   => 0,
        'atRisk'      => 0,
    ];

    public function mount($user, $initialSection = null)
    {
        $this->user     = $user;
        $this->sections = $this->user->assignedsections()->get()
            ->map(fn($s) => ['id' => $s->id, 'section_number' => $s->section_number, 'student_count' => $s->student_count()])
            ->toArray();

        // Use the passed section if given, otherwise fall back to first
        if ($initialSection && collect($this->sections)->firstWhere('id', $initialSection)) {
            $this->selectedSection = $initialSection;
        } elseif (! empty($this->sections)) {
            $this->selectedSection = $this->sections[0]['id'];
        }

        $this->loadSectionStats();
        $this->loadStudents();
    }

    public function selectSection($sectionId)
    {
        $this->selectedSection = $sectionId;
        $this->page = 1;
        $this->loadSectionStats();
        $this->loadStudents();
    }

    public function sortColumn($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy  = $column;
            $this->sortDir = 'asc';
        }
        $this->page = 1;
        $this->loadStudents();
    }

    public function updatedSearch()
    {
        $this->page = 1;
        $this->loadStudents();
    }

    public function updatedFilterProgress()
    {
        $this->page = 1;
        $this->loadStudents();
    }

    public function nextPage()
    {
        $lastPage = (int) ceil($this->totalStudents / $this->perPage);
        if ($this->page < $lastPage) {
            $this->page++;
            $this->loadStudents();
        }
    }

    public function prevPage()
    {
        if ($this->page > 1) {
            $this->page--;
            $this->loadStudents();
        }
    }

    public function goToPage(int $p)
    {
        $this->page = $p;
        $this->loadStudents();
    }

    private function buildStudentCollection()
    {
        if (! $this->selectedSection) {
            return collect();
        }

        $students = user::where('section_id', $this->selectedSection)
            ->with(['enrolledCourses.chapters.lessons'])
            ->when($this->search, fn($q) =>
            $q->where(fn($inner) =>
            $inner->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
            )
            )
            ->orderBy(
                $this->sortBy === 'progress' ? 'name' : $this->sortBy,
                $this->sortDir
            )
            ->get()
            ->map(function ($student) {
                $courses = $student->enrolledCourses;
                if ($courses->isEmpty()) {
                    $student->avg_progress = 0;
                } else {
                    $total = $courses->sum(fn($c) => $c->progressForUser($student->id));
                    $student->avg_progress = (int) round($total / $courses->count());
                }
                return $student;
            });

        if ($this->filterProgress !== 'all') {
            $students = $students->filter(fn($s) => match ($this->filterProgress) {
                'completed' => $s->avg_progress >= 100,
                'on_track'  => $s->avg_progress >= 30 && $s->avg_progress < 100,
                'at_risk'   => $s->avg_progress < 30,
                default     => true,
            });
        }

        if ($this->sortBy === 'progress') {
            $students = $this->sortDir === 'asc'
                ? $students->sortBy('avg_progress')
                : $students->sortByDesc('avg_progress');
        }

        return $students->values();
    }

    public function loadStudents()
    {
        $all = $this->buildStudentCollection();

        $this->totalStudents = $all->count();

        $this->students = $all
            ->slice(($this->page - 1) * $this->perPage, $this->perPage)
            ->map(fn($s) => [
                'id'           => $s->id,
                'name'         => $s->name,
                'last_name'    => $s->last_name,
                'email'        => $s->email,
                'avg_progress' => $s->avg_progress,
                'created_at'   => $s->created_at->format('M d, Y'),
            ])
            ->values()
            ->toArray();
    }

    public function loadSectionStats()
    {
        if (! $this->selectedSection) {
            $this->sectionStats = ['total' => 0, 'avgProgress' => 0, 'completed' => 0, 'atRisk' => 0];
            return;
        }

        $students = $this->buildStudentCollection();
        $total       = $students->count();
        $completed   = $students->filter(fn($s) => $s->avg_progress >= 100)->count();
        $atRisk      = $students->filter(fn($s) => $s->avg_progress < 30)->count();
        $avgProgress = $total ? (int) round($students->avg('avg_progress')) : 0;

        $this->sectionStats = compact('total', 'completed', 'atRisk', 'avgProgress');
    }

}?>

<div class="teacher-dashboard">

    {{-- ── Section Tabs ───────────────────────────────────────────────── --}}
    @if(empty($sections))
        <div class="empty-state">
            <span style="font-size:32px;">📋</span>
            <p>You haven't been assigned any sections yet.</p>
        </div>
    @else

        <div class="section-tabs">
            @foreach($sections as $section)
                <button
                    wire:click="selectSection({{ $section['id'] }})"
                    @class(['section-tab', 'active' => $selectedSection === $section['id']])
                >
                    <span class="tab-name">Section {{ $section['section_number'] }}</span>
                    <span class="tab-count">{{ $section['student_count'] }}</span>
                </button>
            @endforeach
        </div>

        @if($selectedSection)

            {{-- ── Stats Row ──────────────────────────────────────────────────── --}}
            <div class="ts-stats">
                <div class="ts-stat">
                    <div class="ts-stat-val">{{ $sectionStats['total'] }}</div>
                    <div class="ts-stat-label">Students</div>
                </div>
                <div class="ts-stat">
                    <div class="ts-stat-val" style="color:var(--accent-green)">{{ $sectionStats['avgProgress'] }}%</div>
                    <div class="ts-stat-label">Avg. progress</div>
                </div>
                <div class="ts-stat">
                    <div class="ts-stat-val" style="color:#3C3489">{{ $sectionStats['completed'] }}</div>
                    <div class="ts-stat-label">Completed</div>
                </div>
                <div class="ts-stat">
                    <div class="ts-stat-val" style="color:var(--accent-red)">{{ $sectionStats['atRisk'] }}</div>
                    <div class="ts-stat-label">At risk</div>
                </div>
            </div>

            {{-- ── Toolbar ─────────────────────────────────────────────────────── --}}
            <div class="ts-toolbar">
                <div class="ts-search-wrap">
                    <span class="ts-search-icon">🔍</span>
                    <input
                        wire:model.live.debounce.300ms="search"
                        class="ts-search"
                        type="text"
                        placeholder="Search students…"
                    >
                </div>

                <div class="ts-filters">
                    @foreach(['all' => 'All', 'on_track' => 'On track', 'at_risk' => 'At risk', 'completed' => 'Done'] as $val => $label)
                        <button
                            wire:click="$set('filterProgress','{{ $val }}')"
                            @class(['ts-filter-btn', 'active' => $filterProgress === $val])
                        >{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            {{-- ── Student Table ───────────────────────────────────────────────── --}}
            <div class="ts-table-wrap">
                <table class="ts-table">
                    <thead>
                    <tr>
                        <th wire:click="sortColumn('name')" class="sortable">
                            Student
                            @if($sortBy === 'name')
                                <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th wire:click="sortColumn('progress')" class="sortable">
                            Progress
                            @if($sortBy === 'progress')
                                <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th wire:click="sortColumn('created_at')" class="sortable">
                            Joined
                            @if($sortBy === 'created_at')
                                <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($students as $student)
                        @php
                            $avg    = $student['avg_progress'];
                            $status = $avg >= 100 ? 'completed' : ($avg < 30 ? 'at_risk' : 'on_track');
                            $statusLabels = ['completed' => 'Completed', 'at_risk' => 'At risk', 'on_track' => 'On track'];
                        @endphp
                        <tr wire:key="student-{{ $student['id'] }}">
                            <td>
                                <div class="student-cell">
                                    <div class="student-avatar">{{ strtoupper(substr($student['name'], 0, 1)) }}</div>
                                    <div>
                                        <div class="student-name">{{ $student['name'] }} {{ $student['last_name'] }}</div>
                                        <div class="student-email">{{ $student['email'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="progress-cell">
                                    <div class="progress-bar-wrap">
                                        <div
                                            class="progress-bar-fill"
                                            style="width:{{ $avg }}%;background:{{ $avg >= 100 ? 'var(--accent-green)' : ($avg < 30 ? 'var(--accent-red)' : '#3C3489') }}"
                                        ></div>
                                    </div>
                                    <span class="progress-pct">{{ $avg }}%</span>
                                </div>
                            </td>
                            <td class="ts-muted">{{ $student['created_at'] }}</td>
                            <td>
                                <span class="status-badge status-{{ $status }}">
                                    {{ $statusLabels[$status] }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.userProfile', ['userid' => $student['id']]) }}" class="ts-action-btn">
                                    View →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ts-empty-row">No students match your search.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ── Pagination ──────────────────────────────────────────────────── --}}
            @php
                $lastPage = max(1, (int) ceil($totalStudents / $perPage));
                $from     = $totalStudents ? ($page - 1) * $perPage + 1 : 0;
                $to       = min($page * $perPage, $totalStudents);
            @endphp
            @if($lastPage > 1)
                <div class="ts-pagination">
                    <span class="ts-page-info">{{ $from }}–{{ $to }} of {{ $totalStudents }}</span>
                    <button wire:click="prevPage" class="ts-page-btn" @disabled($page <= 1)>‹ Prev</button>
                    @for($p = 1; $p <= $lastPage; $p++)
                        <button
                            wire:click="goToPage({{ $p }})"
                            @class(['ts-page-btn', 'active' => $page === $p])
                        >{{ $p }}</button>
                    @endfor
                    <button wire:click="nextPage" class="ts-page-btn" @disabled($page >= $lastPage)>Next ›</button>
                </div>
            @endif

        @endif
    @endif

</div>

<style>
    .teacher-dashboard { display: flex; flex-direction: column; gap: 16px; }

    /* ── Section tabs ── */
    .section-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
    .section-tab {
        display: flex; align-items: center; gap: 6px;
        padding: 6px 12px; border-radius: 7px; border: 1px solid var(--border);
        background: var(--bg-subtle); cursor: pointer;
        font-size: 12.5px; color: var(--text-muted); transition: all .13s;
    }
    .section-tab:hover { background: var(--bg-hover); color: var(--text); }
    .section-tab.active { background: #3C3489; color: #fff; border-color: #3C3489; }
    .tab-count {
        font-size: 10px; padding: 1px 6px; border-radius: 999px;
        background: rgba(255,255,255,.25); font-weight: 600;
    }
    .section-tab:not(.active) .tab-count { background: var(--border); color: var(--text-muted); }

    /* ── Stats ── */
    .ts-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
    @media (max-width: 600px) { .ts-stats { grid-template-columns: repeat(2, 1fr); } }
    .ts-stat {
        background: var(--bg-subtle); border-radius: 8px;
        padding: 12px 14px; text-align: center;
    }
    .ts-stat-val   { font-size: 22px; font-weight: 600; color: var(--text); }
    .ts-stat-label { font-size: 11px; color: var(--text-muted); margin-top: 2px; text-transform: uppercase; letter-spacing: .05em; }

    /* ── Toolbar ── */
    .ts-toolbar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .ts-search-wrap { position: relative; flex: 1; min-width: 180px; }
    .ts-search-icon { position: absolute; left: 9px; top: 50%; transform: translateY(-50%); font-size: 12px; }
    .ts-search {
        width: 100%; padding: 7px 10px 7px 28px; border: 1px solid var(--border);
        border-radius: 7px; background: var(--bg-subtle); color: var(--text);
        font-size: 12.5px; outline: none;
    }
    .ts-search:focus { border-color: #3C3489; }
    .ts-filters { display: flex; gap: 4px; }
    .ts-filter-btn {
        padding: 5px 10px; border-radius: 6px; border: 1px solid var(--border);
        font-size: 11.5px; color: var(--text-muted); background: transparent; cursor: pointer; transition: all .13s;
    }
    .ts-filter-btn:hover { background: var(--bg-hover); }
    .ts-filter-btn.active { background: #3C3489; color: #fff; border-color: #3C3489; }

    /* ── Table ── */
    .ts-table-wrap { overflow-x: auto; border: 1px solid var(--border); border-radius: 8px; }
    .ts-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .ts-table thead { background: var(--bg-subtle); }
    .ts-table th {
        padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 500;
        color: var(--text-muted); text-transform: uppercase; letter-spacing: .05em;
        border-bottom: 1px solid var(--border);
    }
    .ts-table th.sortable { cursor: pointer; user-select: none; }
    .ts-table th.sortable:hover { color: var(--text); }
    .ts-table td { padding: 10px 14px; border-bottom: 1px solid var(--border-mid); vertical-align: middle; }
    .ts-table tr:last-child td { border-bottom: none; }
    .ts-table tbody tr:hover { background: var(--bg-hover); }

    /* ── Student cell ── */
    .student-cell { display: flex; align-items: center; gap: 8px; }
    .student-avatar {
        width: 28px; height: 28px; border-radius: 50%;
        background: #EEEDFE; color: #3C3489;
        font-size: 11px; font-weight: 600;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        text-transform: uppercase;
    }
    [data-theme="dark"] .student-avatar { background: #3C3489; color: #CECBF6; }
    .student-name  { font-size: 12.5px; font-weight: 500; color: var(--text); }
    .student-email { font-size: 11px; color: var(--text-muted); }
    .ts-muted { color: var(--text-muted); font-size: 12px; }

    /* ── Progress bar ── */
    .progress-cell { display: flex; align-items: center; gap: 8px; }
    .progress-bar-wrap { flex: 1; min-width: 80px; height: 5px; background: var(--border); border-radius: 999px; overflow: hidden; }
    .progress-bar-fill { height: 100%; border-radius: 999px; transition: width .3s; }
    .progress-pct { font-size: 11px; color: var(--text-muted); min-width: 30px; text-align: right; }

    /* ── Status badges ── */
    .status-badge { font-size: 10px; padding: 2px 8px; border-radius: 999px; font-weight: 500; }
    .status-completed { background: #D1FAE5; color: #065F46; }
    .status-on_track  { background: #EEEDFE; color: #3C3489; }
    .status-at_risk   { background: #FEE2E2; color: #991B1B; }
    [data-theme="dark"] .status-completed { background: #065F46; color: #A7F3D0; }
    [data-theme="dark"] .status-on_track  { background: #3C3489; color: #CECBF6; }
    [data-theme="dark"] .status-at_risk   { background: #991B1B; color: #FECACA; }

    /* ── Action & empty ── */
    .ts-action-btn {
        font-size: 12px; color: #3C3489; text-decoration: none; font-weight: 500;
        padding: 4px 8px; border-radius: 5px; transition: background .13s;
    }
    .ts-action-btn:hover { background: #EEEDFE; }
    [data-theme="dark"] .ts-action-btn:hover { background: #3C3489; color: #CECBF6; }
    .ts-empty-row { text-align: center; color: var(--text-muted); padding: 24px; }

    /* ── Pagination ── */
    .ts-pagination { display: flex; align-items: center; gap: 4px; justify-content: flex-end; }
    .ts-page-info { font-size: 11.5px; color: var(--text-muted); margin-right: 8px; }
    .ts-page-btn {
        padding: 4px 10px; border-radius: 6px; border: 1px solid var(--border);
        font-size: 12px; color: var(--text-muted); background: transparent; cursor: pointer; transition: all .13s;
    }
    .ts-page-btn:hover:not(:disabled) { background: var(--bg-hover); }
    .ts-page-btn.active { background: #3C3489; color: #fff; border-color: #3C3489; }
    .ts-page-btn:disabled { opacity: .4; cursor: default; }

    /* ── Empty state ── */
    .empty-state {
        display: flex; flex-direction: column; align-items: center;
        gap: 8px; padding: 32px; color: var(--text-muted); font-size: 13px;
    }

    /* ── CSS vars fallbacks ── */
    :root {
        --accent-green: #059669;
        --accent-red:   #DC2626;
        --border-mid:   color-mix(in srgb, var(--border) 60%, transparent);
    }
</style>
