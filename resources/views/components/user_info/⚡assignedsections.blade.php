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
        <div class="cal-form-group">
            <div class="section-header">
                <span class="section-title">Assigned sections</span>
            </div>

            @if(session('success'))
                <p style="color:#16a34a;font-size:12px;margin:0 0 6px;">{{ session('success') }}</p>
            @endif


            <div class="section-picker">
                <div class="section-input-wrap">
                    <input
                        type="text"
                        class="section-input"
                        wire:model.live="f_sectionInput"
                        placeholder="Type a section number (e.g. 12)…"
                        autocomplete="off"
                    >
                    @if(count($this->sectionSuggestions))
                        <div class="section-suggestions">
                            @foreach($this->sectionSuggestions as $sug)
                                <button type="button"
                                        class="section-sug-btn"
                                        wire:click="addSection('{{ $sug }}')">
                                    {{ $sug }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Tags of added sections --}}
                @if(count($f_sections))
                    <div class="section-tags">
                        @foreach($f_sections as $sec)
                            <span class="section-tag">
                                                    {{ $sec }}
                                                    <button type="button"
                                                            class="section-tag-remove"
                                                            wire:click="removeSection('{{ $sec }}')">×</button>
                                                </span>
                        @endforeach
                    </div>
                @else
                    <p class="section-empty">No sections added yet.</p>
                @endif
                <button class="cal-btn-submit"
                        wire:click="save">save
                </button>
            </div>
        </div>
    @endif
    <style>
        .cw {
            display: flex;
            height: calc(100vh - 60px);
            font-family: 'DM Sans', sans-serif;
            background: var(--bg, #f1f5f9);
            color: var(--text, #0f172a);
            overflow: hidden;
        }

        /* sidebar */
        .cw-side {
            width: 100%;
            height: 100%;
            flex-shrink: 0;
            background: var(--surface, #fff);
            border-right: 1px solid var(--border, #e2e8f0);
            display: flex;
            flex-direction: column;
            gap: 18px;
            padding: 16px 12px;
            overflow-y: auto;
        }

        .cw-add {
            display: flex;
            align-items: center;
            gap: 7px;
            width: 100%;
            padding: 9px 14px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
        }

        .cw-add:hover {
            background: #1d4ed8;
        }

        /* mini cal */
        .mini-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .mini-arr {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: #64748b;
            padding: 2px 5px;
            border-radius: 4px;
        }

        .mini-arr:hover {
            background: #f1f5f9;
        }

        .mini-lbl {
            font-size: 12px;
            font-weight: 700;
        }

        .mini-grid {
            display: grid;
            grid-template-columns:repeat(7, 1fr);
            gap: 1px;
        }

        .mini-dow {
            font-size: 9px;
            text-align: center;
            color: #94a3b8;
            font-weight: 700;
            padding: 2px 0;
        }

        .mini-day {
            position: relative;
            font-size: 10px;
            text-align: center;
            padding: 3px 0;
            border: none;
            background: none;
            cursor: pointer;
            border-radius: 50%;
            color: var(--text, #0f172a);
            line-height: 1.7;
            transition: background .1s;
        }

        .mini-day:hover {
            background: #f1f5f9;
        }

        .mini-dim {
            color: #cbd5e1;
        }

        .mini-td {
            background: #2563eb !important;
            color: #fff !important;
            font-weight: 700;
        }

        .mini-dot {
            position: absolute;
            bottom: 1px;
            left: 50%;
            transform: translateX(-50%);
            width: 3px;
            height: 3px;
            border-radius: 50%;
            background: #2563eb;
        }

        .mini-dot-w {
            background: #fff;
        }

        /* sb sections */
        .sb-sec {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .sb-lbl {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #94a3b8;
            margin: 0 0 5px;
        }

        .sb-empty {
            font-size: 11px;
            color: #cbd5e1;
            padding: 2px 6px;
            margin: 0;
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

        /* section picker */
        .section-picker {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 8px;
        }

        .section-input-wrap {
            position: relative;
        }

        .section-input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 7px;
            font-size: 13px;
            color: var(--text, #0f172a);
            background: var(--bg, #f8fafc);
            outline: none;
            transition: border .15s;
            box-sizing: border-box;
            font-family: inherit;
        }

        .section-input:focus {
            border-color: #2563eb;
            background: #fff;
        }

        .section-suggestions {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            padding: 8px;
            z-index: 50;
        }

        .section-sug-btn {
            padding: 4px 12px;
            border: 1.5px solid #2563eb;
            border-radius: 20px;
            background: none;
            color: #2563eb;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s;
        }

        .section-sug-btn:hover {
            background: #2563eb;
            color: #fff;
        }

        .section-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .section-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: #1d4ed8;
        }

        .section-tag-remove {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            color: #60a5fa;
            line-height: 1;
            padding: 0 1px;
            transition: color .1s;
        }

        .section-tag-remove:hover {
            color: #ef4444;
        }

        .section-empty {
            font-size: 11px;
            color: #94a3b8;
            margin: 0;
        }

        .cal-btn-submit {
            padding: 8px 20px;
            border-radius: 7px;
            border: none;
            background: var(--accent);
            color: #fff;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
            transition: background .15s;
        }


    </style>
</div>
