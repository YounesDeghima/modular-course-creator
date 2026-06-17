<?php

use App\Models\section;
use App\Models\user;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {

    public $isTeacher;
    public $user;

    // ── Teacher being edited ──
    public int $teacherId;

    // ── Modal ──
    public bool $showModal = false;
    public ?int $editingId = null;

    public array $f_sectionids = [];
    public array $f_sections = [];
    public array $f_assignedSections = [];
    public string $f_sectionInput = '';

    // Load the teacher's currently assigned sections on mount
    public function mount($user,int $teacherId,$isTeacher): void
    {

        $this->user = $user;
        $this->isTeacher = $isTeacher;
        $this->teacherId = $teacherId;

        // Load already-assigned section_numbers for this teacher
        $this->f_sectionids = user::findOrFail($this->teacherId)
            ->assignedsections()
            ->pluck('section_id')
            ->toArray();

        $this->f_sections = section::wherein('id',$this->f_sectionids)
            ->pluck('section_number')
            ->map(fn($v) => (string) $v)
            ->toArray();



    }

    // ── Filters ──

    #[Computed]
    public function sectionSuggestions(): array
    {
        $input = trim($this->f_sectionInput);
        if ($input === '') return [];

        return Section::select('section_number')
            ->where('section_number', 'like', $input . '%')
            ->whereNotIn('section_number', $this->f_sections)
            ->limit(8)
            ->pluck('section_number')
            ->toArray();
    }

    public function addSection(string $section): void
    {
        $section = trim($section);
        if ($section !== '' && !in_array($section, $this->f_sections)) {
            $this->f_sections[] = $section;
        }
        $this->f_sectionInput = '';
    }

    public function removeSection(string $section): void
    {
        $this->f_sections = array_values(
            array_filter($this->f_sections, fn($s) => $s !== $section)
        );
    }


    public function save(): void
    {
        $sectionIds = Section::whereIn('section_number', $this->f_sections)
            ->pluck('id')
            ->toArray();

        // Find the teacher model instance safely
        $teacher = User::findOrFail($this->teacherId);

        // 🌟 USE SYNC: This automatically adds new, removes old, and clears state caches
        $teacher->assignedsections()->sync($sectionIds);

        // Update your local component arrays so they reflect changes cleanly
        $this->f_sectionids = $sectionIds;

        session()->flash('success', 'Sections saved successfully.');
    }

};
?>

<div class="assigned-sections">
    @if($this->isTeacher)
        <div class="as-card">

            {{-- Header --}}
            <div class="as-header">
                <div class="as-header-left">
                    <div class="as-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <span class="as-title">Assigned Sections</span>
                </div>
                <span class="as-count-badge">{{ count($f_sections) }} section{{ count($f_sections) !== 1 ? 's' : '' }}</span>
            </div>

            {{-- Success toast --}}
            @if(session('success'))
                <div class="as-success">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    {{ session('success') }}
                </div>
            @endif

            <div class="as-body">

                {{-- Search input --}}
                <div class="as-field-label">Add a section</div>
                <div class="as-input-wrap">
                    <svg class="as-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input
                        type="text"
                        class="as-input"
                        wire:model.live="f_sectionInput"
                        placeholder="Search by section number…"
                        autocomplete="off"
                    >
                    @if($f_sectionInput !== '')
                        <button type="button" class="as-clear-btn" wire:click="$set('f_sectionInput', '')">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    @endif

                    {{-- Suggestions dropdown --}}
                    @if(count($this->sectionSuggestions))
                        <div class="as-suggestions">
                            <div class="as-sug-label">Suggestions</div>
                            @foreach($this->sectionSuggestions as $sug)
                                <button type="button"
                                        class="as-sug-item"
                                        wire:click="addSection('{{ $sug }}')">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    Section {{ $sug }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Tags area --}}
                <div class="as-tags-area">
                    @if(count($f_sections))
                        <div class="as-tags">
                            @foreach($f_sections as $sec)
                                <span class="as-tag" wire:key="tag-{{ $sec }}">
                                    <span class="as-tag-dot"></span>
                                    Section {{ $sec }}
                                    <button type="button"
                                            class="as-tag-remove"
                                            wire:click="removeSection('{{ $sec }}')"
                                            title="Remove section {{ $sec }}">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </button>
                                </span>
                            @endforeach
                        </div>
                    @else
                        <div class="as-empty">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            <span>No sections assigned yet</span>
                            <span class="as-empty-sub">Search above to add sections to this teacher.</span>
                        </div>
                    @endif
                </div>

            </div>

            {{-- Footer --}}
            <div class="as-footer">
                <button class="as-save-btn" wire:click="save" wire:loading.attr="disabled" wire:loading.class="as-saving">
                    <span wire:loading.remove>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Save changes
                    </span>
                    <span wire:loading>Saving…</span>
                </button>
            </div>

        </div>
    @endif
    <style>
        /* ── Assigned Sections Card ── */
        .as-card {
            background: var(--bg, #fff);
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 16px;
            overflow: hidden;
            font-family: 'DM Sans', sans-serif;
        }

        .as-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 24px 16px;
            border-bottom: 1px solid var(--border, #e2e8f0);
        }

        .as-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .as-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
            flex-shrink: 0;
        }

        .as-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text, #0f172a);
            letter-spacing: -0.01em;
        }

        .as-count-badge {
            font-size: 11px;
            font-weight: 600;
            color: #2563eb;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 20px;
            padding: 2px 10px;
        }

        .as-success {
            display: flex;
            align-items: center;
            gap: 7px;
            margin: 14px 24px 0;
            padding: 9px 12px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            color: #15803d;
        }

        .as-body {
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .as-field-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-faint, #94a3b8);
        }

        .as-input-wrap {
            position: relative;
        }

        .as-search-icon {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .as-input {
            width: 100%;
            padding: 9px 36px 9px 32px;
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 9px;
            font-size: 13px;
            color: var(--text, #0f172a);
            background: var(--bg-subtle, #f8fafc);
            outline: none;
            transition: border-color .15s, background .15s, box-shadow .15s;
            box-sizing: border-box;
            font-family: inherit;
        }

        .as-input:focus {
            border-color: #2563eb;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
        }

        .as-clear-btn {
            position: absolute;
            right: 9px;
            top: 50%;
            transform: translateY(-50%);
            background: #e2e8f0;
            border: none;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #64748b;
            transition: background .1s;
            padding: 0;
        }

        .as-clear-btn:hover {
            background: #cbd5e1;
        }

        .as-suggestions {
            position: absolute;
            top: calc(100% + 5px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .12);
            z-index: 50;
            overflow: hidden;
        }

        .as-sug-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #94a3b8;
            padding: 8px 12px 4px;
        }

        .as-sug-item {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 8px 12px;
            border: none;
            background: none;
            font-size: 13px;
            font-weight: 500;
            color: var(--text, #0f172a);
            cursor: pointer;
            text-align: left;
            transition: background .1s;
            font-family: inherit;
        }

        .as-sug-item svg {
            color: #2563eb;
            flex-shrink: 0;
        }

        .as-sug-item:hover {
            background: #eff6ff;
            color: #2563eb;
        }

        .as-tags-area {
            min-height: 52px;
        }

        .as-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .as-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 8px 5px 10px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #1d4ed8;
            transition: border-color .15s;
        }

        .as-tag:hover {
            border-color: #93c5fd;
        }

        .as-tag-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #60a5fa;
            flex-shrink: 0;
        }

        .as-tag-remove {
            background: none;
            border: none;
            cursor: pointer;
            color: #93c5fd;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2px;
            border-radius: 4px;
            transition: color .1s, background .1s;
            line-height: 1;
        }

        .as-tag-remove:hover {
            color: #ef4444;
            background: #fee2e2;
        }

        .as-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            padding: 24px 0 8px;
            color: #cbd5e1;
            text-align: center;
            font-size: 13px;
            font-weight: 500;
        }

        .as-empty-sub {
            font-size: 12px;
            font-weight: 400;
            color: #cbd5e1;
        }

        .as-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 14px 24px;
            border-top: 1px solid var(--border, #e2e8f0);
            background: var(--bg-subtle, #f8fafc);
        }

        .as-save-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 18px;
            background: var(--accent, #2563eb);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: background .15s, opacity .15s;
        }

        .as-save-btn:hover {
            background: #1d4ed8;
        }

        .as-save-btn.as-saving {
            opacity: .7;
            cursor: not-allowed;
        }

        .flt-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 8px;
            border: none;
            background: none;
            cursor: pointer;
            border-radius: 6px;
            font-size: 12px;
            color: var(--text, #0f172a);
            text-align: left;
            width: 100%;
            transition: background .1s;
        }

        .flt-row:hover {
            background: #f1f5f9;
        }

        .flt-off {
            opacity: .4;
        }

        .flt-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .flt-hidden {
            margin-left: auto;
            font-size: 9px;
            color: #94a3b8;
        }

        .up-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 8px;
            border: none;
            background: none;
            cursor: pointer;
            border-radius: 6px;
            width: 100%;
            text-align: left;
            transition: background .1s;
        }

        .up-row:hover {
            background: #f1f5f9;
        }

        .up-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .up-title {
            font-size: 11px;
            font-weight: 500;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .up-date {
            font-size: 10px;
            color: #94a3b8;
            flex-shrink: 0;
        }

        /* main */
        .cw-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: var(--surface, #fff);
        }

        .cw-hdr {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 18px;
            border-bottom: 1px solid var(--border, #e2e8f0);
            flex-shrink: 0;
        }

        .cw-nav {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .cw-arr {
            background: none;
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 6px;
            padding: 4px 10px;
            cursor: pointer;
            font-size: 15px;
            color: #64748b;
        }

        .cw-arr:hover {
            background: #f1f5f9;
        }

        .cw-today-btn {
            background: none;
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 6px;
            padding: 5px 12px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
        }

        .cw-today-btn:hover {
            background: #f1f5f9;
        }

        .cw-period {
            font-size: 16px;
            font-weight: 700;
            margin: 0 10px;
        }

        .cw-views {
            display: flex;
            gap: 2px;
            background: #f1f5f9;
            border-radius: 8px;
            padding: 3px;
        }

        .cw-vbtn {
            padding: 5px 14px;
            border: none;
            background: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            color: #64748b;
            transition: all .15s;
        }

        .cw-vactive {
            background: #fff;
            color: #2563eb;
            font-weight: 700;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .1);
        }

        /* month */
        .month-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .dow-row {
            display: grid;
            grid-template-columns:repeat(7, 1fr);
            border-bottom: 1px solid var(--border, #e2e8f0);
            flex-shrink: 0;
        }

        .dow-hdr {
            padding: 7px 4px;
            text-align: center;
            font-size: 10px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .days-grid {
            display: grid;
            grid-template-columns:repeat(7, 1fr);
            grid-template-rows:repeat(6, 1fr);
            flex: 1;
            overflow: hidden;
        }

        .day-cell {
            border-right: 1px solid var(--border, #e2e8f0);
            border-bottom: 1px solid var(--border, #e2e8f0);
            padding: 4px;
            cursor: pointer;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 2px;
            transition: background .1s;
        }

        .day-cell:hover {
            background: #f8fafc;
        }

        .dc-dim {
            background: #f8fafc;
        }

        .dc-today {
            background: #eff6ff;
        }

        .day-n {
            font-size: 12px;
            font-weight: 500;
            color: #64748b;
            padding: 1px 3px;
            align-self: flex-start;
        }

        .dn-today {
            background: #2563eb;
            color: #fff;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 11px;
            padding: 0;
        }

        .dc-dim .day-n {
            color: #cbd5e1;
        }

        .ev-chip {
            display: block;
            width: 100%;
            text-align: left;
            padding: 1px 5px;
            border: none;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 500;
            color: #fff;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: opacity .1s;
        }

        .ev-chip:hover {
            opacity: .82;
        }

        .ev-more {
            font-size: 9px;
            color: #94a3b8;
            padding: 0 4px;
        }

        /* week */
        .week-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .wk-hdr-row {
            display: grid;
            grid-template-columns:repeat(7, 1fr);
            border-bottom: 1px solid var(--border, #e2e8f0);
            flex-shrink: 0;
        }

        .wk-hdr {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8px 4px;
            border-left: 1px solid var(--border, #e2e8f0);
        }

        .wk-hdr:first-child {
            border-left: none;
        }

        .wk-hdr-today .wk-dow, .wk-hdr-today .wk-num {
            color: #2563eb;
        }

        .wk-dow {
            font-size: 10px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .wk-num {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
        }

        .wk-num-today {
            background: #2563eb;
            color: #fff;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .wk-body {
            display: grid;
            grid-template-columns:repeat(7, 1fr);
            flex: 1;
            overflow-y: auto;
        }

        .wk-col {
            border-left: 1px solid var(--border, #e2e8f0);
            padding: 6px 4px;
            display: flex;
            flex-direction: column;
            gap: 3px;
            cursor: pointer;
            transition: background .1s;
            min-height: 120px;
        }

        .wk-col:first-child {
            border-left: none;
        }

        .wk-col:hover {
            background: #f8fafc;
        }

        .wk-chip {
            display: block;
            width: 100%;
            text-align: left;
            padding: 3px 7px;
            border: none;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 500;
            color: #fff;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: opacity .1s;
        }

        .wk-chip:hover {
            opacity: .82;
        }

        /* agenda */
        .ag-wrap {
            flex: 1;
            overflow-y: auto;
            padding: 20px 24px;
        }

        .ag-group {
            margin-bottom: 28px;
        }

        .ag-date {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #64748b;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid var(--border, #e2e8f0);
        }

        .ag-date-today {
            color: #2563eb;
        }

        .ag-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            background: var(--surface, #fff);
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 8px;
            margin-bottom: 6px;
            cursor: pointer;
            width: 100%;
            text-align: left;
            transition: box-shadow .15s, border-color .15s;
        }

        .ag-row:hover {
            box-shadow: 0 2px 10px rgba(0, 0, 0, .07);
            border-color: #cbd5e1;
        }

        .ag-bar {
            width: 4px;
            height: 36px;
            border-radius: 2px;
            flex-shrink: 0;
        }

        .ag-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }

        .ag-title {
            font-size: 13px;
            font-weight: 600;
        }

        .ag-desc {
            font-size: 11px;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ag-badge {
            padding: 2px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            text-transform: capitalize;
            flex-shrink: 0;
        }

        .ag-glob {
            font-size: 12px;
            flex-shrink: 0;
        }

        .ag-empty {
            text-align: center;
            padding: 60px;
            color: #94a3b8;
            font-size: 14px;
        }

        /* modal */
        .modal-bg {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .45);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999;
        }

        .modal-box {
            background: var(--surface, #fff);
            border-radius: 14px;
            width: 480px;
            max-width: calc(100vw - 24px);
            max-height: calc(100vh - 48px);
            display: flex;
            flex-direction: column;
            box-shadow: 0 24px 64px rgba(0, 0, 0, .22);
            overflow: hidden;
        }

        .modal-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 20px 0;
        }

        .modal-h {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
        }

        .modal-x {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: #94a3b8;
            padding: 4px 6px;
            border-radius: 4px;
        }

        .modal-x:hover {
            background: #f1f5f9;
        }

        .modal-body {
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            overflow-y: auto;
        }

        .modal-foot {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 14px 20px;
            border-top: 1px solid var(--border, #e2e8f0);
        }

        .fg {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .fl {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #64748b;
        }

        .fi {
            padding: 8px 10px;
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 7px;
            font-size: 13px;
            color: var(--text, #0f172a);
            background: var(--bg, #f8fafc);
            outline: none;
            transition: border .15s;
            width: 100%;
            box-sizing: border-box;
            font-family: inherit;
        }

        .fi:focus {
            border-color: #2563eb;
            background: #fff;
        }

        .fe {
            font-size: 11px;
            color: #ef4444;
        }

        .fg-row {
            display: grid;
            grid-template-columns:1fr 1fr;
            gap: 10px;
        }

        .type-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .tp {
            padding: 5px 12px;
            border-radius: 20px;
            border: 1.5px solid;
            background: none;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            transition: all .15s;
        }

        .vis-row {
            display: flex;
            gap: 8px;
        }

        .vb {
            flex: 1;
            padding: 7px;
            border: 1.5px solid var(--border, #e2e8f0);
            border-radius: 8px;
            background: none;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            color: #64748b;
            transition: all .15s;
        }

        .vb-on {
            border-color: #2563eb;
            background: #eff6ff;
            color: #2563eb;
            font-weight: 700;
        }

        .mbtn-del {
            margin-right: auto;
            padding: 7px 14px;
            background: none;
            border: 1px solid #fecaca;
            color: #ef4444;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .mbtn-del:hover {
            background: #fef2f2;
        }

        .mbtn-cancel {
            padding: 7px 16px;
            background: none;
            border: 1px solid var(--border, #e2e8f0);
            color: #64748b;
            border-radius: 7px;
            font-size: 13px;
            cursor: pointer;
        }

        .mbtn-cancel:hover {
            background: #f1f5f9;
        }

        .mbtn-save {
            margin-left: auto;
            padding: 7px 22px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .mbtn-save:hover {
            background: #1d4ed8;
        }

    </style>
</div>
