<?php

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
        'BlockCreated' => 'addBlock',
        'triggerSaveAll' => 'saveAll',
        'ScrollToBlock' => 'scrollToBlock'];

    public function mount($course, $chapter, $lesson, $blocks)
    {
        $this->course = $course;
        $this->chapter = $chapter;
        $this->lesson = $lesson;
        $this->blocks = $blocks->map(function ($block) {
            $arr = $block->toArray();
            if ($block->type === 'exercise') {
                $arr['solutions'] = $block->solutions->map(fn($s) => $s->toArray())->toArray();
            }
            $this->hydrateBlockFields($arr);
            return $arr;
        })->toArray();
    }

    public function addBlock($id)
    {
        if (!$id) return;
        $block = block::with('solutions')->findOrFail($id);
        if ($block) {
            $arr = $block->toArray();
            if ($block->type === 'exercise') {
                $arr['solutions'] = $block->solutions->map(fn($s) => $s->toArray())->toArray();
            }
            // BUG FIX #2: hydrate fields so function/graph/list/separator show correct inputs
            $this->hydrateBlockFields($arr);
            $this->blocks[] = $arr;
            $this->dispatch('scrollToNewBlock', blockId: $id);
        }
    }

    public function scrollToBlock(int $blockId): void
    {
        $this->dispatch('scrollToNewBlock', blockId: $blockId);
    }

    public function updateLesson($id, $chapterId): void
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

        $this->lesson  = lesson::findOrFail($id);
        $this->lesson->refresh();
        $this->chapter = $this->lesson->chapter;
        $this->chapter->refresh();
    }

    public function updatedBlocks($value, $key)
    {
        if (str_contains($key, 'func_expression') ||
            str_contains($key, 'x_min') ||
            str_contains($key, 'x_max') ||
            str_contains($key, 'y_min') ||
            str_contains($key, 'y_max') ||
            str_contains($key, 'color') ||
            str_contains($key, 'step')) {

            $index = explode('.', $key)[0];
            $this->blocks[$index]['content'] = json_encode([
                'function' => $this->blocks[$index]['func_expression'] ?? 'sin(x)',
                'x_min' => $this->blocks[$index]['x_min'] ?? -10,
                'x_max' => $this->blocks[$index]['x_max'] ?? 10,
                'y_min' => $this->blocks[$index]['y_min'] ?? -5,
                'y_max' => $this->blocks[$index]['y_max'] ?? 5,
                'color' => $this->blocks[$index]['color'] ?? '#4f46e5',
                'step' => $this->blocks[$index]['step'] ?? 0.1,
            ]);
        }
    }

    public function saveAll()
    {
        foreach ($this->blocks as $blockData) {
            $content = $blockData['content'] ?? null;

            if ($blockData['type'] === 'graph') {
                $content = json_encode([
                    'type'   => $blockData['graph_type']   ?? 'line',
                    'labels' => array_values(array_filter(array_map('trim', explode(',', $blockData['graph_labels'] ?? '')))),
                    'data'   => array_values(array_filter(array_map('trim', explode(',', $blockData['graph_data']   ?? '')))),
                ]);
            }
            if ($blockData['type'] === 'list') {
                $items = array_values(array_filter(array_map('trim', explode("
", $blockData['list_items'] ?? ''))));
                $content = json_encode([
                    'style' => $blockData['list_style'] ?? 'bullet',
                    'items' => $items,
                ]);
            }
            if ($blockData['type'] === 'separator') {
                $content = json_encode(['type' => $blockData['separator_type'] ?? 'divider']);
            }
            if ($blockData['type'] === 'table') {
                $content = $blockData['table_json'] ?? $blockData['content'];
            }
            if ($blockData['type'] === 'function') {
                $content = json_encode([
                    'function' => $blockData['func_expression'] ?? 'sin(x)',
                    'x_min' => $blockData['x_min'] ?? -10,
                    'x_max' => $blockData['x_max'] ?? 10,
                    'y_min' => $blockData['y_min'] ?? -5,
                    'y_max' => $blockData['y_max'] ?? 5,
                    'color' => $blockData['color'] ?? '#4f46e5',
                    'step' => $blockData['step'] ?? 0.1,
                ]);
            }

            block::where('id', $blockData['id'])->update([
                'content' => $content,
                'type' => $blockData['type'],
                'block_number' => $blockData['block_number'],
            ]);

            if ($blockData['type'] === 'exercise') {
                foreach ($blockData['solutions'] ?? [] as $solution) {
                    exercisesolution::where('id', $solution['id'])
                        ->update(['content' => $solution['content']]);
                }
            }
            if (in_array($blockData['type'], ['photo', 'video'])) {
                $blockData['file_name'] = $blockData['content'] ? basename($blockData['content']) : 'No file selected';
            }
        }

        $this->dispatch('notify', message: 'Saved!');
    }

    private function hydrateBlockFields(&$block): void
    {
        if ($block['type'] === 'function') {
            $data = json_decode($block['content'], true) ?? [];
            $block['func_expression'] = $data['function'] ?? 'sin(x)';
            $block['x_min']  = $data['x_min']  ?? -10;
            $block['x_max']  = $data['x_max']  ??  10;
            $block['y_min']  = $data['y_min']  ??  -5;
            $block['y_max']  = $data['y_max']  ??   5;
            $block['color']  = $data['color']  ?? '#4f46e5';
            $block['step']   = $data['step']   ??  0.1;
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
            $block['list_items'] = implode("
", $data['items'] ?? []);
        }
        if ($block['type'] === 'separator') {
            $data = json_decode($block['content'], true) ?? [];
            $block['separator_type'] = $data['type'] ?? 'divider';
        }
    }

    public function deleteBlock(int $blockId): void
    {
        $index = collect($this->blocks)->search(fn($b) => $b['id'] === $blockId);
        if ($index === false) return;
        block::destroy($blockId);
        array_splice($this->blocks, $index, 1);
        foreach ($this->blocks as $i => &$b) {
            $b['block_number'] = $i + 1;
            block::where('id', $b['id'])->update(['block_number' => $i + 1]);
        }
        unset($b);
        $this->dispatch('notify', message: 'Block deleted.');
    }

    public function moveBlock(int $blockId, string $direction): void
    {
        $index = collect($this->blocks)->search(fn($b) => $b['id'] === $blockId);
        if ($index === false) return;

        $swapWith = $direction === 'up' ? $index - 1 : $index + 1;
        if ($swapWith < 0 || $swapWith >= count($this->blocks)) return;

        [$this->blocks[$index], $this->blocks[$swapWith]] =
            [$this->blocks[$swapWith], $this->blocks[$index]];

        foreach ($this->blocks as $i => &$b) {
            $b['block_number'] = $i + 1;
            block::where('id', $b['id'])->update(['block_number' => $i + 1]);
        }
        unset($b);
        $this->dispatch('notify', message: 'Block moved.');
    }

    public function updateBlockType(int $blockId, string $newType): void
    {
        $index = collect($this->blocks)->search(fn($b) => $b['id'] === $blockId);
        if ($index === false) return;
        $this->blocks[$index]['type'] = $newType;
        if ($newType === 'table' && !is_array(json_decode($this->blocks[$index]['content'] ?? '', true))) {
            $this->blocks[$index]['content'] = json_encode([['Header 1', 'Header 2'], ['', '']]);
            block::where('id', $blockId)->update([
                'type'    => $newType,
                'content' => $this->blocks[$index]['content'],
            ]);
            return;
        }
        block::where('id', $blockId)->update(['type' => $newType]);
    }

    public function updatedPhotos($value, $key)
    {
        $path = $this->photos[$key]->store('blocks', 'public');
        foreach ($this->blocks as &$block) {
            if ($block['id'] == $key) {
                $block['content'] = $path;
                break;
            }
        }
    }

    public function updatedVideos($value, $key)
    {
        $path = $this->videos[$key]->store('blocks', 'public');
        foreach ($this->blocks as &$block) {
            if ($block['id'] == $key) {
                $block['content'] = $path;
                break;
            }
        }
    }

    public function addTableRow($blockId)
    {
        $index = collect($this->blocks)->search(fn($b) => $b['id'] === $blockId);
        $tableData = json_decode($this->blocks[$index]['content'], true);
        $cols = count($tableData[0] ?? ['']);

        $tableData[] = array_fill(0, $cols, '');
        $this->blocks[$index]['content'] = json_encode($tableData);
    }

    public function addTableCol($blockId)
    {
        $index = collect($this->blocks)->search(fn($b) => $b['id'] === $blockId);
        $tableData = json_decode($this->blocks[$index]['content'], true);
        foreach ($tableData as &$row) {
            $row[] = '';
        }
        $this->blocks[$index]['content'] = json_encode($tableData);
    }

    public function updateTableCell($blockId, $rowIndex, $colIndex, $value)
    {
        $index = collect($this->blocks)->search(fn($b) => $b['id'] === $blockId);
        $tableData = json_decode($this->blocks[$index]['content'], true);
        $tableData[$rowIndex][$colIndex] = $value;
        $this->blocks[$index]['content'] = json_encode($tableData);
    }
};
?>

@php
    $typeConfig = [
        'markdown'    => ['label'=>'MD',       'accent'=>'#6366f1'],
        'header'      => ['label'=>'H1',       'accent'=>'#6366f1'],
        'description' => ['label'=>'Text',     'accent'=>'#94a3b8'],
        'note'        => ['label'=>'Note',     'accent'=>'#f59e0b'],
        'code'        => ['label'=>'Code',     'accent'=>'#10b981'],
        'exercise'    => ['label'=>'Exercise', 'accent'=>'#f43f5e'],
        'photo'       => ['label'=>'Photo',    'accent'=>'#8b5cf6'],
        'video'       => ['label'=>'Video',    'accent'=>'#8b5cf6'],
        'math'        => ['label'=>'Math',     'accent'=>'#e11d48'],
        'graph'       => ['label'=>'Graph',    'accent'=>'#059669'],
        'table'       => ['label'=>'Table',    'accent'=>'#d97706'],
        'function'    => ['label'=>'f(x)',     'accent'=>'#4f46e5'],
        'ext'         => ['label'=>'HTML',     'accent'=>'#6366f1'],
    ];
@endphp

<div class="blocks-editor">

    {{-- ── Breadcrumb ── --}}
    <div class="be-breadcrumb">
        <span class="be-bc-dim">{{ $course->title }}</span>
        <span class="be-bc-sep">›</span>
        <span class="be-bc-dim">{{ $chapter->title }}</span>
        <span class="be-bc-sep">›</span>
        <span class="be-bc-active">{{ $lesson->title }}</span>
        <span class="be-bc-status {{ $lesson->status }}">{{ ucfirst($lesson->status) }}</span>
    </div>

    {{-- ── Blocks ── --}}

    <div class="be-blocks">

        @placeholder
        <div class="be-block-skeleton" style="padding: 15px;">

            <div>
                @foreach(range(1, 10) as $i)

                    <div style="width: 100%; height: 80px; background: var(--bg-subtle); border: 1px solid var(--border); border-radius: 6px; position: relative; overflow: hidden;" class="be-block type-header">

                        <div class="be-block-body">
                            <div class="be-block-side" style="position: absolute; top: 12px; left: 12px; width: 60%; height: 10px; background: var(--border-mid); border-radius: 2px; animation: pulse 2s infinite;">
                                <span class="be-type-label" style="position: absolute; top: 12px; left: 12px; width: 60%; height: 10px; background: var(--border-mid); border-radius: 2px; animation: pulse 2s infinite;"></span>
                            </div>
                            <div class="be-input be-input-title" style="position: absolute; top: 12px; left: 12px; width: 60%; height: 10px; background: var(--border-mid); border-radius: 2px; animation: pulse 2s infinite;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endplaceholder
        @forelse($blocks as $block)
            @php $cfg = $typeConfig[$block['type']] ?? ['label'=>$block['type'],'accent'=>'#94a3b8']; @endphp

            <div class="be-block type-{{ $block['type'] }}"
                 data-id="{{ $block['id'] }}"
                 data-block-id="{{ $block['id'] }}"
                 wire:key="block-{{ $block['id'] }}">

                <input type="hidden" wire:model="blocks.{{ $loop->index }}.id">
                <input type="hidden" wire:model="blocks.{{ $loop->index }}.block_number">

                {{-- Left accent + label --}}
                <div class="be-block-side" style="border-color:{{ $cfg['accent'] }}">
                    <span class="be-type-label" style="color:{{ $cfg['accent'] }}">{{ $cfg['label'] }}</span>
                </div>

                {{-- Content --}}
                <div class="be-block-body">


                    @switch($block['type'])

                        @case('markdown')
                            <div class="markdown-block-editor">
                                <div class="mbe-tabs" data-block-id="{{ $block['id'] }}">
                                    <button type="button" class="mbe-tab active" onclick="mbeSetTab({{ $block['id'] }}, 'edit')">✏️ Edit</button>
                                    <button type="button" class="mbe-tab" onclick="mbeSetTab({{ $block['id'] }}, 'preview')">👁 Preview</button>
                                    <button type="button" class="mbe-tab mbe-tab--upgrade" onclick="openConvertPanel({{ $block['id'] }}, {{ json_encode($block['content']) }})">⚡ Convert block</button>
                                    {{-- NEW: Explode markdown into typed blocks --}}
                                    <button
                                        type="button"
                                        class="mbe-tab mbe-tab--explode"
                                        title="Parse this markdown and replace it with typed blocks (header, code, math, list…)"
                                        onclick="explodeMarkdownBlock(
                                            {{ $block['id'] }},
                                            '{{ route('admin.courses.chapters.lessons.blocks.explode-markdown', [$course->id, $chapter->id, $lesson->id, $block['id']]) }}'
                                        )"
                                    >💥 Explode to blocks</button>
                                </div>
                                <div id="mbe-edit-{{ $block['id'] }}" class="mbe-pane mbe-pane--active">
                                    <textarea
                                        class="be-input be-input-body mbe-textarea"
                                        name="blocks[{{ $block['id'] }}][content]"
                                        wire:model="blocks.{{ $loop->index }}.content"
                                        oninput="autoResize(this); mbeUpdatePreview({{ $block['id'] }})"
                                        placeholder="Markdown and LaTeX supported. Use $...$ for inline math, $$...$$ for display math."
                                    ></textarea>
                                    <span class="be-hint">Markdown + LaTeX ($...$, $$...$$) · Images not supported in this block</span>
                                </div>
                                <div id="mbe-preview-{{ $block['id'] }}"
                                     class="mbe-pane mbe-preview-rendered"
                                     style="display:none;padding:12px;border:1px solid var(--border);border-radius:6px;min-height:60px;background:var(--bg-subtle);">
                                    <em style="color:var(--text-faint);font-size:12px;">Click Preview to render.</em>
                                </div>
                            </div>
                            @break

                        @case('header')
                            <div class="be-md-wrap" data-bid="{{ $block['id'] }}">
                                <textarea
                                    class="be-input be-input-title be-md-src"
                                    name="blocks[{{ $block['id'] }}][content]"
                                    placeholder="Enter heading... (markdown supported)"
                                    wire:model="blocks.{{ $loop->index }}.content"
                                    oninput="autoResize(this);beMdLive(this)"
                                ></textarea>
                                <div class="be-md-preview" style="display:none"></div>
                                <button type="button" class="be-md-toggle" onclick="beMdToggle(this)" title="Toggle markdown preview">👁</button>
                            </div>
                            @break

                        @case('description')
                            <div class="be-md-wrap" data-bid="{{ $block['id'] }}">
                                <textarea
                                    class="be-input be-input-body be-md-src"
                                    name="blocks[{{ $block['id'] }}][content]"
                                    placeholder="Write your content here... (markdown supported)"
                                    wire:model="blocks.{{ $loop->index }}.content"
                                    oninput="autoResize(this);beMdLive(this)"
                                    rows="3"
                                ></textarea>
                                <div class="be-md-preview" style="display:none"></div>
                                <button type="button" class="be-md-toggle" onclick="beMdToggle(this)" title="Toggle markdown preview">👁</button>
                            </div>
                            @break

                        @case('note')
                            <div class="be-note-wrap">
                                <div class="be-note-label">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                                    Note
                                </div>
                                <div class="be-md-wrap" data-bid="{{ $block['id'] }}">
                                    <textarea
                                        class="be-input be-input-body be-md-src"
                                        name="blocks[{{ $block['id'] }}][content]"
                                        placeholder="Add a note... (markdown supported)"
                                        wire:model="blocks.{{ $loop->index }}.content"
                                        oninput="autoResize(this);beMdLive(this)"
                                        rows="2"
                                    ></textarea>
                                    <div class="be-md-preview" style="display:none"></div>
                                    <button type="button" class="be-md-toggle" onclick="beMdToggle(this)" title="Toggle markdown preview">👁</button>
                                </div>
                            </div>
                            @break

                        @case('code')
                            <div class="be-code-wrap">
                                <div class="be-code-header">
                                    <span class="be-code-dots"><span></span><span></span><span></span></span>
                                    <span class="be-code-lang">Code</span>
                                </div>
                                <textarea
                                    class="be-input be-input-code be-input-dark"
                                    name="blocks[{{ $block['id'] }}][content]"
                                    placeholder="// Paste your code here..."
                                    wire:model="blocks.{{ $loop->index }}.content"
                                    oninput="autoResize(this)"
                                    rows="4"
                                ></textarea>
                            </div>
                            @break

                        @case('exercise')
                            <div class="be-exercise-wrap">
                                <div class="be-exercise-label">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3M12 17h.01"/></svg>
                                    Question
                                </div>
                                <div class="be-md-wrap" data-bid="{{ $block['id'] }}">
                                    <textarea
                                        class="be-input be-input-body be-md-src"
                                        name="blocks[{{ $block['id'] }}][content]"
                                        placeholder="Enter the question... (markdown supported)"
                                        wire:model="blocks.{{ $loop->index }}.content"
                                        oninput="autoResize(this);beMdLive(this)"
                                        rows="2"
                                    ></textarea>
                                    <div class="be-md-preview" style="display:none"></div>
                                    <button type="button" class="be-md-toggle" onclick="beMdToggle(this)" title="Toggle markdown preview">👁</button>
                                </div>
                                @foreach($block['solutions'] ?? [] as $sIndex => $solution)
                                    <div class="be-solution-wrap">
                                        <div class="be-solution-label">Solution {{ $sIndex + 1 }}</div>
                                        <textarea
                                            class="be-input be-input-body"
                                            name="blocks[{{ $block['id'] }}][solutions][{{ $solution['id'] }}]"
                                            wire:model="blocks.{{ $loop->index }}.solutions.{{ $sIndex }}.content"
                                            oninput="autoResize(this)"
                                            rows="2"
                                            placeholder="Enter solution..."
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
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
                                    {{ empty($block['content']) ? 'Upload image' : 'Replace image' }}
                                    <input type="file" accept="image/*" wire:model="photos.{{ $block['id'] }}" style="display:none;">
                                </label>
                                <input type="hidden" name="blocks[{{ $block['id'] }}][content]" wire:model="blocks.{{ $loop->index }}.content">
                                <div wire:loading wire:target="photos.{{ $block['id'] }}" class="be-uploading">
                                    <span class="be-spinner"></span> Processing image...
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
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
                                    {{ empty($block['content']) ? 'Upload video' : 'Replace video' }}
                                    <input type="file" accept="video/*" wire:model="videos.{{ $block['id'] }}" style="display:none;">
                                </label>
                                <input type="hidden" name="blocks[{{ $block['id'] }}][content]" wire:model="blocks.{{ $loop->index }}.content">
                                <div wire:loading wire:target="videos.{{ $block['id'] }}" class="be-uploading">
                                    <span class="be-spinner"></span> Uploading video...
                                </div>
                            </div>
                            @break

                        @case('math')
                            <textarea
                                class="be-input be-input-mono"
                                name="blocks[{{ $block['id'] }}][content]"
                                placeholder="Enter LaTeX (e.g., x^2 + y^2 = z^2)"
                                wire:model="blocks.{{ $loop->index }}.content"
                                oninput="triggerMathPreview(this)"
                                rows="2"
                            ></textarea>
                            @if(!empty($block['content']))
                                <div class="be-math-preview" data-math-src="{{ e($block['content']) }}"></div>
                            @endif
                            @break

                        @case('graph')
                            @php
                                $graphData = json_decode($block['content'], true) ?? ['type'=>'line','labels'=>['Jan','Feb','Mar'],'data'=>[10,20,15]];
                                $this->blocks[$loop->index]['graph_type']   ??= $graphData['type']   ?? 'line';
                                $this->blocks[$loop->index]['graph_labels'] ??= implode(',', $graphData['labels'] ?? []);
                                $this->blocks[$loop->index]['graph_data']   ??= implode(',', $graphData['data']   ?? []);
                            @endphp
                            <div class="be-field-stack">
                                <div class="be-field">
                                    <label class="be-field-label">Chart type</label>
                                    <select class="be-select" wire:model="blocks.{{ $loop->index }}.graph_type" name="blocks[{{ $block['id'] }}][chart_type]">
                                        <option value="line">Line Chart</option>
                                        <option value="bar">Bar Chart</option>
                                        <option value="pie">Pie Chart</option>
                                    </select>
                                </div>
                                <div class="be-field">
                                    <label class="be-field-label">Labels (comma separated)</label>
                                    <textarea class="be-input be-input-mono" rows="1" wire:model="blocks.{{ $loop->index }}.graph_labels" name="blocks[{{ $block['id'] }}][graph_labels]" placeholder="Jan, Feb, Mar"></textarea>
                                </div>
                                <div class="be-field">
                                    <label class="be-field-label">Values (comma separated)</label>
                                    <textarea class="be-input be-input-mono" rows="1" wire:model="blocks.{{ $loop->index }}.graph_data" name="blocks[{{ $block['id'] }}][graph_values]" placeholder="10, 20, 15"></textarea>
                                </div>
                                <input type="hidden" name="blocks[{{ $block['id'] }}][content]" wire:model.live.debounce.300ms="blocks.{{ $loop->index }}.content">
                            </div>
                            @break

                        @case('table')
                            @php
                                $tableData  = json_decode($block['content'], true) ?? [['Header 1','Header 2'],['Row 1 Col 1','Row 1 Col 2']];
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
                                                        <input type="text" value="{{ $cell }}" class="be-table-cell"
                                                               wire:change="updateTableCell({{ $block['id'] }},{{ $rowIndex }},{{ $colIndex }},$event.target.value)">
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
                            @php
                                $listData = json_decode($block['content'], true) ?? ['style'=>'bullet','items'=>['Item 1','Item 2']];
                                $listStyle = $block['list_style'] ?? ($listData['style'] ?? 'bullet');
                                $listItems = $block['list_items'] ?? implode("
", $listData['items'] ?? []);
                            @endphp
                            <div class="be-field-stack">
                                <div class="be-field">
                                    <label class="be-field-label">Style</label>
                                    <select class="be-select"
                                            wire:model="blocks.{{ $loop->index }}.list_style"
                                            name="blocks[{{ $block['id'] }}][list_style]">
                                        <option value="bullet"    @selected($listStyle === 'bullet')>Bullet</option>
                                        <option value="numbered"  @selected($listStyle === 'numbered')>Numbered</option>
                                        <option value="checklist" @selected($listStyle === 'checklist')>Checklist</option>
                                    </select>
                                </div>
                                <div class="be-field">
                                    <label class="be-field-label">Items (one per line)</label>
                                    <textarea
                                        class="be-input be-input-body"
                                        wire:model="blocks.{{ $loop->index }}.list_items"
                                        name="blocks[{{ $block['id'] }}][list_items]"
                                        oninput="autoResize(this)"
                                        rows="4"
                                        placeholder="Item 1&#10;Item 2&#10;Item 3">{{ $listItems }}</textarea>
                                </div>
                            </div>
                            @break

                        @case('separator')
                            @php
                                $sepData = json_decode($block['content'], true) ?? ['type'=>'divider'];
                                $sepType = $block['separator_type'] ?? ($sepData['type'] ?? 'divider');
                            @endphp
                            <div class="be-field">
                                <label class="be-field-label">Separator Style</label>
                                <select class="be-select"
                                        wire:model.live="blocks.{{ $loop->index }}.separator_type"
                                        name="blocks[{{ $block['id'] }}][separator_type]">
                                    <option value="divider"       @selected($sepType === 'divider')>Line Divider</option>
                                    <option value="section_break" @selected($sepType === 'section_break')>Section Break (§)</option>
                                    <option value="page_break"    @selected($sepType === 'page_break')>Page Break</option>
                                </select>
                                <div style="margin-top:10px;">
                                    @if($sepType === 'section_break')
                                        <div style="display:flex;align-items:center;gap:1rem;">
                                            <div style="flex:1;height:1px;background:var(--border);"></div>
                                            <span style="color:var(--text-faint);font-size:0.75rem;">§</span>
                                            <div style="flex:1;height:1px;background:var(--border);"></div>
                                        </div>
                                    @elseif($sepType === 'page_break')
                                        <div style="border:2px dashed var(--border);padding:8px;text-align:center;color:var(--text-faint);font-size:11px;border-radius:6px;">PAGE BREAK</div>
                                    @else
                                        <hr style="border:none;border-top:1px solid var(--border);">
                                    @endif
                                </div>
                            </div>
                            @break

                        @case('ext')
                            <div class="be-ext-warn">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                Raw HTML — use with caution
                            </div>
                            <div class="be-md-wrap" data-bid="{{ $block['id'] }}" data-mode="html">
                                <textarea
                                    class="be-input be-input-code be-input-dark be-md-src"
                                    placeholder="Paste HTML, iframe embed, or script code here..."
                                    wire:model="blocks.{{ $loop->index }}.content"
                                    oninput="autoResize(this);beMdLive(this)"
                                    rows="4"
                                ></textarea>
                                <div class="be-md-preview be-html-preview" style="display:none"></div>
                                <button type="button" class="be-md-toggle" onclick="beMdToggle(this)" title="Toggle HTML preview">👁 HTML</button>
                            </div>
                            @break

                        @default
                            <textarea
                                class="be-input be-input-body"
                                wire:model="blocks.{{ $loop->index }}.content"
                                oninput="autoResize(this)"
                                rows="2"
                            ></textarea>
                    @endswitch
                </div>

                {{-- Controls --}}
                <div class="be-block-controls">
                    <select class="be-type-select"
                            wire:change="updateBlockType({{ $block['id'] }}, $event.target.value)"
                            name="blocks[{{ $block['id'] }}][type]"
                            title="Change type">
                        <option value="markdown"    {{ $block['type']=='markdown'    ? 'selected':'' }}>MD</option>
                        <option value="header"      {{ $block['type']=='header'      ? 'selected':'' }}>H1</option>
                        <option value="description" {{ $block['type']=='description' ? 'selected':'' }}>Text</option>
                        <option value="note"        {{ $block['type']=='note'        ? 'selected':'' }}>Note</option>
                        <option value="code"        {{ $block['type']=='code'        ? 'selected':'' }}>Code</option>
                        <option value="exercise"    {{ $block['type']=='exercise'    ? 'selected':'' }}>Exercise</option>
                        <option value="photo"       {{ $block['type']=='photo'       ? 'selected':'' }}>Photo</option>
                        <option value="video"       {{ $block['type']=='video'       ? 'selected':'' }}>Video</option>
                        <option value="function"    {{ $block['type']=='function'    ? 'selected':'' }}>f(x)</option>
                        <option value="math"        {{ $block['type']=='math'        ? 'selected':'' }}>Math</option>
                        <option value="graph"       {{ $block['type']=='graph'       ? 'selected':'' }}>Graph</option>
                        <option value="table"       {{ $block['type']=='table'       ? 'selected':'' }}>Table</option>
                        <option value="ext"         {{ $block['type']=='ext'         ? 'selected':'' }}>HTML</option>
                    </select>
                    <div class="be-ctrl-divider"></div>
                    <button type="button" class="be-ctrl-btn" wire:click="moveBlock({{ $block['id'] }}, 'up')" title="Move up">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
                    </button>
                    <button type="button" class="be-ctrl-btn" wire:click="moveBlock({{ $block['id'] }}, 'down')" title="Move down">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="be-ctrl-divider"></div>
                    <button type="button" class="be-ctrl-btn be-ctrl-delete" wire:click="deleteBlock({{ $block['id'] }})" wire:confirm="Delete this block?" title="Delete">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                    </button>
                </div>

            </div>

        @empty
            <div class="be-empty">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 8v8M8 12h8"/></svg>
                <p>No blocks yet — use the <strong>+</strong> button below to add content.</p>
            </div>
        @endforelse

        {{-- Convert panel (markdown upgrade) --}}
        <div id="convert-panel" class="convert-panel-overlay" style="display:none" onclick="if(event.target===this)closeConvertPanel()">
            <div class="convert-panel-modal">
                <div class="convert-panel-header">
                    <span>⚡ Upgrade Markdown Block</span>
                    <button type="button" onclick="closeConvertPanel()" class="convert-panel-close">✕</button>
                </div>
                <p class="convert-panel-sub">Choose a type to convert this block into. The original markdown text will be pre-filled.</p>
                <div id="convert-preview-snippet" class="convert-panel-snippet mbe-preview-rendered"
                     style="max-height:160px;overflow:auto;margin-bottom:14px;padding:10px;border:1px solid var(--border);border-radius:6px;font-size:12px;background:var(--bg-subtle)"></div>
                <div class="convert-panel-grid">
                    @foreach([
                        ['header','H1','Heading','#EEEDFE','#534AB7'],
                        ['description','P','Paragraph','#f0f9ff','#0369a1'],
                        ['note','!','Note','#fef9c3','#854d0e'],
                        ['code','</>','Code','#F1EFE8','#444441'],
                        ['math','∑','Math (LaTeX)','#EEEDFE','#534AB7'],
                        ['exercise','?','Exercise','#EEEDFE','#534AB7'],
                        ['table','▦','Table','#E1F5EE','#0F6E56'],
                        ['list','≡','List','#fef3c7','#92400e'],
                        ['graph','≈','Graph','#E6F1FB','#185FA5'],
                        ['function','f(x)','Function Plot','#E1F5EE','#0F6E56'],
                    ] as [$type,$icon,$label,$bg,$fg])
                        <button type="button" class="convert-type-btn" onclick="doConvert('{{ $type }}')" style="--btn-bg:{{ $bg }};--btn-fg:{{ $fg }}">
                            <div class="convert-type-icon">{{ $icon }}</div>
                            <span>{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
                <div id="convert-status" style="margin-top:12px;font-size:12px;display:none"></div>
            </div>
        </div>

    </div>{{-- /be-blocks --}}

    {{-- ── Save bar ── --}}
    <div class="be-save-bar">
        {{-- <livewire:modular_site.block.blockcreate :lesson="$lesson"/>--}}

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

</div>



<script>
    /* ── Auto-resize all textareas to fit their content ── */
    function autoResize(el) {
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    }

    function autoResizeAll() {
        document.querySelectorAll('.be-blocks textarea').forEach(autoResize);
    }

    // Run on first load and after every Livewire re-render
    document.addEventListener('DOMContentLoaded', autoResizeAll);
    document.addEventListener('livewire:navigated', autoResizeAll);
    document.addEventListener('livewire:load',      () => setTimeout(autoResizeAll, 50));
    document.addEventListener('livewire:update',    () => setTimeout(autoResizeAll, 50));
    if (window.Livewire) {
        Livewire.hook('commit', ({ component, succeed }) => {
            succeed(() => setTimeout(autoResizeAll, 80));
        });
    }

    // ── Toolbar "Save All" button ──
    window.addEventListener('toolbar-save', () => {
        if (window.Livewire) Livewire.dispatch('triggerSaveAll');
    });

    // ── Math preview trigger (inline, before Livewire round-trip) ──
    window.triggerMathPreview = function(textarea) {
        const wrap = textarea.closest('.be-block-body');
        if (!wrap) return;
        const preview = wrap.querySelector('.be-math-preview');
        if (!preview) return;
        const raw = textarea.value || '';
        try {
            katex.render(raw, preview, { displayMode: true, throwOnError: false });
        } catch(e) {
            preview.textContent = raw;
        }
    };

    // ── Markdown block editor functions (defined here to avoid race condition) ──
    window.mbeSetTab = function(blockId, tab) {
        const editPane    = document.getElementById('mbe-edit-' + blockId);
        const previewPane = document.getElementById('mbe-preview-' + blockId);
        const tabs = document.querySelectorAll('.mbe-tabs[data-block-id="' + blockId + '"] .mbe-tab');
        tabs.forEach(t => t.classList.remove('active'));
        if (tab === 'edit') {
            editPane.style.display = '';
            previewPane.style.display = 'none';
            tabs[0] && tabs[0].classList.add('active');
        } else {
            editPane.style.display = 'none';
            previewPane.style.display = '';
            tabs[1] && tabs[1].classList.add('active');
            window.mbeRenderPreview(blockId);
        }
    };

    window.mbeUpdatePreview = function(blockId) {
        const previewPane = document.getElementById('mbe-preview-' + blockId);
        if (previewPane && previewPane.style.display !== 'none') {
            window.mbeRenderPreview(blockId);
        }
    };

    window.mbeRenderPreview = function(blockId) {
        const textarea    = document.querySelector('#mbe-edit-' + blockId + ' textarea');
        const previewPane = document.getElementById('mbe-preview-' + blockId);
        if (!textarea || !previewPane) return;
        const md = textarea.value || '';
        previewPane.innerHTML = (typeof marked !== 'undefined')
            ? marked.parse(md)
            : md.replace(/\n/g, '<br>');
        if (window.renderMathInElement) {
            renderMathInElement(previewPane, {
                delimiters: [
                    {left: '$$', right: '$$', display: true},
                    {left: '$',  right: '$',  display: false},
                    {left: '\\(', right: '\\)', display: false},
                    {left: '\\[', right: '\\]', display: true},
                ],
                throwOnError: false,
            });
        }
    };

    // ── Livewire commit hook: re-render math, canvases, resize textareas ──
    if (window.Livewire) {
        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => {
                setTimeout(() => {
                    // Resize textareas
                    document.querySelectorAll('.be-blocks textarea').forEach(autoResize);
                    // Math previews (KaTeX)
                    document.querySelectorAll('.be-math-preview').forEach(el => {
                        const src = el.dataset.mathSrc || el.textContent.replace(/^\$\$|\$\$$/g, '').trim();
                        if (src && window.katex) {
                            try {
                                katex.render(src, el, { displayMode: true, throwOnError: false });
                                el.dataset.mathSrc = src;
                            } catch(e) {}
                        }
                    });
                    // Function canvases
                    document.querySelectorAll('.function-editor').forEach(editor => {
                        if (typeof ImplicitPlotter !== 'undefined') {
                            const bid = editor.dataset.blockId;
                            const canvas = document.getElementById('func-canvas-' + bid);
                            if (canvas) {
                                const opts = {
                                    equation:   editor.querySelector('input[name*="func_expression"]')?.value ?? 'y=sin(x)',
                                    xMin:       parseFloat(editor.querySelector('input[name*="x_min"]')?.value)  || -10,
                                    xMax:       parseFloat(editor.querySelector('input[name*="x_max"]')?.value)  ||  10,
                                    yMin:       parseFloat(editor.querySelector('input[name*="y_min"]')?.value)  ||  -6,
                                    yMax:       parseFloat(editor.querySelector('input[name*="y_max"]')?.value)  ||   6,
                                    color:      editor.querySelector('input[name*="color"]')?.value              || '#4f46e5',
                                    resolution: parseFloat(editor.querySelector('input[name*="step"]')?.value)   || 0.05,
                                };
                                if (!canvas.style.height) canvas.style.height = '240px';
                                ImplicitPlotter.render(canvas, opts);
                            }
                        }
                    });
                }, 80);
            });
        });
    }

    // ── Scroll to newly created block ──
    window.addEventListener('scrollToNewBlock', (e) => {
        const blockId = e.detail?.blockId;
        if (!blockId) return;

        // Wait for Livewire to finish re-rendering, then scroll
        const tryScroll = (attempts = 0) => {
            // Try both data-block-id attr and the block-row cards by order
            const el = document.querySelector('[data-block-id="' + blockId + '"]')
                || document.querySelector('.block-row[data-id="' + blockId + '"]');

            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Flash highlight
                el.style.transition = 'box-shadow .3s';
                el.style.boxShadow = '0 0 0 3px var(--accent)';
                setTimeout(() => { el.style.boxShadow = ''; }, 1400);
            } else if (attempts < 8) {
                setTimeout(() => tryScroll(attempts + 1), 100);
            } else {
                // Fallback: scroll to last .block-row
                const all = document.querySelectorAll('.block-row');
                if (all.length) all[all.length - 1].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        };
        setTimeout(() => tryScroll(), 80);
    });

    // ── BUG FIX #9 (toolbar outline scroll): fixed in _lesson-toolbar_blade.php ──

    // ── Markdown preview toggle for individual blocks ──────────────────────
    window.beMdToggle = function(btn) {
        var wrap    = btn.closest('.be-md-wrap');
        var src     = wrap.querySelector('.be-md-src');
        var preview = wrap.querySelector('.be-md-preview');
        var isHtml  = wrap.dataset.mode === 'html';
        var showing = preview.style.display !== 'none';

        if (showing) {
            preview.style.display = 'none';
            src.style.display = '';
            btn.classList.remove('active');
        } else {
            src.style.display = 'none';
            preview.style.display = '';
            btn.classList.add('active');
            if (isHtml) {
                preview.innerHTML = src.value;
            } else {
                preview.innerHTML = (typeof marked !== 'undefined')
                    ? marked.parse(src.value || '')
                    : (src.value || '').replace(/\n/g, '<br>');
                if (window.renderMathInElement) {
                    renderMathInElement(preview, {
                        delimiters: [
                            {left: '$$', right: '$$', display: true},
                            {left: '$',  right: '$',  display: false},
                            {left: '\\(', right: '\\)', display: false},
                            {left: '\\[', right: '\\]', display: true},
                        ],
                        throwOnError: false,
                    });
                }
            }
        }
    };

    window.beMdLive = function(textarea) {
        var wrap    = textarea.closest('.be-md-wrap');
        if (!wrap) return;
        var preview = wrap.querySelector('.be-md-preview');
        if (!preview || preview.style.display === 'none') return;
        var isHtml  = wrap.dataset.mode === 'html';
        if (isHtml) {
            preview.innerHTML = textarea.value;
        } else {
            preview.innerHTML = (typeof marked !== 'undefined')
                ? marked.parse(textarea.value || '')
                : (textarea.value || '').replace(/\n/g, '<br>');
            if (window.renderMathInElement) {
                renderMathInElement(preview, {
                    delimiters: [
                        {left: '$$', right: '$$', display: true},
                        {left: '$',  right: '$',  display: false},
                        {left: '\\(', right: '\\)', display: false},
                        {left: '\\[', right: '\\]', display: true},
                    ],
                    throwOnError: false,
                });
            }
        }
    };

</script>
<style>


    /* ── Markdown preview toggle (shared by header/desc/note/exercise/ext) ── */
    .be-md-wrap { position: relative; }
    .be-md-toggle {
        position: absolute;
        top: 6px; right: 6px;
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: 5px;
        padding: 2px 7px;
        font-size: 11px;
        cursor: pointer;
        color: var(--text-muted);
        transition: background .13s, color .13s;
        z-index: 2;
        line-height: 1.6;
    }
    .be-md-toggle:hover { background: var(--bg-hover); color: var(--text); }
    .be-md-toggle.active { background: var(--accent); color: #fff; border-color: var(--accent); }
    .be-md-preview {
        min-height: 40px;
        padding: 10px 12px;
        border: 1px solid var(--border);
        border-radius: 7px;
        background: var(--bg-subtle);
        font-size: 14px;
        line-height: 1.65;
        color: var(--text);
        overflow-x: auto;
    }
    .be-md-preview h1,.be-md-preview h2,.be-md-preview h3 { font-weight:700;margin:.5em 0 .25em; }
    .be-md-preview p { margin: .4em 0; }
    .be-md-preview code { background:var(--bg-hover);padding:2px 5px;border-radius:3px;font-size:.9em; }
    .be-md-preview pre { background:var(--bg-hover);padding:10px;border-radius:6px;overflow-x:auto; }
    .be-md-preview ul,.be-md-preview ol { padding-left:1.4em;margin:.4em 0; }
    .be-md-preview blockquote { border-left:3px solid var(--accent);padding-left:10px;color:var(--text-muted);margin:.5em 0; }
    .be-md-preview table { border-collapse:collapse;width:100%;font-size:13px; }
    .be-md-preview th,.be-md-preview td { border:1px solid var(--border);padding:5px 8px; }
    .be-md-preview th { background:var(--bg-hover);font-weight:600; }
    .be-html-preview { border: 2px dashed var(--border); }

    /* ── Explode button ── */
    .mbe-tab--explode {
        background: linear-gradient(135deg, #f59e0b, #ef4444) !important;
        color: #fff !important;
        border-color: transparent !important;
        font-weight: 700;
    }
    .mbe-tab--explode:hover {
        opacity: .88;
    }
    .mbe-tab--explode:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    /* BUG FIX #7: x-cloak must be hidden before Alpine initialises */
    [x-cloak] { display: none !important; }

    /* ════════════════════════════════════
       BLOCKS EDITOR
    ════════════════════════════════════ */
    .blocks-editor {
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 0;
        overflow: hidden;
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
        top: -11px;
        z-index: 5;

    }
    .be-bc-dim    { font-size: 12px; color: var(--text-muted); }
    .be-bc-sep    { font-size: 12px; color: var(--text-faint); }
    .be-bc-active { font-size: 12px; font-weight: 600; color: var(--text); }
    .be-bc-status { font-size: 10px; font-weight: 600; padding: 2px 7px; border-radius: 20px; margin-left: 4px; }
    .be-bc-status.published { background: #d1fae5; color: #065f46; }
    .be-bc-status.draft     { background: #f3f4f6; color: #6b7280; }
    [data-theme="dark"] .be-bc-status.published { background: #064e3b; color: #6ee7b7; }
    [data-theme="dark"] .be-bc-status.draft     { background: #2a2a2a; color: #9ca3af; }

    /* ── Blocks list ── */
    .be-blocks {
        flex: 1;
        overflow-y: auto;
        padding: 14px 18px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    /* ── Single block ── */
    .be-block {
        display: flex;
        align-items: stretch;
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

    /* Left accent strip */
    .be-block-side {
        width: 34px;
        flex-shrink: 0;
        border-right: 3px solid;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        padding-top: 10px;
        background: var(--bg-subtle);
    }
    .be-type-label {
        font-size: 8px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .06em;
        writing-mode: vertical-rl;
        text-orientation: mixed;
        transform: rotate(180deg);
        opacity: .9;
    }

    /* Block body */
    .be-block-body {
        flex: 1;
        padding: 11px 14px;
        min-width: 0;
    }

    /* Controls column */
    .be-block-controls {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        padding: 8px 5px;
        border-left: 1px solid var(--border-mid);
        flex-shrink: 0;
        background: var(--bg-subtle);
    }
    .be-type-select {
        font-size: 10px;
        padding: 3px 2px;
        border: 1px solid var(--border);
        border-radius: 5px;
        background: var(--bg);
        color: var(--text-muted);
        cursor: pointer;
        font-family: inherit;
        width: 50px;
    }
    .be-ctrl-divider { width: 20px; height: 1px; background: var(--border-mid); margin: 2px 0; }
    .be-ctrl-btn {
        display: flex; align-items: center; justify-content: center;
        width: 26px; height: 26px;
        border: none; background: none; border-radius: 5px;
        cursor: pointer; color: var(--text-faint);
        transition: background .12s, color .12s;
    }
    .be-ctrl-btn:hover        { background: var(--bg-hover); color: var(--text); }
    .be-ctrl-delete:hover     { background: #fff5f5; color: #ef4444; }
    [data-theme="dark"] .be-ctrl-delete:hover { background: #2a0f0f; color: #f87171; }

    /* ── Inputs ── */
    .be-input {
        display: block; width: 100%;
        background: transparent; border: none; outline: none; resize: none;
        font-family: inherit; color: var(--text); line-height: 1.65;
        transition: background .12s; box-sizing: border-box;
    }
    .be-input:focus { background: var(--bg-subtle); border-radius: 4px; padding: 2px 4px; }
    .be-input::placeholder { color: var(--text-faint); }
    .be-input-title { font-size: 20px; font-weight: 700; letter-spacing: -.02em; min-height: 34px; }
    .be-input-body  { font-size: 13.5px; min-height: 46px; }
    .be-input-mono  { font-family: 'JetBrains Mono','Fira Code',monospace; font-size: 13px; }
    .be-input-code  { font-family: 'JetBrains Mono','Fira Code',monospace; font-size: 12.5px; line-height: 1.7; }
    .be-input-dark  { background: #0d1117 !important; color: #e2e8f0; border-radius: 6px; padding: 10px; }
    .be-input-sm    { font-size: 12px; padding: 4px 6px; border: 1px solid var(--border); border-radius: 5px; background: var(--bg-subtle); }

    /* ── Note ── */
    .be-note-wrap { background: #fffbeb; border: 1px solid #fde68a; border-radius: 7px; padding: 10px 12px; }
    [data-theme="dark"] .be-note-wrap { background: #1f1a0f; border-color: #78350f; }
    .be-note-label {
        display: flex; align-items: center; gap: 5px;
        font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
        color: #92400e; margin-bottom: 6px;
    }
    [data-theme="dark"] .be-note-label { color: #fcd34d; }

    /* ── Code ── */
    .be-code-wrap { background: #0d1117; border-radius: 8px; overflow: hidden; }
    .be-code-header { display:flex;align-items:center;gap:8px;padding:7px 12px;background:#161b22;border-bottom:1px solid #30363d; }
    .be-code-dots { display:flex;gap:5px; }
    .be-code-dots span { width:10px;height:10px;border-radius:50%; }
    .be-code-dots span:nth-child(1){background:#ff5f57}
    .be-code-dots span:nth-child(2){background:#febc2e}
    .be-code-dots span:nth-child(3){background:#28c840}
    .be-code-lang { font-size:10px;color:#8b949e;font-family:monospace;margin-left:auto; }
    .be-code-wrap .be-input { color:#e2e8f0; padding:12px; }

    /* ── Exercise ── */
    .be-exercise-wrap { display:flex;flex-direction:column;gap:8px; }
    .be-exercise-label, .be-solution-label {
        display:flex;align-items:center;gap:5px;
        font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;
        color:var(--text-faint);margin-bottom:2px;
    }
    .be-solution-wrap { padding:8px 10px;background:var(--bg-subtle);border:1px solid var(--border);border-radius:6px;border-left:3px solid #10b981; }

    /* ── Media ── */
    .be-media-wrap { display:flex;flex-direction:column;gap:8px; }
    .be-media-preview { display:flex;flex-direction:column;gap:4px; }
    .be-media-filename { font-size:11px;color:var(--text-faint); }
    .be-upload-label {
        display:inline-flex;align-items:center;gap:6px;
        padding:7px 14px;border:1.5px dashed var(--border);border-radius:7px;
        font-size:12px;color:var(--text-muted);cursor:pointer;font-weight:500;
        transition:border-color .15s,background .15s;
    }
    .be-upload-label:hover { border-color:var(--accent);color:var(--accent);background:var(--bg-hover); }
    .be-uploading { display:flex;align-items:center;gap:6px;font-size:12px;color:var(--accent); }
    .be-spinner { display:inline-block;width:12px;height:12px;border:2px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:be-spin .6s linear infinite; }
    @keyframes be-spin { to{transform:rotate(360deg)} }

    /* ── Math ── */
    .be-math-preview { margin-top:8px;padding:10px 12px;background:var(--bg-subtle);border:1px solid var(--border);border-radius:6px;font-family:'Times New Roman',serif;font-size:16px;min-height:40px; }

    /* ── Fields ── */
    .be-field { display:flex;flex-direction:column;gap:3px; }
    .be-field-label { font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text-faint); }
    .be-field-stack { display:flex;flex-direction:column;gap:8px; }
    .be-field-row { display:flex;gap:8px;flex-wrap:wrap; }
    .be-field-row .be-field { flex:1;min-width:65px; }
    .be-select { padding:5px 8px;border:1px solid var(--border);border-radius:6px;background:var(--bg-subtle);color:var(--text);font-size:12px;cursor:pointer;font-family:inherit; }
    .be-color-input { width:100%;height:32px;border:1px solid var(--border);border-radius:5px;cursor:pointer;padding:2px; }

    /* ── Table ── */
    .be-table-wrap { overflow-x:auto; }
    .be-table-actions { display:flex;gap:6px;margin-bottom:8px; }
    .be-btn-sm { padding:4px 10px;border:1px solid var(--border);border-radius:5px;background:none;font-size:11px;color:var(--text-muted);cursor:pointer;font-family:inherit;transition:background .12s; }
    .be-btn-sm:hover { background:var(--bg-hover);color:var(--text); }
    .be-table { width:100%;border-collapse:collapse;font-size:13px; }
    .be-table td { border:1px solid var(--border);padding:0;min-width:80px; }
    .be-th { background:var(--bg-subtle); }
    .be-table-cell { width:100%;border:none;background:transparent;padding:7px 9px;font-family:inherit;font-size:13px;color:var(--text);outline:none; }
    .be-table-cell:focus { background:var(--bg-hover); }

    /* ── Function ── */
    .be-function-wrap { display:flex;flex-direction:column;gap:6px; }
    .be-canvas-wrap { position:relative;margin-top:10px;background:var(--bg-subtle);border:1px solid var(--border);border-radius:8px;padding:10px; }

    /* ── Ext warn ── */
    .be-ext-warn { display:flex;align-items:center;gap:6px;font-size:11px;color:#f59e0b;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:5px 10px;margin-bottom:8px;font-weight:500; }
    [data-theme="dark"] .be-ext-warn { background:#1f1a0f;border-color:#78350f;color:#fcd34d; }

    /* ── Hint ── */
    .be-hint { font-size:11px;color:var(--text-faint);margin-top:4px;display:block; }

    /* ── Empty ── */
    .be-empty { display:flex;flex-direction:column;align-items:center;padding:48px 20px;color:var(--text-faint);font-size:13px;text-align:center;border:1.5px dashed var(--border);border-radius:10px; }
    .be-empty svg { opacity:.25;margin-bottom:10px; }

    /* ── Save bar ── */
    .be-save-bar {
        display:flex;align-items:center;justify-content:space-between;
        padding:10px 18px;border-top:1px solid var(--border);
        background:var(--bg);flex-shrink:0;gap:10px;
        position:sticky;bottom:-11px;z-index:5;
    }
    .be-save-btn {
        display:flex;align-items:center;gap:6px;
        padding:8px 20px;background:var(--accent);color:#fff;
        border:none;border-radius:8px;font-size:13px;font-weight:600;
        cursor:pointer;font-family:inherit;transition:background .15s;flex-shrink:0;
    }
    .be-save-btn:hover { background:var(--accent-hover); }
    .be-save-btn:disabled { opacity:.6;cursor:not-allowed; }
    .be-spin { animation:be-spin .7s linear infinite; }
</style>
