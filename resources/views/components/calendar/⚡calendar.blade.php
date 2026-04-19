<?php

use App\Models\Event;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Carbon;

new class extends Component {

    // ── View state ──
    public string $view      = 'month';
    public int    $year;
    public int    $month;
    public string $weekStart = '';

    // ── Modal ──
    public bool  $showModal = false;
    public ?int  $editingId = null;

    // ── Form ──
    public string $f_title       = '';
    public string $f_description = '';
    public string $f_start       = '';
    public string $f_end         = '';
    public string $f_type        = 'exam';
    public string $f_visibility  = 'global';

    // ── Filters ──
    public array $activeFilters = ['exam','vacation','project','assignment','personal'];
    protected $listeners = ['eventCreated','events'];

    public function mount(): void
    {
        $this->year      = now()->year;
        $this->month     = now()->month;
        $this->weekStart = now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');


    }

    // ─────────────────────────────────────────
    // Computed
    // ─────────────────────────────────────────

    #[Computed]
    public function isAdmin(): bool
    {
        return Auth::user()->role == 'admin' ?? false;
    }

    #[Computed]
    public function events(): array
    {
        return Event::where(function ($q) {
            $q->where('visibility', 'global')
                ->orWhere('user_id', auth()->id());
        })
            ->when(!empty($this->activeFilters), fn($q) => $q->whereIn('type', $this->activeFilters))
            ->get()
            ->map(fn($e) => [
                'id'          => $e->id,
                'title'       => $e->title,
                'description' => $e->description ?? '',
                'start_date'  => Carbon::parse($e->start_date)->format('Y-m-d'),
                'end_date'    => Carbon::parse($e->end_date ?? $e->start_date)->format('Y-m-d'),
                'type'        => $e->type,
                'visibility'  => $e->visibility,
            ])
            ->toArray();
    }

    #[Computed]
    public function monthLabel(): string
    {
        return Carbon::create($this->year, $this->month)->format('F Y');
    }

    #[Computed]
    public function calendarDays(): array
    {
        $first  = Carbon::create($this->year, $this->month, 1);
        $last   = $first->copy()->endOfMonth();
        $offset = $first->dayOfWeek;
        $days   = [];

        for ($i = $offset - 1; $i >= 0; $i--) {
            $days[] = ['date' => $first->copy()->subDays($i + 1)->format('Y-m-d'), 'cur' => false];
        }
        for ($d = 1; $d <= $last->day; $d++) {
            $days[] = ['date' => Carbon::create($this->year, $this->month, $d)->format('Y-m-d'), 'cur' => true];
        }
        $rem = 42 - count($days);
        for ($d = 1; $d <= $rem; $d++) {
            $days[] = ['date' => $last->copy()->addDays($d)->format('Y-m-d'), 'cur' => false];
        }
        return $days;
    }

    #[Computed]
    public function weekDays(): array
    {
        $sun  = Carbon::parse($this->weekStart);
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $sun->copy()->addDays($i)->format('Y-m-d');
        }
        return $days;
    }

    #[Computed]
    public function weekLabel(): string
    {
        $sun = Carbon::parse($this->weekStart);
        $sat = $sun->copy()->addDays(6);
        return $sun->month === $sat->month
            ? $sun->format('F d') . '–' . $sat->format('d, Y')
            : $sun->format('M d') . ' – ' . $sat->format('M d, Y');
    }

    #[Computed]
    public function agendaGroups(): array
    {
        $today    = now()->format('Y-m-d');
        $filtered = array_filter($this->events, fn($e) => $e['end_date'] >= $today);
        usort($filtered, fn($a, $b) => strcmp($a['start_date'], $b['start_date']));
        $groups = [];
        foreach ($filtered as $ev) {
            $groups[$ev['start_date']][] = $ev;
        }
        return $groups;
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    public function eventsOnDate(string $date): array
    {
        return array_values(array_filter(
            $this->events,
            fn($e) => $date >= $e['start_date'] && $date <= $e['end_date']
        ));
    }

    // ─────────────────────────────────────────
    // Navigation
    // ─────────────────────────────────────────

    public function prevPeriod(): void
    {
        if ($this->view === 'week') {
            $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->format('Y-m-d');
        } else {
            $d = Carbon::create($this->year, $this->month)->subMonth();
            $this->year  = $d->year;
            $this->month = $d->month;
        }
    }

    public function nextPeriod(): void
    {
        if ($this->view === 'week') {
            $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->format('Y-m-d');
        } else {
            $d = Carbon::create($this->year, $this->month)->addMonth();
            $this->year  = $d->year;
            $this->month = $d->month;
        }
    }

    public function goToday(): void
    {
        $this->year      = now()->year;
        $this->month     = now()->month;
        $this->weekStart = now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');
    }

    // ─────────────────────────────────────────
    // Filters
    // ─────────────────────────────────────────

    public function toggleFilter(string $type): void
    {
        if (in_array($type, $this->activeFilters)) {
            $this->activeFilters = array_values(
                array_filter($this->activeFilters, fn($t) => $t !== $type)
            );
        } else {
            $this->activeFilters[] = $type;
        }
    }

    // ─────────────────────────────────────────
    // Modal
    // ─────────────────────────────────────────

    public function openNew(string $date = ''): void
    {
        $this->resetForm();
        $this->f_start      = $date ?: now()->format('Y-m-d');
        $this->f_end        = $date ?: now()->format('Y-m-d');
        $this->f_visibility = $this->isAdmin ? 'global' : 'personal';
        $this->showModal    = true;
    }

    public function openEdit(int $id): void
    {
        $e = Event::findOrFail($id);
        $this->editingId     = $id;
        $this->f_title       = $e->title;
        $this->f_description = $e->description ?? '';
        $this->f_start       = Carbon::parse($e->start_date)->format('Y-m-d');
        $this->f_end         = Carbon::parse($e->end_date ?? $e->start_date)->format('Y-m-d');
        $this->f_type        = $e->type;
        $this->f_visibility  = $e->visibility;
        $this->showModal     = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    // ─────────────────────────────────────────
    // CRUD
    // ─────────────────────────────────────────

    public function save(): void
    {
        // Non-admins can only save personal events
        if (!$this->isAdmin) {
            $this->f_visibility = 'personal';
        }

        $this->validate([
            'f_title'       => 'required|string|max:255',
            'f_description' => 'nullable|string',
            'f_start'       => 'required|date',
            'f_end'         => 'required|date|after_or_equal:f_start',
            'f_type'        => 'required|in:exam,vacation,project,assignment,personal',
            'f_visibility'  => 'required|in:global,personal',
        ]);

        $data = [
            'title'       => $this->f_title,
            'description' => $this->f_description,
            'start_date'  => $this->f_start,
            'end_date'    => $this->f_end,
            'type'        => $this->f_type,
            'visibility'  => $this->f_visibility,
            'user_id'     => auth()->id(),
        ];

        if ($this->editingId) {
            $event = Event::findOrFail($this->editingId);
            // Users can only edit their own
            if (!$this->isAdmin && $event->user_id !== auth()->id()) return;
            $event->update($data);
        } else {
            Event::create($data);
        }

        $this->closeModal();
    }

    public function delete(): void
    {
        if (!$this->editingId) return;
        $event = Event::findOrFail($this->editingId);
        if (!$this->isAdmin && $event->user_id !== auth()->id()) return;
        $event->delete();
        $this->closeModal();
    }

    private function resetForm(): void
    {
        $this->f_title       = '';
        $this->f_description = '';
        $this->f_start       = '';
        $this->f_end         = '';
        $this->f_type        = 'exam';
        $this->f_visibility  = 'global';
        $this->editingId     = null;
    }
};
?>

@php
    $types  = ['exam','vacation','project','assignment','personal'];
    $today  = now()->format('Y-m-d');
    $dow    = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

    // Using your exact color classes from calendar.css
    $typeChip = [
    'exam'       => 'bar-exam',
    'vacation'   => 'bar-vacation',
    'project'    => 'bar-project',
    'assignment' => 'bar-assignment',
    'personal'   => 'bar-personal',
];
    $typeBar = [
        'exam'       => 'bar-exam',
        'vacation'   => 'bar-vacation',
        'project'    => 'bar-project',
        'assignment' => 'bar-assignment',
        'personal'   => 'bar-personal',
    ];
    $typeDot = [
        'exam'       => 'dot-exam',
        'vacation'   => 'dot-vacation',
        'project'    => 'dot-project',
        'assignment' => 'dot-assignment',
        'personal'   => 'dot-personal',
    ];
@endphp

<div class="middle">

    {{-- ══════════════════════════════
         SIDEBAR
    ══════════════════════════════════ --}}
    <div class="side-bar" id="calSideBar">

        <button class="sidebar-toggle" id="sidebarToggle">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </button>

        <div class="sidebar-content">
            <div class="cal-sidebar">

                <button class="cal-add-btn" wire:click="openNew('{{ $today }}')">+ Add event</button>

                {{-- Mini calendar --}}
                <div>
                    <div class="cal-sb-label">{{ $this->monthLabel }}</div>
                    <div style="display:flex;align-items:center;gap:4px;margin-bottom:8px;">
                        <button wire:click="prevPeriod" class="cal-nav-btn" style="width:24px;height:24px;font-size:13px;">‹</button>
                        <button wire:click="nextPeriod" class="cal-nav-btn" style="width:24px;height:24px;font-size:13px;">›</button>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:1px;margin-bottom:4px;">
                        @foreach(['S','M','T','W','T','F','S'] as $d)
                            <div style="font-size:9px;text-align:center;color:var(--text-faint);font-weight:500;padding:2px 0;">{{ $d }}</div>
                        @endforeach
                        @foreach($this->calendarDays as $day)
                            @php $cnt = count($this->eventsOnDate($day['date'])); @endphp
                            <button
                                wire:click="openNew('{{ $day['date'] }}')"
                                style="position:relative;font-size:10px;text-align:center;padding:3px 0;border:none;background:none;cursor:pointer;border-radius:50%;line-height:1.7;
                                    color:{{ !$day['cur'] ? 'var(--border)' : ($day['date']===$today ? '#fff' : 'var(--text-muted)') }};
                                    background:{{ $day['date']===$today ? 'var(--accent)' : 'none' }};
                                    font-weight:{{ $day['date']===$today ? '500' : '400' }};"
                            >{{ (int)substr($day['date'],8,2) }}@if($cnt && $day['date']!==$today)<span style="position:absolute;bottom:1px;left:50%;transform:translateX(-50%);width:3px;height:3px;border-radius:50%;background:var(--accent);display:block;"></span>@endif</button>
                        @endforeach
                    </div>
                </div>

                {{-- Type filters --}}
                <div>
                    <div class="cal-sb-label">Event types</div>
                    <div style="display:flex;flex-direction:column;gap:2px;margin-top:4px;">
                        @foreach($types as $t)
                            <div
                                class="type-filter{{ in_array($t,$activeFilters) ? '' : ' off' }}"
                                wire:click="toggleFilter('{{ $t }}')"
                            >
                                <span class="type-dot {{ $typeDot[$t] }}"></span>
                                {{ ucfirst($t) }}
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Upcoming --}}
                <div>
                    <div class="cal-sb-label">Upcoming</div>
                    <div style="display:flex;flex-direction:column;gap:6px;margin-top:4px;">
                        @php
                            $flat = [];
                            foreach($this->agendaGroups as $evs) foreach($evs as $ev) $flat[] = $ev;
                            $upcoming = array_slice($flat, 0, 5);
                        @endphp
                        @forelse($upcoming as $ev)
                            <div class="upcoming-item" wire:click="openEdit({{ $ev['id'] }})" style="cursor:pointer;">
                                <div class="upcoming-date">{{ \Carbon\Carbon::parse($ev['start_date'])->format('d M Y') }}</div>
                                <div class="upcoming-title">{{ $ev['title'] }}</div>
                            </div>
                        @empty
                            <div style="font-size:12px;color:var(--text-faint);padding:4px 0;">No upcoming events.</div>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ══════════════════════════════
         MAIN
    ══════════════════════════════════ --}}
    <main style="display:flex;flex-direction:column;overflow:hidden;padding:0;">

        <div class="cal-page">

            {{-- Header --}}
            <div class="cal-header">
                <div class="cal-nav">
                    <button wire:click="prevPeriod" class="cal-nav-btn">‹</button>
                    <span class="cal-month-title">
                        {{ $view === 'week' ? $this->weekLabel : $this->monthLabel }}
                    </span>
                    <button wire:click="nextPeriod" class="cal-nav-btn">›</button>
                    <button wire:click="goToday" class="cal-nav-btn" style="width:auto;padding:0 10px;font-size:12px;">Today</button>
                </div>
                <div class="view-toggle">
                    @foreach(['month'=>'Month','week'=>'Week','agenda'=>'Agenda'] as $v => $lbl)
                        <button
                            class="view-btn{{ $view===$v ? ' active' : '' }}"
                            wire:click="$set('view','{{ $v }}')"
                        >{{ $lbl }}</button>
                    @endforeach
                </div>
            </div>

            {{-- ── MONTH VIEW ── --}}
            @if($view === 'month')
                <div class="month-view" style="display:flex;">
                    <div class="dow-headers">
                        @foreach($dow as $d)<div class="dow-hdr">{{ $d }}</div>@endforeach
                    </div>
                    <div class="days-grid">
                        @foreach($this->calendarDays as $day)
                            @php
                                $evs     = $this->eventsOnDate($day['date']);
                                $isToday = $day['date'] === $today;
                            @endphp
                            <div
                                class="day-cell"
                                wire:click="openNew('{{ $day['date'] }}')"
                            >
                                <div class="day-num{{ $isToday ? ' today' : '' }}{{ !$day['cur'] ? ' other-month' : '' }}">
                                    {{ (int)substr($day['date'],8,2) }}
                                </div>
                                @foreach(array_slice($evs,0,3) as $ev)
                                    <div
                                        class="ev-chip {{ $typeChip[$ev['type']] }}"
                                        wire:click.stop="openEdit({{ $ev['id'] }})"
                                    >{{ $ev['title'] }}</div>
                                @endforeach
                                @if(count($evs) > 3)
                                    <div style="font-size:10px;color:var(--text-faint);padding:1px 4px;">+{{ count($evs)-3 }} more</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ── WEEK VIEW ── --}}
            @if($view === 'week')
                <div class="week-view" style="display:block;">
                    {{-- Day headers --}}
                    <div class="week-grid" style="border-bottom:1px solid var(--border);flex-shrink:0;">
                        <div style="border-right:1px solid var(--border);"></div>
                        @foreach($this->weekDays as $wd)
                            @php $wIsToday = $wd === $today; @endphp
                            <div class="week-day-hdr{{ $wIsToday ? ' today-col' : '' }}">
                                <div style="font-size:10px;text-transform:uppercase;letter-spacing:.04em;">{{ $dow[\Carbon\Carbon::parse($wd)->dayOfWeek] }}</div>
                                <div style="font-size:18px;font-weight:500;line-height:1.3;">{{ (int)substr($wd,8,2) }}</div>
                            </div>
                        @endforeach
                    </div>
                    {{-- All-day row --}}
                    <div class="week-grid" style="border-bottom:1px solid var(--border);">
                        <div style="font-size:10px;color:var(--text-faint);padding:4px 6px;text-align:right;border-right:1px solid var(--border);">all day</div>
                        @foreach($this->weekDays as $wd)
                            <div style="border-left:1px solid var(--border-mid);padding:3px;min-height:36px;" wire:click="openNew('{{ $wd }}')">
                                @foreach($this->eventsOnDate($wd) as $ev)
                                    <div
                                        class="ev-chip {{ $typeChip[$ev['type']] }}"
                                        style="margin-bottom:2px;"
                                        wire:click.stop="openEdit({{ $ev['id'] }})"
                                    >{{ $ev['title'] }}</div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ── AGENDA VIEW ── --}}
            @if($view === 'agenda')
                <div class="agenda-view" style="display:block;">
                    @forelse($this->agendaGroups as $date => $evs)
                        <div class="agenda-date-group">
                            <div class="agenda-date-label" style="{{ $date===$today ? 'color:var(--accent)' : '' }}">
                                {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}
                            </div>
                            @foreach($evs as $ev)
                                <div class="agenda-event" wire:click="openEdit({{ $ev['id'] }})">
                                    <div class="agenda-type-bar {{ $typeBar[$ev['type']] }}"></div>
                                    <div style="flex:1;min-width:0;">
                                        <div class="agenda-event-title">{{ $ev['title'] }}</div>
                                        @if($ev['description'])
                                            <div class="agenda-event-desc">{{ $ev['description'] }}</div>
                                        @endif
                                    </div>
                                    <span class="agenda-event-badge {{ $typeChip[$ev['type']] }}">{{ $ev['type'] }}</span>
                                    @if($ev['visibility']==='global')
                                        <span style="font-size:10px;color:var(--text-faint);margin-left:4px;">global</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div style="text-align:center;padding:40px;color:var(--text-faint);font-size:14px;">No upcoming events.</div>
                    @endforelse
                </div>
            @endif

        </div>

        {{-- ══════════════════════════════
             MODAL
        ══════════════════════════════════ --}}
        @if($showModal)
            <div class="cal-modal-backdrop open">
                <div class="cal-modal">
                    <button class="cal-modal-close" wire:click="closeModal">✕</button>
                    <h3>{{ $editingId ? 'Edit event' : 'New event' }}</h3>

                    <div class="cal-form-group">
                        <label>Title</label>
                        <input type="text" wire:model="f_title" placeholder="Event title">
                        @error('f_title')<span style="font-size:11px;color:#ef4444;">{{ $message }}</span>@enderror
                    </div>

                    <div class="cal-form-group">
                        <label>Description</label>
                        <textarea wire:model="f_description" rows="2" placeholder="Optional description"></textarea>
                    </div>

                    <div class="cal-form-row">
                        <div class="cal-form-group">
                            <label>Start date</label>
                            <input type="date" wire:model="f_start">
                            @error('f_start')<span style="font-size:11px;color:#ef4444;">{{ $message }}</span>@enderror
                        </div>
                        <div class="cal-form-group">
                            <label>End date</label>
                            <input type="date" wire:model="f_end">
                            @error('f_end')<span style="font-size:11px;color:#ef4444;">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="cal-form-group">
                        <label>Type</label>
                        <select wire:model="f_type">
                            @foreach($types as $t)
                                <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($this->isAdmin)
                        <div class="cal-form-group">
                            <label>Visibility</label>
                            <div class="visibility-row">
                                <button type="button"
                                        class="vis-opt{{ $f_visibility==='global' ? ' selected' : '' }}"
                                        wire:click="$set('f_visibility','global')">🌍 Global (all users)</button>
                                <button type="button"
                                        class="vis-opt{{ $f_visibility==='personal' ? ' selected' : '' }}"
                                        wire:click="$set('f_visibility','personal')">🔒 Personal (only me)</button>
                            </div>
                        </div>
                    @endif

                    <div class="cal-modal-actions">
                        @if($editingId)
                            <button class="cal-btn-delete" wire:click="delete" wire:confirm="Delete this event?">Delete</button>
                        @endif
                        <button class="cal-btn-cancel" wire:click="closeModal">Cancel</button>
                        <button class="cal-btn-submit" wire:click="save">{{ $editingId ? 'Update' : 'Save event' }}</button>
                    </div>
                </div>
            </div>
        @endif

    </main>
</div>







<style>
    .cw { display:flex;height:calc(100vh - 60px);font-family:'DM Sans',sans-serif;background:var(--bg,#f1f5f9);color:var(--text,#0f172a);overflow:hidden; }

    /* sidebar */
    .cw-side { width:100%; height: 100%; flex-shrink:0;background:var(--surface,#fff);border-right:1px solid var(--border,#e2e8f0);display:flex;flex-direction:column;gap:18px;padding:16px 12px;overflow-y:auto; }
    .cw-add { display:flex;align-items:center;gap:7px;width:100%;padding:9px 14px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:background .15s; }
    .cw-add:hover { background:#1d4ed8; }

    /* mini cal */
    .mini-nav { display:flex;align-items:center;justify-content:space-between;margin-bottom:6px; }
    .mini-arr { background:none;border:none;cursor:pointer;font-size:16px;color:#64748b;padding:2px 5px;border-radius:4px; }
    .mini-arr:hover { background:#f1f5f9; }
    .mini-lbl { font-size:12px;font-weight:700; }
    .mini-grid { display:grid;grid-template-columns:repeat(7,1fr);gap:1px; }
    .mini-dow { font-size:9px;text-align:center;color:#94a3b8;font-weight:700;padding:2px 0; }
    .mini-day { position:relative;font-size:10px;text-align:center;padding:3px 0;border:none;background:none;cursor:pointer;border-radius:50%;color:var(--text,#0f172a);line-height:1.7;transition:background .1s; }
    .mini-day:hover { background:#f1f5f9; }
    .mini-dim { color:#cbd5e1; }
    .mini-td  { background:#2563eb !important;color:#fff !important;font-weight:700; }
    .mini-dot { position:absolute;bottom:1px;left:50%;transform:translateX(-50%);width:3px;height:3px;border-radius:50%;background:#2563eb; }
    .mini-dot-w { background:#fff; }

    /* sb sections */
    .sb-sec { display:flex;flex-direction:column;gap:1px; }
    .sb-lbl { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin:0 0 5px; }
    .sb-empty { font-size:11px;color:#cbd5e1;padding:2px 6px;margin:0; }
    .flt-row { display:flex;align-items:center;gap:8px;padding:5px 8px;border:none;background:none;cursor:pointer;border-radius:6px;font-size:12px;color:var(--text,#0f172a);text-align:left;width:100%;transition:background .1s; }
    .flt-row:hover { background:#f1f5f9; }
    .flt-off { opacity:.4; }
    .flt-dot { width:8px;height:8px;border-radius:50%;flex-shrink:0; }
    .flt-hidden { margin-left:auto;font-size:9px;color:#94a3b8; }
    .up-row { display:flex;align-items:center;gap:8px;padding:5px 8px;border:none;background:none;cursor:pointer;border-radius:6px;width:100%;text-align:left;transition:background .1s; }
    .up-row:hover { background:#f1f5f9; }
    .up-dot { width:6px;height:6px;border-radius:50%;flex-shrink:0; }
    .up-title { font-size:11px;font-weight:500;flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
    .up-date  { font-size:10px;color:#94a3b8;flex-shrink:0; }

    /* main */
    .cw-main { flex:1;display:flex;flex-direction:column;overflow:hidden;background:var(--surface,#fff); }
    .cw-hdr { display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-bottom:1px solid var(--border,#e2e8f0);flex-shrink:0; }
    .cw-nav { display:flex;align-items:center;gap:6px; }
    .cw-arr { background:none;border:1px solid var(--border,#e2e8f0);border-radius:6px;padding:4px 10px;cursor:pointer;font-size:15px;color:#64748b; }
    .cw-arr:hover { background:#f1f5f9; }
    .cw-today-btn { background:none;border:1px solid var(--border,#e2e8f0);border-radius:6px;padding:5px 12px;cursor:pointer;font-size:12px;font-weight:500; }
    .cw-today-btn:hover { background:#f1f5f9; }
    .cw-period { font-size:16px;font-weight:700;margin:0 10px; }
    .cw-views { display:flex;gap:2px;background:#f1f5f9;border-radius:8px;padding:3px; }
    .cw-vbtn { padding:5px 14px;border:none;background:none;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;color:#64748b;transition:all .15s; }
    .cw-vactive { background:#fff;color:#2563eb;font-weight:700;box-shadow:0 1px 3px rgba(0,0,0,.1); }

    /* month */
    .month-wrap { flex:1;display:flex;flex-direction:column;overflow:hidden; }
    .dow-row { display:grid;grid-template-columns:repeat(7,1fr);border-bottom:1px solid var(--border,#e2e8f0);flex-shrink:0; }
    .dow-hdr { padding:7px 4px;text-align:center;font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em; }
    .days-grid { display:grid;grid-template-columns:repeat(7,1fr);grid-template-rows:repeat(6,1fr);flex:1;overflow:hidden; }
    .day-cell { border-right:1px solid var(--border,#e2e8f0);border-bottom:1px solid var(--border,#e2e8f0);padding:4px;cursor:pointer;overflow:hidden;display:flex;flex-direction:column;gap:2px;transition:background .1s; }
    .day-cell:hover { background:#f8fafc; }
    .dc-dim { background:#f8fafc; }
    .dc-today { background:#eff6ff; }
    .day-n { font-size:12px;font-weight:500;color:#64748b;padding:1px 3px;align-self:flex-start; }
    .dn-today { background:#2563eb;color:#fff;border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;padding:0; }
    .dc-dim .day-n { color:#cbd5e1; }
    .ev-chip { display:block;width:100%;text-align:left;padding:1px 5px;border:none;border-radius:3px;font-size:10px;font-weight:500;color:#fff;cursor:pointer;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;transition:opacity .1s; }
    .ev-chip:hover { opacity:.82; }
    .ev-more { font-size:9px;color:#94a3b8;padding:0 4px; }

    /* week */
    .week-wrap { flex:1;display:flex;flex-direction:column;overflow:hidden; }
    .wk-hdr-row { display:grid;grid-template-columns:repeat(7,1fr);border-bottom:1px solid var(--border,#e2e8f0);flex-shrink:0; }
    .wk-hdr { display:flex;flex-direction:column;align-items:center;padding:8px 4px;border-left:1px solid var(--border,#e2e8f0); }
    .wk-hdr:first-child { border-left:none; }
    .wk-hdr-today .wk-dow,.wk-hdr-today .wk-num { color:#2563eb; }
    .wk-dow { font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em; }
    .wk-num { font-size:18px;font-weight:700;color:#0f172a;line-height:1.3; }
    .wk-num-today { background:#2563eb;color:#fff;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-size:14px; }
    .wk-body { display:grid;grid-template-columns:repeat(7,1fr);flex:1;overflow-y:auto; }
    .wk-col { border-left:1px solid var(--border,#e2e8f0);padding:6px 4px;display:flex;flex-direction:column;gap:3px;cursor:pointer;transition:background .1s;min-height:120px; }
    .wk-col:first-child { border-left:none; }
    .wk-col:hover { background:#f8fafc; }
    .wk-chip { display:block;width:100%;text-align:left;padding:3px 7px;border:none;border-radius:5px;font-size:11px;font-weight:500;color:#fff;cursor:pointer;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;transition:opacity .1s; }
    .wk-chip:hover { opacity:.82; }

    /* agenda */
    .ag-wrap { flex:1;overflow-y:auto;padding:20px 24px; }
    .ag-group { margin-bottom:28px; }
    .ag-date { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid var(--border,#e2e8f0); }
    .ag-date-today { color:#2563eb; }
    .ag-row { display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:8px;margin-bottom:6px;cursor:pointer;width:100%;text-align:left;transition:box-shadow .15s,border-color .15s; }
    .ag-row:hover { box-shadow:0 2px 10px rgba(0,0,0,.07);border-color:#cbd5e1; }
    .ag-bar { width:4px;height:36px;border-radius:2px;flex-shrink:0; }
    .ag-info { flex:1;display:flex;flex-direction:column;gap:2px;min-width:0; }
    .ag-title { font-size:13px;font-weight:600; }
    .ag-desc  { font-size:11px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
    .ag-badge { padding:2px 9px;border-radius:20px;font-size:10px;font-weight:600;text-transform:capitalize;flex-shrink:0; }
    .ag-glob  { font-size:12px;flex-shrink:0; }
    .ag-empty { text-align:center;padding:60px;color:#94a3b8;font-size:14px; }

    /* modal */
    .modal-bg { position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(5px);display:flex;align-items:center;justify-content:center;z-index:999; }
    .modal-box { background:var(--surface,#fff);border-radius:14px;width:480px;max-width:calc(100vw - 24px);max-height:calc(100vh - 48px);display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.22);overflow:hidden; }
    .modal-top { display:flex;align-items:center;justify-content:space-between;padding:18px 20px 0; }
    .modal-h { font-size:16px;font-weight:700;margin:0; }
    .modal-x { background:none;border:none;cursor:pointer;font-size:16px;color:#94a3b8;padding:4px 6px;border-radius:4px; }
    .modal-x:hover { background:#f1f5f9; }
    .modal-body { padding:16px 20px;display:flex;flex-direction:column;gap:12px;overflow-y:auto; }
    .modal-foot { display:flex;align-items:center;gap:8px;padding:14px 20px;border-top:1px solid var(--border,#e2e8f0); }
    .fg { display:flex;flex-direction:column;gap:4px; }
    .fl { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b; }
    .fi { padding:8px 10px;border:1px solid var(--border,#e2e8f0);border-radius:7px;font-size:13px;color:var(--text,#0f172a);background:var(--bg,#f8fafc);outline:none;transition:border .15s;width:100%;box-sizing:border-box;font-family:inherit; }
    .fi:focus { border-color:#2563eb;background:#fff; }
    .fe { font-size:11px;color:#ef4444; }
    .fg-row { display:grid;grid-template-columns:1fr 1fr;gap:10px; }
    .type-pills { display:flex;flex-wrap:wrap;gap:6px; }
    .tp { padding:5px 12px;border-radius:20px;border:1.5px solid;background:none;cursor:pointer;font-size:11px;font-weight:600;transition:all .15s; }
    .vis-row { display:flex;gap:8px; }
    .vb { flex:1;padding:7px;border:1.5px solid var(--border,#e2e8f0);border-radius:8px;background:none;cursor:pointer;font-size:12px;font-weight:500;color:#64748b;transition:all .15s; }
    .vb-on { border-color:#2563eb;background:#eff6ff;color:#2563eb;font-weight:700; }
    .mbtn-del    { margin-right:auto;padding:7px 14px;background:none;border:1px solid #fecaca;color:#ef4444;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer; }
    .mbtn-del:hover { background:#fef2f2; }
    .mbtn-cancel { padding:7px 16px;background:none;border:1px solid var(--border,#e2e8f0);color:#64748b;border-radius:7px;font-size:13px;cursor:pointer; }
    .mbtn-cancel:hover { background:#f1f5f9; }
    .mbtn-save   { margin-left:auto;padding:7px 22px;background:#2563eb;color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer; }
    .mbtn-save:hover { background:#1d4ed8; }
</style>
