{{--
    lesson-toolbar.blade.php
    ────────────────────────
    Sidebar toolbar with 4 tabs: Add · Outline · Reorder · Tools.
    Reorder drag-drop and Renumber call blockcontroller@updateAll.
    Add buttons fire the window event  add-block  → picked up by blockcreate.

    Usage: <livewire:modular_site.block.lesson-toolbar
                :lesson="$lesson"
                :course="$course"
                :chapter="$chapter"
                :blocks="$blocks" />
--}}
<?php
use Livewire\Component;
use App\Models\block;

new class extends Component {

    public $lesson;
    public $course;
    public $chapter;
    public $blocks;  // array of block arrays (same shape as blocks.blade.php)

    public function mount($lesson, $course, $chapter, $blocks): void
    {
        $this->lesson  = $lesson;
        $this->course  = $course;
        $this->chapter = $chapter;
        $this->blocks  = is_array($blocks) ? $blocks : $blocks->toArray();
    }

    /**
     * Drag-drop reorder from the Reorder tab.
     * Swaps block_number values, then persists via direct model update
     * (same swap logic as blockcontroller@updateAll move branch).
     */
    public function moveBlock(int $fromIndex, int $toIndex): void
    {
        if (
            $fromIndex < 0 || $fromIndex >= count($this->blocks) ||
            $toIndex   < 0 || $toIndex   >= count($this->blocks)
        ) return;

        // Swap in local array so outline re-renders immediately
        $item = array_splice($this->blocks, $fromIndex, 1)[0];
        array_splice($this->blocks, $toIndex, 0, [$item]);

        // Persist: sequential block_number = position + 1
        foreach ($this->blocks as $i => $b) {
            block::where('id', $b['id'])->update(['block_number' => $i + 1]);
        }

        $this->dispatch('notify', message: 'Block moved!');
    }

    /**
     * Re-sequences all block_numbers 1…n.
     * Mirrors the intent of blockcontroller@updateAll's bulk-update loop.
     */
    public function renumberAll(): void
    {
        // Reload from DB ordered correctly before renumbering
        $fresh = block::where('lesson_id', $this->lesson->id)
            ->orderBy('block_number')
            ->get();

        foreach ($fresh as $i => $b) {
            $b->update(['block_number' => $i + 1]);
        }

        $this->blocks = $fresh->map(fn($b) => $b->toArray())->toArray();
        $this->dispatch('notify', message: 'Blocks renumbered!');
    }

    /** Refresh outline + reorder list after a new block is created */
    public function getListeners(): array
    {
        return [
            'BlockCreated' => 'refreshBlocks',
        ];
    }

    public function refreshBlocks(): void
    {
        $this->blocks = block::where('lesson_id', $this->lesson->id)
            ->orderBy('block_number')
            ->get()
            ->map(fn($b) => $b->toArray())
            ->toArray();
    }
};
?>

{{--
    CRITICAL FIX: Define Alpine data inline using x-data with an object literal
    instead of referencing external functions that may not be loaded yet.
    This ensures Alpine has immediate access to all reactive data.
--}}
<div
    x-data="{
        activeTab: 'add',
        outlineSearch: '',
        srcIndex: null,

        // Tab switching
        setTab(tab) { this.activeTab = tab },

        // Drag reorder methods
        dragStart(e) {
            this.srcIndex = parseInt(e.currentTarget.dataset.index);
            e.currentTarget.style.opacity = '0.4';
            e.dataTransfer.effectAllowed = 'move';
        },

        dragOver(e) {
            e.currentTarget.style.borderColor = '#534AB7';
            e.currentTarget.style.background  = '#EEEDFE';
        },

        dragLeave(e) {
            e.currentTarget.style.borderColor = 'var(--border)';
            e.currentTarget.style.background  = 'var(--bg)';
        },

        drop(e, wire) {
            const toIndex = parseInt(e.currentTarget.dataset.index);
            e.currentTarget.style.borderColor = 'var(--border)';
            e.currentTarget.style.background  = 'var(--bg)';
            document.querySelectorAll('.reorder-row').forEach(r => r.style.opacity = '1');

            if (this.srcIndex !== null && this.srcIndex !== toIndex) {
                wire.moveBlock(this.srcIndex, toIndex);
            }
            this.srcIndex = null;
        }
    }"
    class="lesson-toolbar"
    style="
        width: 256px;
        flex-shrink: 0;
        border: 0.5px solid var(--border);
        border-radius: 12px;
        background: var(--bg);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        height: fit-content;
        position: sticky;
        top: 16px;
    "
>

    {{-- ── Tab Bar ──────────────────────────────────────────── --}}
    <div style="display:flex;border-bottom:0.5px solid var(--border)">
        @foreach([
            ['add',     'Add',     '<line x1="8" y1="2" x2="8" y2="14"/><line x1="2" y1="8" x2="14" y2="8"/>'],
            ['outline', 'Outline', '<rect x="2" y="2" width="12" height="3" rx="1"/><rect x="2" y="6.5" width="9" height="3" rx="1"/><rect x="2" y="11" width="11" height="3" rx="1"/>'],
            ['reorder', 'Reorder', '<line x1="3" y1="4" x2="13" y2="4"/><line x1="3" y1="8" x2="13" y2="8"/><line x1="3" y1="12" x2="13" y2="12"/>'],
            ['tools',   'Tools',   '<circle cx="8" cy="8" r="2.5"/><path d="M8 1v2M8 13v2M1 8h2M13 8h2"/>'],
        ] as [$tab, $label, $paths])
            <button
                type="button"
                x-on:click="activeTab = '{{ $tab }}'"
                :style="activeTab === '{{ $tab }}'
                ? 'flex:1;padding:10px 4px;font-size:11px;font-weight:500;background:var(--bg-subtle);color:var(--text);border:none;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:3px;border-bottom:2px solid #534AB7'
                : 'flex:1;padding:10px 4px;font-size:11px;font-weight:500;background:transparent;color:var(--text-faint);border:none;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:3px'"
            >
                <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">{!! $paths !!}</svg>
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ── PAGE 1 · Add Blocks ──────────────────────────────── --}}
    <div x-show="activeTab === 'add'" style="padding:12px;overflow-y:auto">

        @php
            /*
             * Types must match the  'required|in:...'  validation list in
             * blockcontroller@store exactly.
             */
            $addGroups = [
                'Text & Layout' => [
                    ['H1',    'Heading 1',     'header',      '#EEEDFE', '#534AB7'],
                    ['H2',    'Heading 2',     'header',      '#E6F1FB', '#185FA5'],
                    ['¶',     'Paragraph',     'description', 'var(--bg-subtle)', 'var(--text-faint)'],
                    ['≡',     'Note',          'note',        '#EAF3DE', '#3B6D11'],
                    ['•',     'List',          'list',        '#FAEEDA', '#854F0B'],
                    ['"',     'Quote',         'description', '#FBEAF0', '#993556'],
                ],
                'Math & Code' => [
                    ['∑',     'Math (LaTeX)',  'math',        '#EEEDFE', '#534AB7'],
                    ['f(x)',  'Function',      'function',    '#E1F5EE', '#0F6E56'],
                    ['</>',   'Code',          'code',        '#F1EFE8', '#444441'],
                    ['≈',     'Graph',         'graph',       '#E6F1FB', '#185FA5'],
                ],
                'Media' => [
                    ['⬜',    'Image',         'photo',       '#FAECE7', '#993C1D'],
                    ['▶',     'Video',         'video',       '#FCEBEB', '#A32D2D'],
                    ['—',     'Separator',     'separator',   '#F1EFE8', '#5F5E5A'],
                    ['⊞',     'HTML / Embed',  'ext',         '#F1EFE8', '#444441'],
                ],
                'Interactive' => [
                    ['?',     'Exercise',      'exercise',    '#EEEDFE', '#534AB7'],
                    ['▦',     'Table',         'table',       '#E1F5EE', '#0F6E56'],
                ],
            ];
        @endphp

        @foreach($addGroups as $groupLabel => $items)
            <div style="font-size:10px;font-weight:500;color:var(--text-faint);letter-spacing:.06em;text-transform:uppercase;margin:12px 0 6px;padding:0 2px">
                {{ $groupLabel }}
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:5px;margin-bottom:4px">
                @foreach($items as [$icon, $label, $type, $bg, $fg])
                    <button
                        type="button"
                        onclick="window.dispatchEvent(new CustomEvent('add-block', { detail: { type: '{{ $type }}' } }))"
                        style="border:0.5px solid var(--border);border-radius:8px;background:var(--bg);padding:8px 6px;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;transition:background .15s"
                        onmouseover="this.style.background='var(--bg-subtle)'"
                        onmouseout="this.style.background='var(--bg)'"
                    >
                        <div style="width:28px;height:28px;border-radius:6px;background:{{ $bg }};color:{{ $fg }};display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;flex-shrink:0">
                            {{ $icon }}
                        </div>
                        <span style="font-size:10px;color:var(--text-faint);text-align:center;line-height:1.2">{{ $label }}</span>
                    </button>
                @endforeach
            </div>
        @endforeach

    </div>

    {{-- ── PAGE 2 · Outline ─────────────────────────────────── --}}
    <div x-show="activeTab === 'outline'" style="padding:12px;overflow-y:auto;max-height:600px">
        <input
            type="text"
            placeholder="Filter blocks..."
            x-model="outlineSearch"
            style="width:100%;padding:7px 10px;border:0.5px solid var(--border);border-radius:8px;background:var(--bg-subtle);color:var(--text);font-size:12px;margin-bottom:8px"
        >
        @forelse($blocks as $i => $block)
            @php
                // For JSON-content types, show the type label instead of raw JSON
                $jsonTypes = ['graph','table','function','list','separator'];
                $label = in_array($block['type'], $jsonTypes)
                    ? strtoupper($block['type'])
                    : Str::limit($block['content'] ?? '(empty)', 28);
            @endphp
            <div
                style="display:flex;align-items:center;gap:7px;padding:5px 6px;border-radius:6px;cursor:pointer;font-size:12px;color:var(--text-faint);transition:background .12s"
                onmouseover="this.style.background='var(--bg-subtle)';this.style.color='var(--text)'"
                onmouseout="this.style.background='transparent';this.style.color='var(--text-faint)'"
                onclick="document.querySelectorAll('.block-card')[{{ $i }}]?.scrollIntoView({behavior:'smooth',block:'center'})"
                x-show="outlineSearch === '' || '{{ strtolower(addslashes($label)) }}'.includes(outlineSearch.toLowerCase()) || '{{ $block['type'] }}'.includes(outlineSearch.toLowerCase())"
            >
            <span style="font-size:11px;flex:1;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">
                {{ $label }}
            </span>
                <span style="font-size:9px;font-weight:500;padding:1px 5px;border-radius:4px;background:var(--bg-subtle);color:var(--text-faint);flex-shrink:0">
                {{ strtoupper($block['type']) }}
            </span>
            </div>
        @empty
            <p style="font-size:12px;color:var(--text-faint);text-align:center;padding:20px 0">No blocks yet.</p>
        @endforelse
    </div>

    {{-- ── PAGE 3 · Drag Reorder ────────────────────────────── --}}
    <div x-show="activeTab === 'reorder'" style="padding:12px;overflow-y:auto;max-height:600px">
        <p style="font-size:11px;color:var(--text-faint);margin-bottom:8px">
            Drag rows to reorder. Calls the same swap logic as the ↑↓ buttons.
        </p>
        <div id="reorder-list">
            @foreach($blocks as $i => $block)
                @php
                    $jsonTypes = ['graph','table','function','list','separator'];
                    $rowLabel  = in_array($block['type'], $jsonTypes)
                        ? strtoupper($block['type'])
                        : Str::limit($block['content'] ?? '(empty)', 22);
                @endphp
                <div
                    class="reorder-row"
                    draggable="true"
                    data-index="{{ $i }}"
                    data-id="{{ $block['id'] }}"
                    x-on:dragstart="dragStart($event)"
                    x-on:dragover.prevent="dragOver($event)"
                    x-on:dragleave="dragLeave($event)"
                    x-on:drop="drop($event, $wire)"
                    style="display:flex;align-items:center;gap:8px;padding:6px 8px;border:0.5px solid var(--border);border-radius:8px;background:var(--bg);margin-bottom:5px;cursor:grab;font-size:12px;color:var(--text);user-select:none;transition:opacity .12s,border-color .12s,background .12s"
                >
                    <span style="color:var(--text-faint);font-size:14px;line-height:1">⠿</span>
                    <span style="font-size:10px;color:var(--text-faint);min-width:16px;text-align:right">{{ $i + 1 }}</span>
                    <span style="flex:1;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">{{ $rowLabel }}</span>
                    <span style="font-size:9px;font-weight:500;padding:1px 5px;border-radius:4px;background:var(--bg-subtle);color:var(--text-faint)">
                    {{ strtoupper($block['type']) }}
                </span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── PAGE 4 · Tools ───────────────────────────────────── --}}
    <div x-show="activeTab === 'tools'" style="padding:12px;overflow-y:auto">

        {{-- Stats — counted from $blocks array, same shape as blocks.blade.php --}}
        <div style="font-size:10px;font-weight:500;color:var(--text-faint);letter-spacing:.06em;text-transform:uppercase;margin-bottom:8px">Lesson stats</div>
        @php
            $counts = collect($blocks)->countBy('type');
            $stats  = [
                'Total blocks' => count($blocks),
                'Exercises'    => $counts->get('exercise', 0),
                'Media'        => $counts->get('photo', 0) + $counts->get('video', 0),
                'Code blocks'  => $counts->get('code', 0),
                'Math / Fn'    => $counts->get('math', 0) + $counts->get('function', 0),
                'Graphs'       => $counts->get('graph', 0),
            ];
        @endphp
        @foreach($stats as $statLabel => $val)
            <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 8px;border-radius:6px;font-size:12px;margin-bottom:4px;background:var(--bg-subtle)">
                <span style="color:var(--text-faint)">{{ $statLabel }}</span>
                <span style="font-weight:500;font-size:13px;color:var(--text)">{{ $val }}</span>
            </div>
        @endforeach

        <div style="font-size:10px;font-weight:500;color:var(--text-faint);letter-spacing:.06em;text-transform:uppercase;margin:14px 0 8px">Quick actions</div>

        {{-- Renumber: calls renumberAll() above which re-sequences block_number --}}
        <button
            type="button"
            wire:click="renumberAll"
            style="width:100%;padding:8px 10px;border:0.5px solid var(--border);border-radius:8px;background:var(--bg);color:var(--text);font-size:12px;cursor:pointer;text-align:left;display:flex;align-items:center;gap:8px;margin-bottom:5px"
            onmouseover="this.style.background='var(--bg-subtle)'" onmouseout="this.style.background='var(--bg)'"
        >
            ⟳ Renumber all blocks
        </button>

        {{--
            Preview link — uses the same route as blockcontroller@index.
            Adjust the route name to match your web.php if different.
        --}}
        <a
            href="{{ route('admin.courses.chapters.lessons.blocks.index', [$course->id, $chapter->id, $lesson->id]) }}"
            target="_blank"
            style="width:100%;padding:8px 10px;border:0.5px solid var(--border);border-radius:8px;background:var(--bg);color:var(--text);font-size:12px;cursor:pointer;text-align:left;display:flex;align-items:center;gap:8px;margin-bottom:5px;text-decoration:none"
            onmouseover="this.style.background='var(--bg-subtle)'" onmouseout="this.style.background='var(--bg)'"
        >
            ↗ Open lesson in new tab
        </a>

        <div style="background:#FAEEDA;border:0.5px solid #BA7517;border-radius:8px;padding:8px 10px;font-size:11px;color:#633806;margin-top:8px;line-height:1.5">
            Tip: drag rows in the Reorder tab to move blocks without scrolling through the full editor.
        </div>
    </div>

</div>
