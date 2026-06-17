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
        <div class="ts-empty-state">
            <span style="font-size:36px;">📋</span>
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
                    <div class="ts-stat-icon">👥</div>
                    <div class="ts-stat-val">{{ $sectionStats['total'] }}</div>
                    <div class="ts-stat-label">Students</div>
                </div>
                <div class="ts-stat ts-stat--green">
                    <div class="ts-stat-icon">📈</div>
                    <div class="ts-stat-val">{{ $sectionStats['avgProgress'] }}%</div>
                    <div class="ts-stat-label">Avg. progress</div>
                </div>
                <div class="ts-stat ts-stat--purple">
                    <div class="ts-stat-icon">✅</div>
                    <div class="ts-stat-val">{{ $sectionStats['completed'] }}</div>
                    <div class="ts-stat-label">Completed</div>
                </div>
                <div class="ts-stat ts-stat--red">
                    <div class="ts-stat-icon">⚠️</div>
                    <div class="ts-stat-val">{{ $sectionStats['atRisk'] }}</div>
                    <div class="ts-stat-label">At risk</div>
                </div>
            </div>

            {{-- ── Charts Row ─────────────────────────────────────────────────── --}}
            @php
                $total     = max(1, $sectionStats['total']);
                $completed = $sectionStats['completed'];
                $atRisk    = $sectionStats['atRisk'];
                $onTrack   = $total - $completed - $atRisk;
                $onTrack   = max(0, $onTrack);

                $completedPct = round($completed / $total * 100);
                $atRiskPct    = round($atRisk    / $total * 100);
                $onTrackPct   = 100 - $completedPct - $atRiskPct;

                // Donut arc helpers (cx=50,cy=50,r=38 → circumference=238.76)
                $circ         = 238.76;
                $gap          = 3;
                $segments = [
                    ['pct' => $completedPct, 'color' => '#059669', 'label' => 'Completed'],
                    ['pct' => $onTrackPct,   'color' => '#3C3489', 'label' => 'On track'],
                    ['pct' => $atRiskPct,    'color' => '#DC2626', 'label' => 'At risk'],
                ];
                $offset = 0;
            @endphp

            <div class="ts-charts-row">

                {{-- Donut: status breakdown --}}
                <div class="ts-chart-card">
                    <div class="ts-chart-title">Status breakdown</div>
                    <div class="ts-donut-wrap">
                        <svg viewBox="0 0 100 100" class="ts-donut-svg">
                            @foreach($segments as $seg)
                                @php
                                    $dash   = max(0, ($seg['pct'] / 100) * $circ - $gap);
                                    $space  = $circ - $dash;
                                    $rotate = ($offset / 100) * 360 - 90;
                                    $offset += $seg['pct'];
                                @endphp
                                <circle
                                    cx="50" cy="50" r="38"
                                    fill="none"
                                    stroke="{{ $seg['color'] }}"
                                    stroke-width="10"
                                    stroke-dasharray="{{ $dash }} {{ $space }}"
                                    stroke-linecap="round"
                                    transform="rotate({{ $rotate }}, 50, 50)"
                                />
                            @endforeach
                            {{-- Centre label --}}
                            <text x="50" y="46" text-anchor="middle" class="donut-centre-val">{{ $sectionStats['avgProgress'] }}%</text>
                            <text x="50" y="57" text-anchor="middle" class="donut-centre-lbl">avg</text>
                        </svg>
                        <div class="ts-donut-legend">
                            <div class="ts-legend-item">
                                <span class="ts-legend-dot" style="background:#059669"></span>
                                <span class="ts-legend-label">Completed</span>
                                <span class="ts-legend-val">{{ $completedPct }}%</span>
                            </div>
                            <div class="ts-legend-item">
                                <span class="ts-legend-dot" style="background:#3C3489"></span>
                                <span class="ts-legend-label">On track</span>
                                <span class="ts-legend-val">{{ $onTrackPct }}%</span>
                            </div>
                            <div class="ts-legend-item">
                                <span class="ts-legend-dot" style="background:#DC2626"></span>
                                <span class="ts-legend-label">At risk</span>
                                <span class="ts-legend-val">{{ $atRiskPct }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bar chart: progress distribution buckets --}}
                @php
                    $allStudentsForChart = $this->buildStudentCollection();
                    $buckets = [
                        '0–24%'   => $allStudentsForChart->filter(fn($s) => $s->avg_progress < 25)->count(),
                        '25–49%'  => $allStudentsForChart->filter(fn($s) => $s->avg_progress >= 25 && $s->avg_progress < 50)->count(),
                        '50–74%'  => $allStudentsForChart->filter(fn($s) => $s->avg_progress >= 50 && $s->avg_progress < 75)->count(),
                        '75–99%'  => $allStudentsForChart->filter(fn($s) => $s->avg_progress >= 75 && $s->avg_progress < 100)->count(),
                        '100%'    => $allStudentsForChart->filter(fn($s) => $s->avg_progress >= 100)->count(),
                    ];
                    $maxBucket = max(1, max(array_values($buckets)));
                    $bucketColors = ['#DC2626','#F97316','#EAB308','#3C3489','#059669'];
                @endphp
                <div class="ts-chart-card">
                    <div class="ts-chart-title">Progress distribution</div>
                    <div class="ts-bar-chart">
                        @foreach($buckets as $label => $count)
                            @php $heightPct = round($count / $maxBucket * 100); @endphp
                            <div class="ts-bar-col">
                                <div class="ts-bar-count">{{ $count }}</div>
                                <div class="ts-bar-track">
                                    <div
                                        class="ts-bar-fill"
                                        style="height:{{ $heightPct }}%;background:{{ $bucketColors[$loop->index] }}"
                                    ></div>
                                </div>
                                <div class="ts-bar-label">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Gauge: average progress --}}
                @php
                    $avg        = $sectionStats['avgProgress'];
                    // Semi-circle: r=36, circumference of half = π*r = 113.1
                    $halfCirc   = 113.1;
                    $gaugeDash  = ($avg / 100) * $halfCirc;
                    $gaugeColor = $avg >= 75 ? '#059669' : ($avg >= 40 ? '#3C3489' : '#DC2626');
                @endphp
                <div class="ts-chart-card">
                    <div class="ts-chart-title">Section progress</div>
                    <div class="ts-gauge-wrap">
                        <svg viewBox="0 0 100 60" class="ts-gauge-svg">
                            {{-- Track --}}
                            <path
                                d="M 14 54 A 36 36 0 0 1 86 54"
                                fill="none" stroke="var(--border)" stroke-width="10"
                                stroke-linecap="round"
                            />
                            {{-- Fill --}}
                            <path
                                d="M 14 54 A 36 36 0 0 1 86 54"
                                fill="none"
                                stroke="{{ $gaugeColor }}"
                                stroke-width="10"
                                stroke-linecap="round"
                                stroke-dasharray="{{ $gaugeDash }} {{ $halfCirc }}"
                            />
                            <text x="50" y="50" text-anchor="middle" class="gauge-val">{{ $avg }}%</text>
                        </svg>
                        <div class="ts-gauge-labels">
                            <span>0%</span><span>100%</span>
                        </div>
                        <div class="ts-gauge-caption">
                            @if($avg >= 75) 🎉 Excellent progress
                            @elseif($avg >= 40) 📘 Good momentum
                            @else ⚡ Needs attention
                            @endif
                        </div>
                    </div>
                </div>

            </div>{{-- end charts row --}}

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
                                <span class="sort-arrow">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span class="sort-arrow sort-arrow--faint">↕</span>
                            @endif
                        </th>
                        <th wire:click="sortColumn('progress')" class="sortable">
                            Progress
                            @if($sortBy === 'progress')
                                <span class="sort-arrow">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span class="sort-arrow sort-arrow--faint">↕</span>
                            @endif
                        </th>
                        <th wire:click="sortColumn('created_at')" class="sortable">
                            Joined
                            @if($sortBy === 'created_at')
                                <span class="sort-arrow">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span class="sort-arrow sort-arrow--faint">↕</span>
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
                                            style="width:{{ $avg }}%;background:{{ $avg >= 100 ? '#059669' : ($avg < 30 ? '#DC2626' : '#3C3489') }}"
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
    /* ═══════════════════════════════════════════════════════════
       CSS VARIABLES — fallbacks matching app design system
    ═══════════════════════════════════════════════════════════ */
    :root {
        --accent-green: #059669;
        --accent-red:   #DC2626;
        --accent-orange:#F97316;
        --border-mid:   color-mix(in srgb, var(--border) 60%, transparent);
    }

    /* ═══════════════════════════════════════════════════════════
       LAYOUT
    ═══════════════════════════════════════════════════════════ */
    .teacher-dashboard {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    /* ═══════════════════════════════════════════════════════════
       SECTION TABS
    ═══════════════════════════════════════════════════════════ */
    .section-tabs {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    .section-tab {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 7px;
        border: 1px solid var(--border);
        background: var(--bg-subtle);
        cursor: pointer;
        font-size: 12.5px;
        font-family: inherit;
        color: var(--text-muted);
        transition: background .13s, border-color .13s, color .13s;
    }

    .section-tab:hover {
        background: var(--bg-hover);
        color: var(--text);
    }

    .section-tab.active {
        background: #3C3489;
        color: #fff;
        border-color: #3C3489;
        font-weight: 500;
        box-shadow: 0 2px 8px rgba(60, 52, 137, .25);
    }

    .tab-count {
        font-size: 10px;
        padding: 1px 7px;
        border-radius: 999px;
        background: rgba(255,255,255,.25);
        font-weight: 600;
    }

    .section-tab:not(.active) .tab-count {
        background: var(--border);
        color: var(--text-muted);
    }

    /* ═══════════════════════════════════════════════════════════
       STATS ROW
    ═══════════════════════════════════════════════════════════ */
    .ts-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }

    @media (max-width: 600px) {
        .ts-stats { grid-template-columns: repeat(2, 1fr); }
    }

    .ts-stat {
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 3px;
        transition: box-shadow .15s;
    }

    .ts-stat:hover {
        box-shadow: 0 2px 12px var(--shadow);
    }

    .ts-stat-icon {
        font-size: 16px;
        margin-bottom: 2px;
    }

    .ts-stat-val {
        font-size: 24px;
        font-weight: 600;
        color: var(--text);
        line-height: 1.1;
    }

    .ts-stat-label {
        font-size: 11px;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .ts-stat--green { border-color: rgba(5,150,105,.25); }
    .ts-stat--green .ts-stat-val { color: var(--accent-green); }

    .ts-stat--purple { border-color: rgba(60,52,137,.25); }
    .ts-stat--purple .ts-stat-val { color: #3C3489; }

    .ts-stat--red { border-color: rgba(220,38,38,.2); }
    .ts-stat--red .ts-stat-val { color: var(--accent-red); }

    [data-theme="dark"] .ts-stat--green { border-color: rgba(5,150,105,.35); }
    [data-theme="dark"] .ts-stat--purple { border-color: rgba(100,90,200,.35); }
    [data-theme="dark"] .ts-stat--red { border-color: rgba(220,38,38,.35); }

    /* ═══════════════════════════════════════════════════════════
       CHARTS ROW
    ═══════════════════════════════════════════════════════════ */
    .ts-charts-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 12px;
    }

    @media (max-width: 860px) {
        .ts-charts-row { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 560px) {
        .ts-charts-row { grid-template-columns: 1fr; }
    }

    .ts-chart-card {
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .ts-chart-title {
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--text-muted);
    }

    /* ── Donut ── */
    .ts-donut-wrap {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .ts-donut-svg {
        width: 90px;
        height: 90px;
        flex-shrink: 0;
    }

    .donut-centre-val {
        font-size: 14px;
        font-weight: 700;
        fill: var(--text);
    }

    .donut-centre-lbl {
        font-size: 7px;
        fill: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .ts-donut-legend {
        display: flex;
        flex-direction: column;
        gap: 7px;
        flex: 1;
    }

    .ts-legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 11.5px;
    }

    .ts-legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .ts-legend-label {
        flex: 1;
        color: var(--text-muted);
    }

    .ts-legend-val {
        font-weight: 600;
        color: var(--text);
        font-size: 11px;
    }

    /* ── Bar chart ── */
    .ts-bar-chart {
        display: flex;
        align-items: flex-end;
        gap: 6px;
        height: 90px;
        padding-bottom: 0;
    }

    .ts-bar-col {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        flex: 1;
    }

    .ts-bar-count {
        font-size: 10px;
        font-weight: 600;
        color: var(--text-muted);
    }

    .ts-bar-track {
        width: 100%;
        height: 56px;
        background: var(--border);
        border-radius: 4px 4px 0 0;
        display: flex;
        align-items: flex-end;
        overflow: hidden;
    }

    .ts-bar-fill {
        width: 100%;
        border-radius: 4px 4px 0 0;
        transition: height .4s ease;
        min-height: 2px;
    }

    .ts-bar-label {
        font-size: 9px;
        color: var(--text-muted);
        text-align: center;
        white-space: nowrap;
    }

    /* ── Gauge ── */
    .ts-gauge-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }

    .ts-gauge-svg {
        width: 140px;
        height: 85px;
    }

    .gauge-val {
        font-size: 14px;
        font-weight: 700;
        fill: var(--text);
    }

    .ts-gauge-labels {
        display: flex;
        justify-content: space-between;
        width: 130px;
        font-size: 9px;
        color: var(--text-muted);
        margin-top: -4px;
    }

    .ts-gauge-caption {
        font-size: 11.5px;
        color: var(--text-muted);
        font-weight: 500;
        text-align: center;
    }

    /* ═══════════════════════════════════════════════════════════
       TOOLBAR
    ═══════════════════════════════════════════════════════════ */
    .ts-toolbar {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .ts-search-wrap {
        position: relative;
        flex: 1;
        min-width: 180px;
    }

    .ts-search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 12px;
        pointer-events: none;
    }

    .ts-search {
        width: 100%;
        padding: 8px 12px 8px 30px;
        border: 1px solid var(--border);
        border-radius: 7px;
        background: var(--bg-subtle);
        color: var(--text);
        font-size: 13px;
        font-family: inherit;
        outline: none;
        transition: border-color .15s, background .15s;
    }

    .ts-search:focus {
        border-color: #3C3489;
        background: var(--bg);
    }

    .ts-filters {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
    }

    .ts-filter-btn {
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid var(--border);
        font-size: 12px;
        font-family: inherit;
        color: var(--text-muted);
        background: transparent;
        cursor: pointer;
        transition: background .13s, color .13s, border-color .13s;
    }

    .ts-filter-btn:hover {
        background: var(--bg-hover);
        color: var(--text);
    }

    .ts-filter-btn.active {
        background: #3C3489;
        color: #fff;
        border-color: #3C3489;
        font-weight: 500;
    }

    /* ═══════════════════════════════════════════════════════════
       TABLE
    ═══════════════════════════════════════════════════════════ */
    .ts-table-wrap {
        overflow-x: auto;
        border: 1px solid var(--border);
        border-radius: 10px;
    }

    .ts-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .ts-table thead {
        background: var(--bg-subtle);
    }

    .ts-table th {
        padding: 10px 14px;
        text-align: left;
        font-size: 11px;
        font-weight: 500;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .05em;
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
    }

    .ts-table th.sortable {
        cursor: pointer;
        user-select: none;
        transition: color .13s;
    }

    .ts-table th.sortable:hover {
        color: var(--text);
    }

    .ts-table td {
        padding: 11px 14px;
        border-bottom: 1px solid var(--border-mid);
        vertical-align: middle;
        color: var(--text);
    }

    .ts-table tr:last-child td {
        border-bottom: none;
    }

    .ts-table tbody tr {
        transition: background .1s;
    }

    .ts-table tbody tr:hover td {
        background: var(--bg-subtle);
    }

    .sort-arrow {
        margin-left: 3px;
        font-size: 10px;
    }

    .sort-arrow--faint {
        opacity: .3;
    }

    /* ── Student cell ── */
    .student-cell {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .student-avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #EEEDFE;
        color: #3C3489;
        font-size: 11px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        text-transform: uppercase;
    }

    [data-theme="dark"] .student-avatar {
        background: #3C3489;
        color: #CECBF6;
    }

    .student-name  { font-size: 13px; font-weight: 500; color: var(--text); }
    .student-email { font-size: 11px; color: var(--text-muted); }
    .ts-muted      { color: var(--text-muted); font-size: 12px; }

    /* ── Progress bar ── */
    .progress-cell {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .progress-bar-wrap {
        flex: 1;
        min-width: 80px;
        height: 5px;
        background: var(--border);
        border-radius: 999px;
        overflow: hidden;
    }

    .progress-bar-fill {
        height: 100%;
        border-radius: 999px;
        transition: width .3s ease;
    }

    .progress-pct {
        font-size: 11px;
        color: var(--text-muted);
        min-width: 32px;
        text-align: right;
        font-weight: 500;
    }

    /* ── Status badges ── */
    .status-badge {
        font-size: 10.5px;
        padding: 3px 9px;
        border-radius: 999px;
        font-weight: 500;
    }

    .status-completed { background: #D1FAE5; color: #065F46; }
    .status-on_track  { background: #EEEDFE; color: #3C3489; }
    .status-at_risk   { background: #FEE2E2; color: #991B1B; }

    [data-theme="dark"] .status-completed { background: #065F46; color: #A7F3D0; }
    [data-theme="dark"] .status-on_track  { background: #3C3489; color: #CECBF6; }
    [data-theme="dark"] .status-at_risk   { background: #991B1B; color: #FECACA; }

    /* ── Action btn ── */
    .ts-action-btn {
        font-size: 12px;
        color: #3C3489;
        text-decoration: none;
        font-weight: 500;
        padding: 4px 10px;
        border-radius: 5px;
        transition: background .13s;
    }

    .ts-action-btn:hover {
        background: #EEEDFE;
    }

    [data-theme="dark"] .ts-action-btn:hover {
        background: #3C3489;
        color: #CECBF6;
    }

    .ts-empty-row {
        text-align: center;
        color: var(--text-muted);
        padding: 28px;
        font-size: 13px;
    }

    /* ═══════════════════════════════════════════════════════════
       PAGINATION
    ═══════════════════════════════════════════════════════════ */
    .ts-pagination {
        display: flex;
        align-items: center;
        gap: 4px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    .ts-page-info {
        font-size: 11.5px;
        color: var(--text-muted);
        margin-right: 8px;
    }

    .ts-page-btn {
        padding: 5px 11px;
        border-radius: 6px;
        border: 1px solid var(--border);
        font-size: 12px;
        font-family: inherit;
        color: var(--text-muted);
        background: transparent;
        cursor: pointer;
        transition: background .13s, color .13s;
    }

    .ts-page-btn:hover:not(:disabled) {
        background: var(--bg-hover);
        color: var(--text);
    }

    .ts-page-btn.active {
        background: #3C3489;
        color: #fff;
        border-color: #3C3489;
        font-weight: 500;
    }

    .ts-page-btn:disabled {
        opacity: .35;
        cursor: default;
    }

    /* ═══════════════════════════════════════════════════════════
       EMPTY STATE
    ═══════════════════════════════════════════════════════════ */
    .ts-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        padding: 40px;
        color: var(--text-muted);
        font-size: 13px;
        text-align: center;
    }
</style>
