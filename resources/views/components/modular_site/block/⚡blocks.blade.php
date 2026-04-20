<?php
// ── PHP SECTION UNCHANGED ──
use App\Models\block;
use App\Models\exercisesolution;
use App\Models\lesson;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    public $course;
    public $chapter;
    public $lesson;
    public $blocks;
    public $photos = [];
    public $videos = [];

    use WithFileUploads;

    public $listeners = ['LessonChanged' => 'updateLesson',
        'BlockCreated' => 'addBlock'];

    public function mount($course, $chapter, $lesson = null, $blocks = null)
    {
        $this->course  = $course;
        $this->chapter = $chapter;
        $this->lesson  = $lesson;

        if ($blocks && $blocks->count() > 0) {
            $this->blocks = $blocks->map(function ($block) {
                $arr = $block->toArray();
                if ($block->type === 'exercise') {
                    $arr['solutions'] = $block->solutions->map(fn($s) => $s->toArray())->toArray();
                }
                $this->hydrateBlockFields($arr);
                return $arr;
            })->toArray();
        } else {
            $this->blocks = [];
        }
    }

    public function addBlock($id)
    {
        $block = block::findOrFail($id);
        if ($block) $this->blocks[] = $block->toArray();
    }

    public function updateLesson($id, $chapterId)
    {
        $this->blocks = block::where('lesson_id', $id)
            ->orderBy('block_number')
            ->with('solutions')
            ->get()
            ->map(function ($b) {
                $arr = $b->toArray();
                if ($b->type === 'exercise') {
                    $arr['solutions'] = $b->solutions->map(fn($s) => $s->toArray())->toArray();
                }
                $this->hydrateBlockFields($arr);
                return $arr;
            })
            ->toArray();

        $this->lesson  = lesson::findorfail($id);
        $this->chapter = $this->lesson->chapter;
    }

    public function updatedBlocks($value, $key)
    {
        if (str_contains($key, 'func_expression') || str_contains($key, 'x_min') ||
            str_contains($key, 'x_max')  || str_contains($key, 'y_min') ||
            str_contains($key, 'y_max')  || str_contains($key, 'color') ||
            str_contains($key, 'step')) {

            $index = explode('.', $key)[0];
            $this->blocks[$index]['content'] = json_encode([
                'function' => $this->blocks[$index]['func_expression'] ?? 'sin(x)',
                'x_min'    => $this->blocks[$index]['x_min']  ?? -10,
                'x_max'    => $this->blocks[$index]['x_max']  ?? 10,
                'y_min'    => $this->blocks[$index]['y_min']  ?? -5,
                'y_max'    => $this->blocks[$index]['y_max']  ?? 5,
                'color'    => $this->blocks[$index]['color']  ?? '#4f46e5',
                'step'     => $this->blocks[$index]['step']   ?? 0.1,
            ]);
        }
    }

    public function saveAll()
    {
        if (!$this->lesson) return;

        foreach ($this->blocks as $blockData) {
            $content = $blockData['content'] ?? null;

            if ($blockData['type'] === 'list') {
                $items   = array_filter(array_map('trim', explode("\n", $blockData['content'] ?? '')));
                $content = json_encode(['style' => $blockData['list_style'] ?? 'bullet', 'items' => array_values($items)]);
            }
            if ($blockData['type'] === 'separator') {
                $content = json_encode(['type' => $blockData['sep_type'] ?? 'divider']);
            }
            if ($blockData['type'] === 'graph') {
                $content = json_encode([
                    'type'   => $blockData['graph_type']   ?? 'line',
                    'labels' => array_map('trim', explode(',', $blockData['graph_labels'] ?? '')),
                    'data'   => array_map('trim', explode(',', $blockData['graph_data']   ?? '')),
                ]);
            }
            if ($blockData['type'] === 'table') {
                $content = $blockData['table_json'] ?? $blockData['content'];
            }
            if ($blockData['type'] === 'function') {
                $content = json_encode([
                    'function' => $blockData['func_expression'] ?? 'sin(x)',
                    'x_min'    => $blockData['x_min']  ?? -10,
                    'x_max'    => $blockData['x_max']  ?? 10,
                    'y_min'    => $blockData['y_min']  ?? -5,
                    'y_max'    => $blockData['y_max']  ?? 5,
                    'color'    => $blockData['color']  ?? '#4f46e5',
                    'step'     => $blockData['step']   ?? 0.1,
                ]);
            }

            $blockUpdates[] = [
                'id'           => $blockData['id'],
                'content'      => $content,
                'type'         => $blockData['type'],
                'block_number' => $blockData['block_number'],
                'lesson_id'    => $this->lesson->id,
            ];

            if ($blockData['type'] === 'exercise') {
                foreach ($blockData['solutions'] ?? [] as $solution) {
                    exercisesolution::where('id', $solution['id'])->update(['content' => $solution['content']]);
                }
            }

            if (in_array($blockData['type'], ['photo', 'video'])) {
                $blockData['file_name'] = $blockData['content'] ? basename($blockData['content']) : 'No file selected';
            }
        }

        block::upsert($blockUpdates, ['id'], ['content', 'type', 'block_number', 'lesson_id']);
        $this->dispatch('notify', message: 'Saved!');
    }

    private function hydrateBlockFields(&$block)
    {
        if (in_array($block['type'], ['function', 'graph'])) {
            $data = json_decode($block['content'], true) ?? [];
            $block['func_expression'] = $data['function'] ?? 'y=sin(x)';
            $block['x_min']  = $data['x_min']  ?? -10;
            $block['x_max']  = $data['x_max']  ?? 10;
            $block['y_min']  = $data['y_min']  ?? -5;
            $block['y_max']  = $data['y_max']  ?? 5;
            $block['color']  = $data['color']  ?? '#4f46e5';
            $block['step']   = $data['step']   ?? 0.1;
        }
        if ($block['type'] === 'graph') {
            $data = json_decode($block['content'], true) ?? [];
            $block['graph_type']   = $data['type']   ?? 'line';
            $block['graph_labels'] = implode(',', $data['labels'] ?? []);
            $block['graph_data']   = implode(',', $data['data']   ?? []);
        }
        if ($block['type'] === 'list') {
            $data = json_decode($block['content'], true) ?? [];
            $block['list_style'] = $data['style'] ?? 'bullet';
            $block['content']    = implode("\n", $data['items'] ?? []);
        }
        if ($block['type'] === 'separator') {
            $data = json_decode($block['content'], true) ?? [];
            $block['sep_type'] = $data['type'] ?? 'divider';
        }
    }

    public function deleteBlock(int $index)
    {
        $blockData = $this->blocks[$index] ?? null;
        if (!$blockData) return;

        Block::destroy($blockData['id']);
        array_splice($this->blocks, $index, 1);

        if (!empty($this->blocks) && $this->lesson) {
            foreach ($this->blocks as $i => &$b) {
                $b['block_number'] = $i + 1;
                $updates[] = ['id' => $b['id'], 'block_number' => $i + 1, 'lesson_id' => $this->lesson->id, 'type' => $b['type'], 'content' => $b['content']];
            }
            block::upsert($updates, ['id'], ['block_number'], ['lesson_id']);
        }
    }

    public function moveBlock(int $index, string $direction)
    {
        $swapWith = $direction === 'up' ? $index - 1 : $index + 1;
        if ($swapWith < 0 || $swapWith >= count($this->blocks)) return;
        [$this->blocks[$index], $this->blocks[$swapWith]] = [$this->blocks[$swapWith], $this->blocks[$index]];
        foreach ($this->blocks as $i => &$b) {
            $b['block_number'] = $i + 1;
            block::where('id', $b['id'])->update(['block_number' => $b['block_number']]);
        }
    }

    public function updateBlockType(int $index, string $newType)
    {
        $this->blocks[$index]['type'] = $newType;
        if ($newType === 'table' && !is_array(json_decode($this->blocks[$index]['content'] ?? '', true))) {
            $this->blocks[$index]['content'] = json_encode([['Header 1', 'Header 2'], ['', '']]);
            Block::where('id', $this->blocks[$index]['id'])->update(['type' => $newType, 'content' => $this->blocks[$index]['content']]);
            return;
        }
        Block::where('id', $this->blocks[$index]['id'])->update(['type' => $newType]);
    }

    public function updatedPhotos($value, $key)
    {
        $path = $this->photos[$key]->store('blocks', 'public');
        foreach ($this->blocks as &$block) {
            if ($block['id'] == $key) { $block['content'] = $path; break; }
        }
    }

    public function updatedVideos($value, $key)
    {
        $path = $this->videos[$key]->store('blocks', 'public');
        foreach ($this->blocks as &$block) {
            if ($block['id'] == $key) { $block['content'] = $path; break; }
        }
    }

    public function addTableRow($blockId)
    {
        $index     = collect($this->blocks)->search(fn($b) => $b['id'] === $blockId);
        $tableData = json_decode($this->blocks[$index]['content'], true);
        $cols      = count($tableData[0] ?? ['']);
        $tableData[] = array_fill(0, $cols, '');
        $this->blocks[$index]['content'] = json_encode($tableData);
    }

    public function addTableCol($blockId)
    {
        $index     = collect($this->blocks)->search(fn($b) => $b['id'] === $blockId);
        $tableData = json_decode($this->blocks[$index]['content'], true);
        foreach ($tableData as &$row) { $row[] = ''; }
        $this->blocks[$index]['content'] = json_encode($tableData);
    }

    public function updateTableCell($blockId, $rowIndex, $colIndex, $value)
    {
        $index     = collect($this->blocks)->search(fn($b) => $b['id'] === $blockId);
        $tableData = json_decode($this->blocks[$index]['content'], true);
        $tableData[$rowIndex][$colIndex] = $value;
        $this->blocks[$index]['content'] = json_encode($tableData);
    }

    public function changeSeparatorType($blockId, $newtype)
    {
        $index = collect($this->blocks)->search(fn($b) => $b['id'] === $blockId);
        $this->blocks[$index]['sep_type'] = $newtype;
    }
};
?>

@php
    $typeConfig = [
        'header'      => ['label'=>'H1',       'accent'=>'#6366f1', 'icon'=>'T'],
        'description' => ['label'=>'Text',      'accent'=>'#94a3b8', 'icon'=>'¶'],
        'note'        => ['label'=>'Note',      'accent'=>'#f59e0b', 'icon'=>'!'],
        'code'        => ['label'=>'Code',      'accent'=>'#10b981', 'icon'=>'</>'],
        'exercise'    => ['label'=>'Exercise',  'accent'=>'#f43f5e', 'icon'=>'?'],
        'photo'       => ['label'=>'Photo',     'accent'=>'#8b5cf6', 'icon'=>'🖼'],
        'video'       => ['label'=>'Video',     'accent'=>'#8b5cf6', 'icon'=>'▶'],
        'math'        => ['label'=>'Math',      'accent'=>'#e11d48', 'icon'=>'∑'],
        'graph'       => ['label'=>'Graph',     'accent'=>'#059669', 'icon'=>'📈'],
        'table'       => ['label'=>'Table',     'accent'=>'#d97706', 'icon'=>'⊞'],
        'function'    => ['label'=>'Function',  'accent'=>'#4f46e5', 'icon'=>'f(x)'],
        'list'        => ['label'=>'List',      'accent'=>'#0891b2', 'icon'=>'≡'],
        'separator'   => ['label'=>'Separator', 'accent'=>'#64748b', 'icon'=>'—'],
        'ext'         => ['label'=>'HTML',      'accent'=>'#6366f1', 'icon'=>'<>'],
    ];
@endphp

<div class="blocks-editor">

    @if(!$lesson)
        {{-- Empty state: no lesson selected --}}
        <div class="be-empty-state">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                <path d="M9 12h6M9 16h6M9 8h6M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/>
            </svg>
            <p>Select a lesson from the sidebar to start editing</p>
        </div>
    @else

        {{-- ── Lesson breadcrumb ── --}}
        <div class="be-breadcrumb">
            <span class="be-bc-course">{{ $course->title }}</span>
            <span class="be-bc-sep">›</span>
            <span class="be-bc-chapter">{{ $chapter->title }}</span>
            <span class="be-bc-sep">›</span>
            <span class="be-bc-lesson">{{ $lesson->title }}</span>
            <span class="be-bc-status {{ $lesson->status }}">{{ ucfirst($lesson->status) }}</span>
        </div>

        {{-- ── Blocks list ── --}}
        <div class="be-blocks">

            @forelse($blocks as $block)
                @php $cfg = $typeConfig[$block['type']] ?? ['label'=>$block['type'],'accent'=>'#94a3b8','icon'=>'·']; @endphp

                <div class="be-block type-{{ $block['type'] }}" wire:key="block-{{ $block['id'] }}">

                    {{-- hidden fields --}}
                    <input type="hidden" wire:model="blocks.{{ $loop->index }}.id">
                    <input type="hidden" wire:model="blocks.{{ $loop->index }}.block_number">

                    {{-- Left accent + type badge --}}
                    <div class="be-block-side" style="border-color:{{ $cfg['accent'] }}">
                        <span class="be-type-badge" style="color:{{ $cfg['accent'] }};border-color:{{ $cfg['accent'] }}20;background:{{ $cfg['accent'] }}10;">
                            {{ $cfg['label'] }}
                        </span>
                    </div>

                    {{-- Content area --}}
                    <div class="be-block-body">
                        @switch($block['type'])

                            @case('header')
                                <textarea
                                    class="be-input be-input-title"
                                    placeholder="Enter heading..."
                                    wire:model="blocks.{{ $loop->index }}.content"
                                    oninput="this.style.height='';this.style.height=this.scrollHeight+'px'"
                                ></textarea>
                                @break

                            @case('description')
                                <textarea
                                    class="be-input be-input-body"
                                    placeholder="Write your content here..."
                                    wire:model="blocks.{{ $loop->index }}.content"
                                    oninput="this.style.height='';this.style.height=this.scrollHeight+'px'"
                                    rows="3"
                                ></textarea>
                                @break

                            @case('note')
                                <div class="be-note-wrap">
                                    <div class="be-note-label">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                                        Note
                                    </div>
                                    <textarea
                                        class="be-input be-input-body"
                                        placeholder="Add a note..."
                                        wire:model="blocks.{{ $loop->index }}.content"
                                        oninput="this.style.height='';this.style.height=this.scrollHeight+'px'"
                                        rows="2"
                                    ></textarea>
                                </div>
                                @break

                            @case('code')
                                <div class="be-code-wrap">
                                    <div class="be-code-header">
                                        <span class="be-code-dots">
                                            <span></span><span></span><span></span>
                                        </span>
                                        <span class="be-code-lang">Code</span>
                                    </div>
                                    <textarea
                                        class="be-input be-input-code"
                                        placeholder="// Paste your code here..."
                                        wire:model="blocks.{{ $loop->index }}.content"
                                        oninput="this.style.height='';this.style.height=this.scrollHeight+'px'"
                                        rows="4"
                                    ></textarea>
                                </div>
                                @break

                            @case('exercise')
                                <div class="be-exercise-wrap">
                                    <div class="be-exercise-label">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                                        Question
                                    </div>
                                    <textarea
                                        class="be-input be-input-body"
                                        placeholder="Enter the question..."
                                        wire:model="blocks.{{ $loop->index }}.content"
                                        oninput="this.style.height='';this.style.height=this.scrollHeight+'px'"
                                        rows="2"
                                    ></textarea>
                                    @foreach($block['solutions'] ?? [] as $sIndex => $solution)
                                        <div class="be-solution-wrap">
                                            <div class="be-solution-label">Solution {{ $sIndex + 1 }}</div>
                                            <textarea
                                                class="be-input be-input-body"
                                                placeholder="Enter solution..."
                                                wire:model="blocks.{{ $loop->index }}.solutions.{{ $sIndex }}.content"
                                                name="blocks[{{ $block['id'] }}][solutions][{{ $solution['id'] }}]"
                                                oninput="this.style.height='';this.style.height=this.scrollHeight+'px'"
                                                rows="2"
                                            ></textarea>
                                        </div>
                                    @endforeach
                                </div>
                                @break

                            @case('photo')
                                <div class="be-media-wrap">
                                    @if(!empty($block['content']) && \Storage::disk('public')->exists($block['content']))
                                        <div class="be-media-preview" wire:ignore>
                                            <img src="{{ asset('storage/' . $block['content']) }}"
                                                 onclick="window.open(this.src)"
                                                 style="max-height:180px;border-radius:6px;cursor:pointer;display:block;">
                                            <span class="be-media-filename">{{ basename($block['content']) }}</span>
                                        </div>
                                    @endif
                                    <label class="be-upload-label">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
                                        {{ empty($block['content']) ? 'Upload image' : 'Replace image' }}
                                        <input type="file" accept="image/*" wire:model="photos.{{ $block['id'] }}" style="display:none;">
                                    </label>
                                    <input type="hidden" wire:model="blocks.{{ $loop->index }}.content">
                                    <div wire:loading wire:target="photos.{{ $block['id'] }}" class="be-uploading">
                                        <div class="be-spinner"></div> Processing image...
                                    </div>
                                </div>
                                @break

                            @case('video')
                                <div class="be-media-wrap">
                                    @if(!empty($block['content']) && \Storage::disk('public')->exists($block['content']))
                                        <div class="be-media-preview" wire:ignore>
                                            <video src="{{ asset('storage/' . $block['content']) }}"
                                                   style="max-height:180px;border-radius:6px;display:block;"
                                                   controls></video>
                                            <span class="be-media-filename">{{ basename($block['content']) }}</span>
                                        </div>
                                    @endif
                                    <label class="be-upload-label">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
                                        {{ empty($block['content']) ? 'Upload video' : 'Replace video' }}
                                        <input type="file" accept="video/*" wire:model="videos.{{ $block['id'] }}" style="display:none;">
                                    </label>
                                    <input type="hidden" wire:model="blocks.{{ $loop->index }}.content">
                                    <div wire:loading wire:target="videos.{{ $block['id'] }}" class="be-uploading">
                                        <div class="be-spinner"></div> Uploading video...
                                    </div>
                                </div>
                                @break

                            @case('math')
                                <textarea
                                    class="be-input be-input-mono"
                                    placeholder="Enter LaTeX (e.g., x^2 + y^2 = z^2)"
                                    wire:model.live.debounce.300ms="blocks.{{ $loop->index }}.content"
                                    rows="2"
                                ></textarea>
                                @if(!empty($block['content']))
                                    <div class="be-math-preview">$${{ $block['content'] }}$$</div>
                                @endif
                                @break

                            @case('graph')
                                @php
                                    $graphData = json_decode($block['content'], true) ?? ['type'=>'line','labels'=>['Jan','Feb','Mar'],'data'=>[10,20,15]];
                                    $this->blocks[$loop->index]['graph_type']   ??= $graphData['type']   ?? 'line';
                                    $this->blocks[$loop->index]['graph_labels'] ??= implode(',', $graphData['labels'] ?? []);
                                    $this->blocks[$loop->index]['graph_data']   ??= implode(',', $graphData['data']   ?? []);
                                @endphp
                                <div class="be-field-grid">
                                    <div class="be-field">
                                        <label class="be-field-label">Chart type</label>
                                        <select class="be-select" wire:model="blocks.{{ $loop->index }}.graph_type">
                                            <option value="line">Line</option>
                                            <option value="bar">Bar</option>
                                            <option value="pie">Pie</option>
                                        </select>
                                    </div>
                                    <div class="be-field" style="grid-column:span 2;">
                                        <label class="be-field-label">Labels (comma separated)</label>
                                        <textarea class="be-input be-input-mono" rows="1" wire:model="blocks.{{ $loop->index }}.graph_labels" placeholder="Jan, Feb, Mar"></textarea>
                                    </div>
                                    <div class="be-field" style="grid-column:span 2;">
                                        <label class="be-field-label">Values (comma separated)</label>
                                        <textarea class="be-input be-input-mono" rows="1" wire:model="blocks.{{ $loop->index }}.graph_data" placeholder="10, 20, 15"></textarea>
                                    </div>
                                </div>
                                @break

                            @case('table')
                                @php
                                    $tableData  = json_decode($block['content'], true) ?? [['Header 1','Header 2'],['','']];
                                    $blockIndex = collect($blocks)->search(fn($b) => $b['id'] === $block['id']);
                                @endphp
                                <div class="be-table-wrap">
                                    <div class="be-table-actions">
                                        <button type="button" class="be-btn-sm" wire:click="addTableRow({{ $block['id'] }})">+ Row</button>
                                        <button type="button" class="be-btn-sm" wire:click="addTableCol({{ $block['id'] }})">+ Column</button>
                                    </div>
                                    <div style="overflow-x:auto;">
                                        <table class="be-table">
                                            @foreach($tableData as $rowIndex => $row)
                                                <tr>
                                                    @foreach($row as $colIndex => $cell)
                                                        <td class="{{ $rowIndex === 0 ? 'be-th' : '' }}">
                                                            <input
                                                                type="text"
                                                                value="{{ $cell }}"
                                                                class="be-table-cell"
                                                                wire:change="updateTableCell({{ $block['id'] }},{{ $rowIndex }},{{ $colIndex }},$event.target.value)"
                                                            >
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                </div>
                                @break

                            @case('function')
                                @php
                                    $funcData = json_decode($block['content'], true) ?? [
                                    'function' => 'y = sin(x)',
                                    'x_min'    => -10,
                                    'x_max'    => 10,
                                    'y_min'    => -6,
                                    'y_max'    => 6,
                                    'color'    => '#4f46e5',
                                    'step'     => 0.05,
                                    ];
                                @endphp

                                <div class="function-editor" data-block-id="{{ $block['id'] }}">

                                    {{-- Row 1: equation input --}}
                                    <div style="margin-bottom:8px;">
                                        <label
                                            style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">
                                            Equation (any form: <code>y=sin(x)</code>, <code>x^2+y^2=1</code>,
                                            <code>y-x^2=0</code> …)
                                        </label>
                                        <input type="text"
                                               name="blocks[{{ $block['id'] }}][func_expression]"
                                               wire:model="blocks.{{ $loop->index }}.func_expression"
                                               class="input-ghost func-eq-input"
                                               style="width:100%;font-family:'JetBrains Mono',monospace;font-size:13px;padding:6px 10px;letter-spacing:.02em;"
                                               placeholder="e.g.  y = x^2   or   x^2 + y^2 = 25   or   sin(y) = cos(x)">
                                    </div>

                                    {{-- Row 2: ranges + color --}}
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
                                        <div style="flex:1;min-width:70px;">
                                            <label
                                                style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">X
                                                min</label>
                                            <input type="number" name="blocks[{{ $block['id'] }}][x_min]"
                                                   wire:model="blocks.{{ $loop->index }}.x_min"
                                                   class="input-ghost" style="width:100%;padding:5px 8px;" step="any">
                                        </div>
                                        <div style="flex:1;min-width:70px;">
                                            <label
                                                style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">X
                                                max</label>
                                            <input type="number" name="blocks[{{ $block['id'] }}][x_max]"
                                                   wire:model="blocks.{{ $loop->index }}.x_max"
                                                   class="input-ghost" style="width:100%;padding:5px 8px;" step="any">
                                        </div>
                                        <div style="flex:1;min-width:70px;">
                                            <label
                                                style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Y
                                                min</label>
                                            <input type="number" name="blocks[{{ $block['id'] }}][y_min]"
                                                   wire:model="blocks.{{ $loop->index }}.y_min"
                                                   class="input-ghost" style="width:100%;padding:5px 8px;" step="any">
                                        </div>
                                        <div style="flex:1;min-width:70px;">
                                            <label
                                                style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Y
                                                max</label>
                                            <input type="number" name="blocks[{{ $block['id'] }}][y_max]"
                                                   wire:model="blocks.{{ $loop->index }}.y_max"
                                                   class="input-ghost" style="width:100%;padding:5px 8px;" step="any">
                                        </div>
                                        <div style="flex:0 0 auto;">
                                            <label
                                                style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Color</label>
                                            <input type="color" name="blocks[{{ $block['id'] }}][color]"
                                                   wire:model="blocks.{{ $loop->index }}.color"
                                                   style="width:48px;height:32px;border:none;cursor:pointer;border-radius:4px;">
                                        </div>
                                        <div style="flex:1;min-width:70px;">
                                            <label
                                                style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Resolution</label>
                                            <input type="number" name="blocks[{{ $block['id'] }}][step]"
                                                   wire:model="blocks.{{ $loop->index }}.step"
                                                   class="input-ghost" style="width:100%;padding:5px 8px;"
                                                   step="0.005" min="0.005" max="0.5">
                                        </div>
                                    </div>

                                    {{-- Canvas preview --}}
                                    <div class="function-preview" wire:ignore
                                         style="position:relative;margin-top:10px;padding:10px;background:var(--bg-subtle);border-radius:8px;border:1px solid var(--border);">
                                        <canvas id="func-canvas-{{ $block['id'] }}"
                                                style="width:100%;height:auto;display:block;border-radius:4px;background:var(--bg);">
                                        </canvas>
                                        <div id="func-error-{{ $block['id'] }}"
                                             style="display:none;position:absolute;bottom:14px;left:14px;font-size:11px;
color:#ef4444;background:rgba(0,0,0,.6);padding:3px 8px;border-radius:4px;"></div>
                                    </div>

                                    <small style="color:var(--text-faint);font-size:11px;display:block;margin-top:5px;">
                                        Supports: + − * / ^ sin cos tan asin acos atan sqrt abs log ln exp pi e — both x and
                                        y variables.
                                    </small>
                                </div>

                                {{-- Hidden input keeps JSON for blockcontroller —
                                  its name must NOT conflict with func_expression / x_min etc.
                                  The JS below writes to it on every change.                  --}}
                                <input type="hidden"
                                       name="blocks[{{ $block['id'] }}][content]"
                                       class="function-content-hidden"
                                       value="{{ $block['content'] }}">
                                @break

                            @case('list')
                                @php $listData = json_decode($block['content'], true) ?? ['style'=>'bullet','items'=>[]]; @endphp
                                <div class="be-field">
                                    <label class="be-field-label">List style</label>
                                    <select class="be-select" style="width:auto;" wire:model="blocks.{{ $loop->index }}.list_style">
                                        <option value="bullet">• Bullet</option>
                                        <option value="numbered">1. Numbered</option>
                                        <option value="checklist">☑ Checklist</option>
                                    </select>
                                </div>
                                <textarea
                                    class="be-input be-input-body"
                                    wire:model="blocks.{{ $loop->index }}.content"
                                    placeholder="One item per line..."
                                    rows="{{ max(3, count($listData['items'] ?? []) + 1) }}"
                                    style="margin-top:8px;"
                                ></textarea>
                                <span class="be-hint">One item per line</span>
                                @break

                            @case('separator')
                                <div class="be-separator-wrap">
                                    <select class="be-select" style="width:auto;" wire:change="changeSeparatorType({{ $block['id'] }}, $event.target.value)">
                                        <option value="divider"       {{ ($block['sep_type'] ?? 'divider') == 'divider'       ? 'selected' : '' }}>— Horizontal line</option>
                                        <option value="section_break" {{ ($block['sep_type'] ?? '') == 'section_break' ? 'selected' : '' }}>§ Section break</option>
                                        <option value="page_break"    {{ ($block['sep_type'] ?? '') == 'page_break'    ? 'selected' : '' }}>↲ Page break</option>
                                    </select>
                                    <div class="be-sep-preview">
                                        @if(($block['sep_type'] ?? 'divider') == 'page_break')
                                            <div class="be-sep-page">——— Page Break ———</div>
                                        @elseif(($block['sep_type'] ?? '') == 'section_break')
                                            <div class="be-sep-section">
                                                <span></span><small>Section</small><span></span>
                                            </div>
                                        @else
                                            <hr class="be-sep-hr">
                                        @endif
                                    </div>
                                </div>
                                <input type="hidden" wire:model="blocks.{{ $loop->index }}.content">
                                @break

                            @case('ext')
                                <div class="be-ext-warn">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                    Raw HTML — use with caution
                                </div>
                                <textarea
                                    class="be-input be-input-code be-input-dark"
                                    placeholder="Paste HTML, iframe, or script..."
                                    wire:model="blocks.{{ $loop->index }}.content"
                                    rows="4"
                                ></textarea>
                                @break

                            @default
                                <textarea class="be-input be-input-body" rows="2" wire:model="blocks.{{ $loop->index }}.content"></textarea>
                        @endswitch
                    </div>

                    {{-- Controls --}}
                    <div class="be-block-controls">
                        <select
                            class="be-type-select"
                            wire:change="updateBlockType({{ $loop->index }}, $event.target.value)"
                            title="Change block type"
                        >
                            @foreach($typeConfig as $val => $tc)
                                <option value="{{ $val }}" {{ $block['type'] == $val ? 'selected' : '' }}>{{ $tc['label'] }}</option>
                            @endforeach
                        </select>
                        <div class="be-ctrl-divider"></div>
                        <button type="button" class="be-ctrl-btn" wire:click="moveBlock({{ $loop->index }}, 'up')" title="Move up">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
                        </button>
                        <button type="button" class="be-ctrl-btn" wire:click="moveBlock({{ $loop->index }}, 'down')" title="Move down">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="be-ctrl-divider"></div>
                        <button type="button" class="be-ctrl-btn be-ctrl-delete" wire:click="deleteBlock({{ $loop->index }})" title="Delete block" wire:confirm="Delete this block?">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                        </button>
                    </div>

                </div>
            @empty
                <div class="be-empty">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="opacity:.25;margin-bottom:10px;">
                        <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 8v8M8 12h8"/>
                    </svg>
                    <p>No blocks yet — use the <strong>+</strong> button below to add content.</p>
                </div>
            @endforelse

        </div>

        {{-- ── Save bar ── --}}
        <div class="be-save-bar">
            <livewire:modular_site.block.blockcreate :lesson="$lesson"/>

            <button
                type="button"
                wire:click="saveAll"
                wire:loading.attr="disabled"
                x-data="{ status: 'idle' }"
                x-on:notify.window="if($event.detail.message==='Saved!'){status='saved';setTimeout(()=>status='idle',2500)}"
                class="be-save-btn"
            >
                <span wire:loading.remove wire:target="saveAll" x-show="status==='idle'">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Save all
                </span>
                <span wire:loading wire:target="saveAll">
                    <svg class="be-spin" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>
                    Saving...
                </span>
                <span x-show="status==='saved'" x-cloak style="color:#10b981;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Saved
                </span>
            </button>
        </div>

    @endif
</div>

<style>
    /* ════════════════════════════════
       BLOCKS EDITOR
    ════════════════════════════════ */
    .blocks-editor {
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 0;
    }

    /* ── Empty / no-lesson state ── */
    .be-empty-state {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        color: var(--text-faint);
        font-size: 13px;
        padding: 60px 20px;
        text-align: center;
    }

    /* ── Breadcrumb ── */
    .be-breadcrumb {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
        flex-wrap: wrap;
        background: var(--bg);
        position: sticky;
        top: 0;
        z-index: 5;
    }
    .be-bc-course  { font-size: 12px; color: var(--text-muted); }
    .be-bc-chapter { font-size: 12px; color: var(--text-muted); }
    .be-bc-lesson  { font-size: 12px; font-weight: 600; color: var(--text); }
    .be-bc-sep     { font-size: 12px; color: var(--text-faint); }
    .be-bc-status  { font-size: 10px; font-weight: 600; padding: 2px 7px; border-radius: 20px; margin-left: 4px; }
    .be-bc-status.published { background: #d1fae5; color: #065f46; }
    .be-bc-status.draft     { background: #f3f4f6; color: #6b7280; }
    [data-theme="dark"] .be-bc-status.published { background: #064e3b; color: #6ee7b7; }
    [data-theme="dark"] .be-bc-status.draft     { background: #2a2a2a; color: #9ca3af; }

    /* ── Blocks list ── */
    .be-blocks {
        flex: 1;
        overflow-y: auto;
        padding: 16px 20px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    /* ── Single block ── */
    .be-block {
        display: flex;
        align-items: stretch;
        gap: 0;
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 10px;
        overflow: hidden;
        transition: border-color .15s, box-shadow .15s;
    }

    .be-block:hover {
        border-color: var(--accent);
        box-shadow: 0 2px 12px rgba(79,70,229,.07);
    }

    /* Left accent strip + badge */
    .be-block-side {
        width: 36px;
        flex-shrink: 0;
        border-right: 3px solid;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        padding-top: 10px;
    }

    .be-type-badge {
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        writing-mode: vertical-rl;
        text-orientation: mixed;
        transform: rotate(180deg);
        padding: 6px 0;
        border-radius: 3px;
        border: 1px solid;
    }

    /* Main body */
    .be-block-body {
        flex: 1;
        padding: 12px 14px;
        min-width: 0;
    }

    /* Controls panel */
    .be-block-controls {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        padding: 8px 6px;
        border-left: 1px solid var(--border-mid);
        flex-shrink: 0;
        background: var(--bg-subtle);
    }

    .be-type-select {
        font-size: 10px;
        padding: 3px 4px;
        border: 1px solid var(--border);
        border-radius: 5px;
        background: var(--bg);
        color: var(--text-muted);
        cursor: pointer;
        font-family: inherit;
        width: 52px;
    }

    .be-ctrl-divider {
        width: 20px;
        height: 1px;
        background: var(--border-mid);
        margin: 2px 0;
    }

    .be-ctrl-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        border: none;
        background: none;
        border-radius: 5px;
        cursor: pointer;
        color: var(--text-faint);
        transition: background .12s, color .12s;
    }
    .be-ctrl-btn:hover        { background: var(--bg-hover); color: var(--text); }
    .be-ctrl-delete:hover     { background: #fff5f5; color: #ef4444; }
    [data-theme="dark"] .be-ctrl-delete:hover { background: #2a0f0f; color: #f87171; }

    /* ── Inputs ── */
    .be-input {
        display: block;
        width: 100%;
        background: transparent;
        border: none;
        outline: none;
        resize: none;
        font-family: inherit;
        color: var(--text);
        line-height: 1.6;
        transition: background .12s;
        box-sizing: border-box;
    }
    .be-input:focus { background: var(--bg-subtle); border-radius: 4px; }
    .be-input::placeholder { color: var(--text-faint); }

    .be-input-title { font-size: 20px; font-weight: 700; letter-spacing: -.02em; min-height: 36px; }
    .be-input-body  { font-size: 13.5px; min-height: 48px; }
    .be-input-mono  { font-family: 'JetBrains Mono', 'Fira Code', monospace; font-size: 13px; }
    .be-input-code  { font-family: 'JetBrains Mono', 'Fira Code', monospace; font-size: 12.5px; line-height: 1.7; }
    .be-input-dark  { background: #0d1117; color: #e2e8f0; border-radius: 6px; padding: 10px; }
    .be-input-sm    { font-size: 12px; padding: 4px 6px; }

    /* ── Note block ── */
    .be-note-wrap {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 7px;
        padding: 10px 12px;
    }
    [data-theme="dark"] .be-note-wrap { background: #1f1a0f; border-color: #78350f; }
    .be-note-label {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #92400e;
        margin-bottom: 6px;
    }
    [data-theme="dark"] .be-note-label { color: #fcd34d; }

    /* ── Code block ── */
    .be-code-wrap {
        background: #0d1117;
        border-radius: 8px;
        overflow: hidden;
    }
    .be-code-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 7px 12px;
        background: #161b22;
        border-bottom: 1px solid #30363d;
    }
    .be-code-dots { display:flex;gap:5px; }
    .be-code-dots span { width:10px;height:10px;border-radius:50%;background:#30363d; }
    .be-code-dots span:nth-child(1) { background:#ff5f57; }
    .be-code-dots span:nth-child(2) { background:#febc2e; }
    .be-code-dots span:nth-child(3) { background:#28c840; }
    .be-code-lang { font-size:10px;color:#8b949e;font-family:'JetBrains Mono',monospace;margin-left:auto; }
    .be-code-wrap .be-input { color:#e2e8f0;padding:12px; }

    /* ── Exercise ── */
    .be-exercise-wrap { display:flex;flex-direction:column;gap:8px; }
    .be-exercise-label, .be-solution-label {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--text-faint);
        margin-bottom: 2px;
    }
    .be-solution-wrap {
        padding: 8px 10px;
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: 6px;
        border-left: 3px solid #10b981;
    }

    /* ── Media ── */
    .be-media-wrap { display:flex;flex-direction:column;gap:8px; }
    .be-media-preview { display:flex;flex-direction:column;gap:4px; }
    .be-media-filename { font-size:11px;color:var(--text-faint); }
    .be-upload-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border: 1.5px dashed var(--border);
        border-radius: 7px;
        font-size: 12px;
        color: var(--text-muted);
        cursor: pointer;
        transition: border-color .15s, background .15s;
        font-weight: 500;
    }
    .be-upload-label:hover { border-color: var(--accent); color: var(--accent); background: var(--bg-hover); }
    .be-uploading { display:flex;align-items:center;gap:6px;font-size:12px;color:var(--accent); }
    .be-spinner {
        width: 12px; height: 12px;
        border: 2px solid var(--border);
        border-top-color: var(--accent);
        border-radius: 50%;
        animation: be-spin .6s linear infinite;
    }
    @keyframes be-spin { to { transform: rotate(360deg); } }

    /* ── Math ── */
    .be-math-preview {
        margin-top: 8px;
        padding: 10px 12px;
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: 6px;
        font-family: 'Times New Roman', serif;
        font-size: 16px;
        min-height: 40px;
    }

    /* ── Field grid ── */
    .be-field { display:flex;flex-direction:column;gap:3px; }
    .be-field-label { font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text-faint); }
    .be-field-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:8px; }
    .be-field-grid-6 { grid-template-columns:repeat(6,1fr); }
    .be-select {
        padding: 5px 8px;
        border: 1px solid var(--border);
        border-radius: 6px;
        background: var(--bg-subtle);
        color: var(--text);
        font-size: 12px;
        cursor: pointer;
        font-family: inherit;
    }
    .be-color-input {
        width: 100%;
        height: 32px;
        border: 1px solid var(--border);
        border-radius: 5px;
        cursor: pointer;
        padding: 2px;
    }

    /* ── Table ── */
    .be-table-wrap { overflow-x:auto; }
    .be-table-actions { display:flex;gap:6px;margin-bottom:8px; }
    .be-btn-sm {
        padding: 4px 10px;
        border: 1px solid var(--border);
        border-radius: 5px;
        background: none;
        font-size: 11px;
        color: var(--text-muted);
        cursor: pointer;
        font-family: inherit;
        transition: background .12s;
    }
    .be-btn-sm:hover { background: var(--bg-hover); color: var(--text); }
    .be-table { width:100%;border-collapse:collapse;font-size:13px; }
    .be-table td { border:1px solid var(--border);padding:0;min-width:80px; }
    .be-th { background:var(--bg-subtle); }
    .be-table-cell {
        width: 100%;
        border: none;
        background: transparent;
        padding: 7px 9px;
        font-family: inherit;
        font-size: 13px;
        color: var(--text);
        outline: none;
    }
    .be-table-cell:focus { background: var(--bg-hover); }

    /* ── Function ── */
    .be-function-wrap { display:flex;flex-direction:column;gap:6px; }
    .be-canvas-wrap {
        position: relative;
        margin-top: 10px;
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 10px;
    }

    /* ── Separator ── */
    .be-separator-wrap { display:flex;flex-direction:column;gap:10px; }
    .be-sep-preview { margin-top:4px; }
    .be-sep-hr { border:none;border-top:1px solid var(--border);margin:4px 0; }
    .be-sep-page {
        border: 2px dashed var(--border);
        padding: 8px 12px;
        text-align: center;
        color: var(--text-faint);
        font-size: 12px;
        border-radius: 6px;
        background: var(--bg);
    }
    .be-sep-section {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--text-faint);
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .1em;
    }
    .be-sep-section span { flex:1;height:1px;background:var(--border); }

    /* ── Ext warn ── */
    .be-ext-warn {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        color: #f59e0b;
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 6px;
        padding: 5px 10px;
        margin-bottom: 8px;
        font-weight: 500;
    }
    [data-theme="dark"] .be-ext-warn { background: #1f1a0f; border-color: #78350f; color: #fcd34d; }

    /* ── Hint ── */
    .be-hint { font-size:11px;color:var(--text-faint);margin-top:4px;display:block; }

    /* ── Empty state ── */
    .be-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 48px 20px;
        color: var(--text-faint);
        font-size: 13px;
        text-align: center;
        border: 1.5px dashed var(--border);
        border-radius: 10px;
    }

    /* ── Save bar ── */
    .be-save-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 20px;
        border-top: 1px solid var(--border);
        background: var(--bg);
        flex-shrink: 0;
        gap: 10px;
        position: sticky;
        bottom: 0;
        z-index: 5;
    }

    .be-save-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 20px;
        background: var(--accent);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
        transition: background .15s;
        flex-shrink: 0;
    }
    .be-save-btn:hover    { background: var(--accent-hover); }
    .be-save-btn:disabled { opacity: .6; cursor: not-allowed; }

    .be-spin {
        animation: be-spin .7s linear infinite;
    }
</style>
