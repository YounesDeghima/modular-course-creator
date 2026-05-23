{{--
    lesson-toolbar.blade.php  (v2 — full-height, clean, insert-between drag)
    ─────────────────────────────────────────────────────────────────────────
    • Toolbar fills the right-sidebar height (flex column, no fit-content)
    • Add tab: compact icon grid, no category labels cluttering space
    • Drag-reorder: drop-zones appear BETWEEN blocks while dragging;
      dropping on a gap inserts there (not just swap)
    • After BlockCreated the new card is scrolled into view

    Usage: <livewire:modular_site.block.lesson-toolbar
                :lesson="$lesson" :course="$course"
                :chapter="$chapter" :blocks="$blocks" />
--}}
<?php
use Livewire\Component;
use App\Models\block;

new class extends Component {

    public $lesson;
    public $course;
    public $chapter;
    public $blocks;

    public function mount($lesson, $course, $chapter, $blocks): void
    {
        $this->lesson  = $lesson;
        $this->course  = $course;
        $this->chapter = $chapter;
        $this->blocks  = is_array($blocks) ? $blocks : $blocks->toArray();
    }

    /**
     * Insert block at $insertAfterIndex (-1 = prepend).
     * Called by the new gap-drop system from JS.
     */
    public function insertBlockAt(int $insertAfterIndex): void
    {
        // Re-sequence so numbers are clean first
        $all = block::where('lesson_id', $this->lesson->id)
            ->orderBy('block_number')->get();

        // We need to shift everything after the insertion point up by 1
        $insertPosition = $insertAfterIndex + 2; // 1-based position for new block

        foreach ($all as $i => $b) {
            $pos = $i + 1;
            if ($pos >= $insertPosition) {
                $b->update(['block_number' => $pos + 1]);
            }
        }

        $newBlock = block::create([
            'lesson_id'    => $this->lesson->id,
            'type'         => 'description',
            'content'      => 'New paragraph text.',
            'block_number' => $insertPosition,
        ]);

        $this->refreshBlocks();
        $this->dispatch('BlockCreated', id: $newBlock->id);
        $this->dispatch('ScrollToBlock', blockId: $newBlock->id);
    }

    public function moveBlock(int $fromIndex, int $toIndex): void
    {
        if (
            $fromIndex < 0 || $fromIndex >= count($this->blocks) ||
            $toIndex   < 0 || $toIndex   >= count($this->blocks)
        ) return;

        $item = array_splice($this->blocks, $fromIndex, 1)[0];
        array_splice($this->blocks, $toIndex, 0, [$item]);

        foreach ($this->blocks as $i => $b) {
            block::where('id', $b['id'])->update(['block_number' => $i + 1]);
        }

        $this->dispatch('notify', message: 'Block moved!');
    }

    public function renumberAll(): void
    {
        $fresh = block::where('lesson_id', $this->lesson->id)
            ->orderBy('block_number')->get();

        foreach ($fresh as $i => $b) {
            $b->update(['block_number' => $i + 1]);
        }

        $this->blocks = $fresh->map(fn($b) => $b->toArray())->toArray();
        $this->dispatch('notify', message: 'Blocks renumbered!');
    }

    public function getListeners(): array
    {
        return ['BlockCreated' => 'refreshBlocks'];
    }

    public function refreshBlocks(): void
    {
        $this->blocks = block::where('lesson_id', $this->lesson->id)
            ->orderBy('block_number')
            ->get()
            ->map(fn($b) => $b->toArray())
            ->toArray();
    }

    public function transferBlocks(array $blockIds, int $targetLessonId, string $mode = 'move'): void
    {
        if (empty($blockIds) || !$targetLessonId) {
            $this->dispatch('notify', message: 'Select blocks and a target lesson first.');
            return;
        }
        $targetLesson = \App\Models\lesson::find($targetLessonId);
        if (!$targetLesson) {
            $this->dispatch('notify', message: 'Target lesson not found.');
            return;
        }
        $maxNum = block::where('lesson_id', $targetLessonId)->max('block_number') ?? 0;
        foreach ($blockIds as $i => $blockId) {
            $blk = block::find($blockId);
            if (!$blk) continue;
            if ($mode === 'copy') {
                $newBlk = $blk->replicate();
                $newBlk->lesson_id    = $targetLessonId;
                $newBlk->block_number = $maxNum + $i + 1;
                $newBlk->save();
                foreach ($blk->solutions as $sol) {
                    $newSol = $sol->replicate();
                    $newSol->block_id = $newBlk->id;
                    $newSol->save();
                }
            } else {
                $blk->update(['lesson_id' => $targetLessonId, 'block_number' => $maxNum + $i + 1]);
            }
        }
        $fresh = block::where('lesson_id', $this->lesson->id)->orderBy('block_number')->get();
        foreach ($fresh as $idx => $b) {
            $b->update(['block_number' => $idx + 1]);
        }
        $this->refreshBlocks();
        $count = count($blockIds);
        $verb  = $mode === 'copy' ? 'Copied' : 'Moved';
        $this->dispatch('notify', message: "{$verb} {$count} block(s) to {$targetLesson->title}!");
        $this->dispatch('BlockCreated', id: 0);
    }

    public function mergeLesson(int $sourceLessonId, string $mode = 'move'): void
    {
        if (!$sourceLessonId) return;
        $sourceLesson = \App\Models\lesson::find($sourceLessonId);
        if (!$sourceLesson) return;

        $sourceBlocks = block::where('lesson_id', $sourceLessonId)->orderBy('block_number')->get();
        $maxNum = block::where('lesson_id', $this->lesson->id)->max('block_number') ?? 0;

        foreach ($sourceBlocks as $i => $blk) {
            if ($mode === 'copy') {
                $newBlk = $blk->replicate();
                $newBlk->lesson_id    = $this->lesson->id;
                $newBlk->block_number = $maxNum + $i + 1;
                $newBlk->save();
                foreach ($blk->solutions as $sol) {
                    $newSol = $sol->replicate();
                    $newSol->block_id = $newBlk->id;
                    $newSol->save();
                }
            } else {
                $blk->update(['lesson_id' => $this->lesson->id, 'block_number' => $maxNum + $i + 1]);
            }
        }

        $this->refreshBlocks();
        $verb = $mode === 'copy' ? 'Copied' : 'Merged';
        $this->dispatch('notify', message: "{$verb} {$sourceLesson->title} into this lesson!");
        $this->dispatch('BlockCreated', id: 0);
    }
};
?>

<div
    x-data="{
        activeTab: 'add',
        outlineSearch: '',
        srcIndex: null,
        isDragging: false,

        dragStart(e, index) {
            this.srcIndex  = index;
            this.isDragging = true;
            e.currentTarget.style.opacity = '0.35';
            e.dataTransfer.effectAllowed = 'move';
        },

        dragEnd(e) {
            this.isDragging = false;
            this.srcIndex   = null;
            document.querySelectorAll('.reorder-row').forEach(r => r.style.opacity = '1');
        },

        dropOnRow(e, wire, toIndex) {
            e.preventDefault();
            this.isDragging = false;
            document.querySelectorAll('.reorder-row').forEach(r => {
                r.style.opacity      = '1';
                r.style.borderColor  = 'var(--border)';
                r.style.background   = 'var(--bg)';
            });
            if (this.srcIndex !== null && this.srcIndex !== toIndex) {
                wire.moveBlock(this.srcIndex, toIndex);
            }
            this.srcIndex = null;
        },

        dropOnGap(e, wire, afterIndex) {
            e.preventDefault();
            e.stopPropagation();
            this.isDragging = false;
            document.querySelectorAll('.reorder-row').forEach(r => {
                r.style.opacity     = '1';
                r.style.borderColor = 'var(--border)';
                r.style.background  = 'var(--bg)';
            });

            if (this.srcIndex === null) { this.srcIndex = null; return; }

            // Calculate real toIndex after gap insertion logic:
            // We want to place srcIndex AFTER afterIndex.
            // If moving down: toIndex = afterIndex
            // If moving up:   toIndex = afterIndex + 1
            let from = this.srcIndex;
            let to   = afterIndex + 1; // position after the gap
            if (from > afterIndex) to = afterIndex + 1;
            else to = afterIndex;

            if (from !== to && to >= 0) {
                wire.moveBlock(from, to);
            }
            this.srcIndex = null;
        }
    }"
    style="
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
    "
>

    {{-- ── Tab Bar ─────────────────────────────────────── --}}
    <div style="display:flex;border-bottom:1px solid var(--border);flex-shrink:0;background:var(--bg)">
        @foreach([
            ['add',      '＋',  'Add'],
            ['outline',  '☰',   'Outline'],
            ['reorder',  '⠿',   'Reorder'],
            ['transfer', '⇄',   'Transfer'],
            ['tools',    '⚙',   'Tools'],
        ] as [$tab, $icon, $label])
            <button
                type="button"
                x-on:click="activeTab = '{{ $tab }}'"
                :style="activeTab === '{{ $tab }}'
                    ? 'flex:1;padding:10px 2px 8px;font-size:10px;font-weight:600;background:var(--bg);color:var(--accent);border:none;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:2px;border-bottom:2px solid var(--accent)'
                    : 'flex:1;padding:10px 2px 8px;font-size:10px;font-weight:500;background:transparent;color:var(--text-faint);border:none;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:2px;border-bottom:2px solid transparent'"
            >
                <span style="font-size:15px;line-height:1">{{ $icon }}</span>
                <span>{{ $label }}</span>
            </button>
        @endforeach
    </div>

    {{-- ── PAGE 1 · Add Blocks ────────────────────────── --}}
    <div x-show="activeTab === 'add'" style="padding:10px;overflow-y:auto;flex:1">
        @php
            $addBlocks = [
                ['H1',    'Heading',    'header',      '#EEEDFE', '#534AB7'],
                ['¶',     'Paragraph',  'description', 'var(--bg-subtle)', 'var(--text-muted)'],
                ['≡',     'Note',       'note',        '#EAF3DE', '#3B6D11'],
                ['•',     'List',       'list',        '#FAEEDA', '#854F0B'],
                ['"',     'Quote',      'description', '#FBEAF0', '#993556'],
                ['∑',     'Math',       'math',        '#EEEDFE', '#534AB7'],
                ['f(x)',  'Function',   'function',    '#E1F5EE', '#0F6E56'],
                ['</>',  'Code',       'code',        '#F1EFE8', '#444441'],
                ['≈',     'Graph',      'graph',       '#E6F1FB', '#185FA5'],
                ['⬜',    'Image',      'photo',       '#FAECE7', '#993C1D'],
                ['▶',     'Video',      'video',       '#FCEBEB', '#A32D2D'],
                ['—',     'Separator',  'separator',   '#F1EFE8', '#5F5E5A'],
                ['⊞',     'HTML',       'ext',         '#F1EFE8', '#444441'],
                ['?',     'Exercise',   'exercise',    '#EEEDFE', '#534AB7'],
                ['▦',     'Table',      'table',       '#E1F5EE', '#0F6E56'],
            ];
        @endphp
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:5px">
            @foreach($addBlocks as [$icon, $label, $type, $bg, $fg])
                <button
                    type="button"
                    onclick="window.dispatchEvent(new CustomEvent('add-block', { detail: { type: '{{ $type }}' } }))"
                    style="border:1px solid var(--border);border-radius:8px;background:var(--bg);padding:8px 4px 6px;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;transition:background .12s,border-color .12s"
                    onmouseover="this.style.background='var(--bg-subtle)';this.style.borderColor='var(--accent)'"
                    onmouseout="this.style.background='var(--bg)';this.style.borderColor='var(--border)'"
                >
                    <div style="width:28px;height:28px;border-radius:6px;background:{{ $bg }};color:{{ $fg }};display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">
                        {{ $icon }}
                    </div>
                    <span style="font-size:9px;color:var(--text-faint);text-align:center;line-height:1.2;white-space:nowrap">{{ $label }}</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- ── PAGE 2 · Outline ──────────────────────────── --}}
    <div x-show="activeTab === 'outline'" style="padding:10px;overflow-y:auto;flex:1">
        <input
            type="text"
            placeholder="Filter…"
            x-model="outlineSearch"
            style="width:100%;padding:6px 9px;border:1px solid var(--border);border-radius:7px;background:var(--bg-subtle);color:var(--text);font-size:12px;margin-bottom:8px;font-family:inherit"
        >
        @forelse($blocks as $i => $block)
            @php
                $jsonTypes = ['graph','table','function','list','separator'];
                $rawLabel  = in_array($block['type'], $jsonTypes)
                    ? strtoupper($block['type'])
                    : ($block['content'] ?? '(empty)');
                // Strip tags, collapse whitespace/newlines to a single space, then limit
                $label = Str::limit(preg_replace('/\s+/', ' ', strip_tags($rawLabel)), 26);
            @endphp
            <div
                style="display:flex;align-items:center;gap:6px;padding:5px 6px;border-radius:6px;cursor:pointer;font-size:12px;color:var(--text-faint);transition:background .1s"
                onmouseover="this.style.background='var(--bg-subtle)';this.style.color='var(--text)'"
                onmouseout="this.style.background='transparent';this.style.color='var(--text-faint)'"
                onclick="document.querySelectorAll('[data-block-id]')[{{ $i }}]?.scrollIntoView({behavior:'smooth',block:'center'})"
                data-outline-label="{{ strtolower($label) }}"
                data-outline-type="{{ $block['type'] }}"
                x-show="outlineSearch === '' || $el.dataset.outlineLabel.includes(outlineSearch.toLowerCase()) || $el.dataset.outlineType.includes(outlineSearch.toLowerCase())"
            >
                <span style="font-size:10px;flex-shrink:0;font-weight:600;color:var(--text-faint);min-width:16px">{{ $i+1 }}</span>
                <span style="flex:1;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;font-size:11px">{{ $label }}</span>
                <span style="font-size:9px;padding:1px 5px;border-radius:4px;background:var(--bg-subtle);color:var(--text-faint);flex-shrink:0">{{ strtoupper($block['type']) }}</span>
            </div>
        @empty
            <p style="font-size:12px;color:var(--text-faint);text-align:center;padding:24px 0">No blocks yet.</p>
        @endforelse
    </div>

    {{-- ── PAGE 3 · Drag Reorder (insert-between) ───── --}}
    <div x-show="activeTab === 'reorder'" style="padding:10px;overflow-y:auto;flex:1">
        <p style="font-size:11px;color:var(--text-faint);margin-bottom:8px;line-height:1.5">
            Drag a row. Blue gaps appear — drop on a gap to insert there.
        </p>

        {{-- Top gap (insert before first block) --}}
        <div
            class="reorder-gap"
            x-show="isDragging"
            x-on:dragover.prevent="$el.style.background='#eeedfe';$el.style.borderColor='var(--accent)'"
            x-on:dragleave="$el.style.background='transparent';$el.style.borderColor='transparent'"
            x-on:drop="dropOnGap($event, $wire, -1); $el.style.background='transparent';$el.style.borderColor='transparent'"
            style="height:10px;border-radius:4px;border:2px dashed transparent;margin-bottom:3px;transition:all .12s;cursor:pointer"
        ></div>

        @foreach($blocks as $i => $block)
            @php
                $jsonTypes = ['graph','table','function','list','separator'];
                $rowLabel  = in_array($block['type'], $jsonTypes)
                    ? strtoupper($block['type'])
                    : Str::limit(preg_replace('/\s+/', ' ', strip_tags($block['content'] ?? '(empty)')), 20);
            @endphp

            {{-- Draggable row --}}
            <div
                class="reorder-row"
                draggable="true"
                data-index="{{ $i }}"
                data-id="{{ $block['id'] }}"
                x-on:dragstart="dragStart($event, {{ $i }})"
                x-on:dragend="dragEnd($event)"
                x-on:dragover.prevent="$el.style.borderColor='#534AB7';$el.style.background='#EEEDFE'"
                x-on:dragleave="$el.style.borderColor='var(--border)';$el.style.background='var(--bg)'"
                x-on:drop="dropOnRow($event, $wire, {{ $i }})"
                style="display:flex;align-items:center;gap:7px;padding:6px 8px;border:1px solid var(--border);border-radius:8px;background:var(--bg);margin-bottom:3px;cursor:grab;font-size:12px;color:var(--text);user-select:none;transition:opacity .1s,border-color .1s,background .1s"
            >
                <span style="color:var(--text-faint);font-size:15px;line-height:1;flex-shrink:0">⠿</span>
                <span style="font-size:10px;color:var(--text-faint);min-width:14px;text-align:right;flex-shrink:0">{{ $i + 1 }}</span>
                <span style="flex:1;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">{{ $rowLabel }}</span>
                <span style="font-size:9px;padding:1px 5px;border-radius:4px;background:var(--bg-subtle);color:var(--text-faint);flex-shrink:0">{{ strtoupper($block['type']) }}</span>
            </div>

            {{-- Gap after this row --}}
            <div
                class="reorder-gap"
                x-show="isDragging"
                x-on:dragover.prevent="$el.style.background='#eeedfe';$el.style.borderColor='var(--accent)'"
                x-on:dragleave="$el.style.background='transparent';$el.style.borderColor='transparent'"
                x-on:drop="dropOnGap($event, $wire, {{ $i }}); $el.style.background='transparent';$el.style.borderColor='transparent'"
                style="height:10px;border-radius:4px;border:2px dashed transparent;margin-bottom:3px;transition:all .12s;cursor:pointer"
            ></div>

        @endforeach
    </div>

    {{-- ── PAGE 4 · Transfer / Merge ──────────────────── --}}
    <div x-show="activeTab === 'transfer'" style="padding:10px;overflow-y:auto;flex:1"
         x-data="{ selectedBlocks: [], targetLesson: '', mergeSource: '', mode: 'move', mergeMode: 'move' }">

        <div style="font-size:10px;font-weight:600;color:var(--text-faint);letter-spacing:.06em;text-transform:uppercase;margin-bottom:8px">Transfer blocks</div>

        <div style="max-height:160px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;padding:5px;margin-bottom:8px">
            @forelse($blocks as $b)
                @php
                    $jt  = ['graph','table','function','list','separator'];
                    $lbl = in_array($b['type'], $jt) ? strtoupper($b['type']) : Str::limit(preg_replace('/\s+/', ' ', strip_tags($b['content'] ?? '(empty)')), 22);
                @endphp
                <label style="display:flex;align-items:center;gap:6px;padding:4px 5px;border-radius:5px;cursor:pointer;font-size:12px;color:var(--text)"
                       onmouseover="this.style.background='var(--bg-subtle)'" onmouseout="this.style.background='transparent'">
                    <input type="checkbox" :value="{{ $b['id'] }}" x-model="selectedBlocks" style="accent-color:var(--accent)">
                    <span style="font-size:9px;padding:1px 5px;border-radius:4px;background:var(--bg-subtle);color:var(--text-faint);flex-shrink:0">{{ strtoupper($b['type']) }}</span>
                    <span style="flex:1;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">{{ $lbl }}</span>
                </label>
            @empty
                <p style="font-size:12px;color:var(--text-faint);padding:8px;text-align:center">No blocks.</p>
            @endforelse
        </div>

        <div style="display:flex;gap:5px;margin-bottom:8px">
            <button type="button" x-on:click="selectedBlocks = {{ json_encode(collect($blocks)->pluck('id')->values()->all()) }}"
                    style="flex:1;padding:5px;border:1px solid var(--border);border-radius:6px;background:var(--bg);font-size:11px;color:var(--text-muted);cursor:pointer">All</button>
            <button type="button" x-on:click="selectedBlocks = []"
                    style="flex:1;padding:5px;border:1px solid var(--border);border-radius:6px;background:var(--bg);font-size:11px;color:var(--text-muted);cursor:pointer">None</button>
        </div>

        <select x-model="targetLesson" style="width:100%;padding:6px 8px;border:1px solid var(--border);border-radius:7px;background:var(--bg-subtle);color:var(--text);font-size:12px;font-family:inherit;margin-bottom:7px">
            <option value="">— destination lesson —</option>
            @foreach($course->chapters()->with('lessons')->orderBy('chapter_number')->get() as $ch)
                @foreach($ch->lessons()->orderBy('lesson_number')->get() as $ls)
                    @if($ls->id !== $lesson->id)
                        <option value="{{ $ls->id }}">Ch.{{ $ch->chapter_number }} · {{ Str::limit($ls->title, 28) }}</option>
                    @endif
                @endforeach
            @endforeach
        </select>

        <div style="display:flex;gap:5px;margin-bottom:8px">
            <button type="button" x-on:click="mode='move'" :style="mode==='move'?'flex:1;padding:5px;border-radius:6px;background:var(--accent);color:#fff;border:none;font-size:11px;font-weight:600;cursor:pointer':'flex:1;padding:5px;border-radius:6px;border:1px solid var(--border);background:var(--bg);color:var(--text-muted);font-size:11px;cursor:pointer'">✂ Move</button>
            <button type="button" x-on:click="mode='copy'" :style="mode==='copy'?'flex:1;padding:5px;border-radius:6px;background:var(--accent);color:#fff;border:none;font-size:11px;font-weight:600;cursor:pointer':'flex:1;padding:5px;border-radius:6px;border:1px solid var(--border);background:var(--bg);color:var(--text-muted);font-size:11px;cursor:pointer'">⎘ Copy</button>
        </div>

        <button type="button"
                x-on:click="if(!selectedBlocks.length||!targetLesson)return; $wire.transferBlocks(selectedBlocks.map(Number),Number(targetLesson),mode); selectedBlocks=[];targetLesson='';"
                :disabled="!selectedBlocks.length||!targetLesson"
                :style="(!selectedBlocks.length||!targetLesson)?'width:100%;padding:8px;border-radius:8px;background:var(--accent);color:#fff;border:none;font-size:12px;font-weight:600;opacity:.4;cursor:not-allowed':'width:100%;padding:8px;border-radius:8px;background:var(--accent);color:#fff;border:none;font-size:12px;font-weight:600;cursor:pointer'"
        >Transfer <span x-text="selectedBlocks.length?'('+selectedBlocks.length+')':''"></span></button>

        <div style="height:1px;background:var(--border);margin:14px 0"></div>
        <div style="font-size:10px;font-weight:600;color:var(--text-faint);letter-spacing:.06em;text-transform:uppercase;margin-bottom:8px">Merge lesson here</div>

        <select x-model="mergeSource" style="width:100%;padding:6px 8px;border:1px solid var(--border);border-radius:7px;background:var(--bg-subtle);color:var(--text);font-size:12px;font-family:inherit;margin-bottom:7px">
            <option value="">— source lesson —</option>
            @foreach($course->chapters()->with('lessons')->orderBy('chapter_number')->get() as $ch)
                @foreach($ch->lessons()->orderBy('lesson_number')->get() as $ls)
                    @if($ls->id !== $lesson->id)
                        <option value="{{ $ls->id }}">Ch.{{ $ch->chapter_number }} · {{ Str::limit($ls->title, 28) }}</option>
                    @endif
                @endforeach
            @endforeach
        </select>

        <div style="display:flex;gap:5px;margin-bottom:8px">
            <button type="button" x-on:click="mergeMode='move'" :style="mergeMode==='move'?'flex:1;padding:5px;border-radius:6px;background:var(--accent);color:#fff;border:none;font-size:11px;font-weight:600;cursor:pointer':'flex:1;padding:5px;border-radius:6px;border:1px solid var(--border);background:var(--bg);color:var(--text-muted);font-size:11px;cursor:pointer'">✂ Move</button>
            <button type="button" x-on:click="mergeMode='copy'" :style="mergeMode==='copy'?'flex:1;padding:5px;border-radius:6px;background:var(--accent);color:#fff;border:none;font-size:11px;font-weight:600;cursor:pointer':'flex:1;padding:5px;border-radius:6px;border:1px solid var(--border);background:var(--bg);color:var(--text-muted);font-size:11px;cursor:pointer'">⎘ Copy</button>
        </div>

        <button type="button"
                x-on:click="if(!mergeSource)return; $wire.mergeLesson(Number(mergeSource),mergeMode); mergeSource='';"
                :disabled="!mergeSource"
                :style="!mergeSource?'width:100%;padding:8px;border-radius:8px;background:#6366f1;color:#fff;border:none;font-size:12px;font-weight:600;opacity:.4;cursor:not-allowed':'width:100%;padding:8px;border-radius:8px;background:#6366f1;color:#fff;border:none;font-size:12px;font-weight:600;cursor:pointer'"
        >Merge into this lesson</button>
    </div>

    {{-- ── PAGE 5 · Tools ──────────────────────────────── --}}
    <div x-show="activeTab === 'tools'" style="padding:10px;overflow-y:auto;flex:1">
        <div style="font-size:10px;font-weight:600;color:var(--text-faint);letter-spacing:.06em;text-transform:uppercase;margin-bottom:8px">Stats</div>
        @php
            $counts = collect($blocks)->countBy('type');
            $stats  = [
                'Total'      => count($blocks),
                'Exercises'  => $counts->get('exercise', 0),
                'Media'      => $counts->get('photo', 0) + $counts->get('video', 0),
                'Code'       => $counts->get('code', 0),
                'Math / Fn'  => $counts->get('math', 0) + $counts->get('function', 0),
                'Graphs'     => $counts->get('graph', 0),
            ];
        @endphp
        @foreach($stats as $statLabel => $val)
            <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 8px;border-radius:6px;font-size:12px;margin-bottom:3px;background:var(--bg-subtle)">
                <span style="color:var(--text-faint)">{{ $statLabel }}</span>
                <span style="font-weight:600;color:var(--text)">{{ $val }}</span>
            </div>
        @endforeach

        <div style="font-size:10px;font-weight:600;color:var(--text-faint);letter-spacing:.06em;text-transform:uppercase;margin:12px 0 8px">Actions</div>

        <button type="button" wire:click="renumberAll"
                style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text);font-size:12px;cursor:pointer;text-align:left;display:flex;align-items:center;gap:7px;margin-bottom:5px"
                onmouseover="this.style.background='var(--bg-subtle)'" onmouseout="this.style.background='var(--bg)'">
            ⟳ Renumber all blocks
        </button>

        <a href="{{ route('admin.courses.chapters.lessons.blocks.index', [$course->id, $chapter->id, $lesson->id]) }}"
           target="_blank"
           style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text);font-size:12px;cursor:pointer;text-align:left;display:flex;align-items:center;gap:7px;margin-bottom:5px;text-decoration:none"
           onmouseover="this.style.background='var(--bg-subtle)'" onmouseout="this.style.background='var(--bg)'">
            ↗ Open in new tab
        </a>
    </div>

    {{-- ── Save All — always pinned at bottom ────────── --}}
    <div style="padding:10px;border-top:1px solid var(--border);background:var(--bg-subtle);flex-shrink:0"
         x-data="{ saved: false }"
         x-on:notify.window="if($event.detail.message==='Saved!'){saved=true;setTimeout(()=>saved=false,2500)}">
        <button
            type="button"
            x-on:click="window.dispatchEvent(new CustomEvent('toolbar-save'))"
            :style="saved
                ? 'width:100%;background:#22c55e;color:#fff;border:none;border-radius:8px;padding:9px 14px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;transition:background .25s'
                : 'width:100%;background:var(--accent);color:#fff;border:none;border-radius:8px;padding:9px 14px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;transition:background .25s'"
        >
            <span x-show="!saved">💾 Save All</span>
            <span x-show="saved" x-cloak style="display:none">✓ Saved!</span>
        </button>
    </div>

</div>
