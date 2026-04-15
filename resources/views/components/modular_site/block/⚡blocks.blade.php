<?php

use App\Models\block;
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

    public function mount($course, $chapter, $lesson, $blocks)
    {
        $this->course = $course;
        $this->chapter = $chapter;
        $this->lesson = $lesson;
        $this->blocks = $blocks->map(function ($block) {
            $arr = $block->toArray();
            // Load solutions for exercise blocks
            if ($block->type === 'exercise') {
                $arr['solutions'] = $block->solutions->map(fn($s) => $s->toArray())->toArray();
            }
            $this->hydrateBlockFields($arr);
            return $arr;
        })->toArray();
    }

    public function addBlock($id)
    {
        $block = block::findOrFail($id);

        if ($block) {
            $this->blocks[] = $block->toArray();
        }

    }


    public function updateLesson($id, $chapterId)
    {
        $this->blocks = block::where('lesson_id', $id)
            ->orderBy('block_number')
            ->with('solutions') // eager load
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




        $this->lesson = lesson::findorfail($id);
        $this->chapter = $this->lesson->chapter;
    }

    public function updatedBlocks($value, $key){
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


    public function saveAll(){
        foreach ($this->blocks as $blockData) {
            $content = $blockData['content'] ?? null;

            // Re-encode structured types
            if ($blockData['type'] === 'graph') {
                $content = json_encode([
                    'type' => $blockData['graph_type'] ?? 'line',
                    'labels' => array_map('trim', explode(',', $blockData['graph_labels'] ?? '')),
                    'data' => array_map('trim', explode(',', $blockData['graph_data'] ?? '')),
                ]);
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

            // Save exercise solutions separately
            if ($blockData['type'] === 'exercise') {
                foreach ($blockData['solutions'] ?? [] as $solution) {
                    \App\Models\Solution::where('id', $solution['id'])
                        ->update(['content' => $solution['content']]);
                }
            }

            if(in_array($blockData['type'], ['photo', 'video'])){
                $blockData['file_name'] = $blockData['content'] ? basename($blockData['content']) : 'No file selected';
            }

        }

        $this->dispatch('notify', message: 'Saved!');
    }

    private function hydrateBlockFields(&$block)
    {
        if (in_array($block['type'], ['function', 'graph'])) {
            $data = json_decode($block['content'], true) ?? [];

            $block['func_expression'] = $data['function'] ?? 'sin(x)';
            $block['x_min'] = $data['x_min'] ?? -10;
            $block['x_max'] = $data['x_max'] ?? 10;
            $block['y_min'] = $data['y_min'] ?? -5;
            $block['y_max'] = $data['y_max'] ?? 5;
            $block['color'] = $data['color'] ?? '#4f46e5';
            $block['step'] = $data['step'] ?? 0.1;
        }

        if ($block['type'] === 'graph') {
            $data = json_decode($block['content'], true) ?? [];
            $block['graph_type'] = $data['type'] ?? 'line';
            $block['graph_labels'] = implode(',', $data['labels'] ?? []);
            $block['graph_data'] = implode(',', $data['data'] ?? []);
        }
    }


    public function deleteBlock(int $index)
    {
        $blockData = $this->blocks[$index] ?? null;

        if (!$blockData) return;

        Block::destroy($blockData['id']);

        array_splice($this->blocks, $index, 1);

        // Re-number remaining blocks
        foreach ($this->blocks as $i => &$b) {
            $b['block_number'] = $i + 1;
            block::where('id', $b['id'])->update(['block_number' => $b['block_number']]);
        }
    }

    public function moveBlock(int $index, string $direction)
    {
        $swapWith = $direction === 'up' ? $index - 1 : $index + 1;

        if ($swapWith < 0 || $swapWith >= count($this->blocks)) return;

        // Swap in array
        [$this->blocks[$index], $this->blocks[$swapWith]] =
            [$this->blocks[$swapWith], $this->blocks[$index]];

        // Persist new order
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
            Block::where('id', $this->blocks[$index]['id'])->update([
                'type' => $newType,
                'content' => $this->blocks[$index]['content'],
            ]);
            return;
        }

        Block::where('id', $this->blocks[$index]['id'])->update(['type' => $newType]);
    }

    public function updatedPhotos($value, $key)
    {
        // $key = block ID
        $path = $this->photos[$key]->store('blocks', 'public');

        // 🔥 update the block content مباشرة
        foreach ($this->blocks as &$block) {
            if ($block['id'] == $key) {
                $block['content'] = $path;
                break;
            }
        }
    }

    public function updatedVideos($value, $key){
        $path = $this->videos[$key]->store('blocks', 'public');

        // 🔥 update the block content مباشرة
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

<div>

    <div class="blocks-list stack-container">

        @forelse($blocks as $block)

            <div class="block-row type-{{ $block['type'] }}">
                <input type="hidden" name="blocks[{{ $block['id'] }}][id]" wire:model="blocks.{{$loop->index}}.id">
                <input type="hidden" name="blocks[{{ $block['id'] }}][block_number]"
                       wire:model="blocks.{{$loop->index}}.block_number">

                <div class="block-main-content">
                    @switch($block['type'])
                        @case('header')
                            <textarea type="text" name="blocks[{{ $block['id'] }}][content]"
                                      class="input-ghost title-style"
                                      placeholder="Enter Title..."
                                      wire:model="blocks.{{ $loop->index }}.content"></textarea>
                            @break

                        @case('description')
                        @case('note')
                        @case('code')
                            <textarea class="input-ghost content-style" name="blocks[{{ $block['id'] }}][content]"
                                      rows="1"
                                      oninput="this.style.height = '';this.style.height = this.scrollHeight + 'px'"
                                      wire:model="blocks.{{ $loop->index }}.content"></textarea>
                            @break

                        @case('exercise')
                            <div class="exercise-container">
                                <label>Question:</label>
                                <textarea class="input-ghost content-style" name="blocks[{{ $block['id'] }}][content]"
                                          rows="1"
                                          oninput="this.style.height='';this.style.height=this.scrollHeight+'px'"
                                          wire:model="blocks.{{ $loop->index }}.content"></textarea>
                                @foreach($block['solutions'] ?? [] as $sIndex => $solution)
                                    <label>Solution</label>
                                    <textarea class="input-ghost content-style"
                                              oninput="this.style.height='';this.style.height=this.scrollHeight+'px'"
                                              wire:model="blocks.{{ $loop->index }}.solutions.{{ $sIndex }}.content"
                                              name="blocks[{{ $block['id'] }}][solutions][{{ $solution['id']}}]"></textarea>
                                @endforeach
                            </div>
                            @break

                        @case('photo')
                            <div class="file-block">
                                @if(!empty($block['content']) && \Storage::disk('public')->exists($block['content']))
                                    <div class="file-preview">
                                        <img src="{{ asset('storage/' . $block['content']) }}"
                                             onclick="window.open(this.src)"

                                             style="max-width:200px;max-height:200px;object-fit:cover;border-radius:8px;cursor:pointer;">
                                        <small style="display:block;color:var(--text-faint);margin-top:4px;">
                                            {{ basename($block['content']) }}
                                        </small>
                                    </div>
                                @endif

                                <input type="file" name="blocks[{{ $block['id'] }}][content_file]" accept="image/*"
                                       wire:model="photos.{{ $block['id'] }}"
                                       class="file-input" style="margin-top:8px;font-size:12px;">
                                <input type="hidden" name="blocks[{{ $block['id'] }}][content]"
                                       wire:model="blocks.{{ $loop->index }}.content">
                                <div wire:loading wire:target="photos.{{ $block['id'] }}">
                                    <small>Uploading...</small>
                                </div>
                            </div>
                            @break

                        @case('video')
                            <div class="file-block">
                                @if(!empty($block['content']) && \Storage::exists('public/' . $block['content']))
                                    <div class="file-preview">
                                        <video src="{{ asset('storage/' . $block['content']) }}"
                                               style="max-width:300px;max-height:200px;border-radius:8px;"
                                               controls></video>
                                        <small style="display:block;color:var(--text-faint);margin-top:4px;">
                                            {{ basename($block['content']) }}
                                        </small>
                                    </div>
                                @endif
                                <input type="file" name="blocks[{{ $block['id'] }}][content_file]" accept="video/*"
                                       wire:model="videos.{{ $block['id'] }}"
                                       class="file-input" style="margin-top:8px;font-size:12px;">
                                <input type="hidden" name="blocks[{{ $block['id'] }}][content]"
                                       wire:model="blocks.{{ $loop->index }}.content">
                                <span class="upload-status-{{ $loop->index }}"
                                      style="font-size:11px;display:block;margin-top:4px;"></span>
                            </div>
                            @break
                        @case('math')
                            <textarea name="blocks[{{ $block['id'] }}][content]"
                                      class="input-ghost content-style math-input"
                                      placeholder="Enter LaTeX (e.g., x^2 + y^2 = z^2)"
                                      rows="2"
                                      wire:model.live.debounce.300ms="blocks.{{ $loop->index }}.content"></textarea>
                            <div class="math-preview"
                                 style="margin-top:8px;padding:12px;background:var(--bg-subtle);border-radius:6px;border:1px solid var(--border);min-height:40px;">
                                @if(!empty($block['content']))
                                    <div>$${{ $block['content'] }}$$</div>
                                @endif
                            </div>

                            @break

                        @case('graph')
                            @php
                                $graphData = json_decode($block['content'], true)
                                    ?? ['type' => 'line', 'labels' => ['Jan','Feb','Mar'], 'data' => [10,20,15]];
                                // Flatten into editable fields
                                $this->blocks[$loop->index]['graph_type']   ??= $graphData['type']   ?? 'line';
                                $this->blocks[$loop->index]['graph_labels'] ??= implode(',', $graphData['labels'] ?? []);
                                $this->blocks[$loop->index]['graph_data']   ??= implode(',', $graphData['data']   ?? []);
                            @endphp
                            <div class="graph-editor">
                                <select wire:model="blocks.{{ $loop->index }}.graph_type"
                                        name="blocks[{{ $block['id'] }}][chart_type]"
                                        class="mini-type-select"
                                        style="margin-bottom:8px;width:auto;display:inline-block;">
                                    <option value="line">Line Chart</option>
                                    <option value="bar">Bar Chart</option>
                                    <option value="pie">Pie Chart</option>
                                </select>
                                <textarea wire:model="blocks.{{ $loop->index }}.graph_labels"
                                          name="blocks[{{ $block['id'] }}][chart_data]"
                                          class="input-ghost content-style"
                                          placeholder="Labels: Jan, Feb, Mar (comma separated)"
                                          rows="2" style="font-family:monospace;font-size:12px;"></textarea>
                                <textarea wire:model="blocks.{{ $loop->index }}.graph_data"
                                          class="input-ghost content-style"
                                          placeholder="Values: 10, 20, 15 (comma separated)"
                                          rows="2" style="font-family:monospace;font-size:12px;"></textarea>
                                <small style="color:var(--text-faint);font-size:11px;">Row 1: Labels | Row 2:
                                    Values</small>
                                <input type="hidden" name="blocks[{{ $block['id'] }}][content]"
                                       wire:model.live.debounce.300ms="blocks.{{ $loop->index }}.content">
                            </div>
                            @break

                        @case('table')
                            @php
                                $tableData = json_decode($block['content'], true)
                                    ?? [['Header 1', 'Header 2'], ['Row 1 Col 1', 'Row 1 Col 2']];
                                $blockIndex = collect($blocks)->search(fn($b) => $b['id'] === $block['id']);
                            @endphp
                            <div class="table-editor" data-block-id="{{ $block['id'] }}">
                                <div class="table-actions" style="margin-bottom:8px;display:flex;gap:6px;">
                                    <button type="button" wire:click="addTableRow({{ $block['id'] }})"
                                            class="arrow-btn" style="width:auto;padding:4px 10px;font-size:11px;">+ Row</button>
                                    <button type="button" wire:click="addTableCol({{ $block['id'] }})"
                                            class="arrow-btn" style="width:auto;padding:4px 10px;font-size:11px;">+ Column</button>
                                </div>
                                <div style="overflow-x:auto;">
                                    <table class="editable-table" style="width:100%;border-collapse:collapse;font-size:13px;">
                                        @foreach($tableData as $rowIndex => $row)
                                            <tr>
                                                @foreach($row as $colIndex => $cell)
                                                    <td style="border:1px solid var(--border);padding:0;min-width:80px;">
                                                        <input type="text" value="{{ $cell }}" wire:change="updateTableCell({{$block['id']}},{{$rowIndex}},{{$colIndex}},$event.target.value)"
                                                               style="width:100%;border:none;background:transparent;padding:8px;font-family:inherit;color:var(--text);">
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
                                    <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">
                                        Equation (any form: <code>y=sin(x)</code>, <code>x^2+y^2=1</code>, <code>y-x^2=0</code> …)
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
                                        <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">X min</label>
                                        <input type="number" name="blocks[{{ $block['id'] }}][x_min]"
                                               wire:model="blocks.{{ $loop->index }}.x_min"
                                               class="input-ghost" style="width:100%;padding:5px 8px;" step="any">
                                    </div>
                                    <div style="flex:1;min-width:70px;">
                                        <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">X max</label>
                                        <input type="number" name="blocks[{{ $block['id'] }}][x_max]"
                                               wire:model="blocks.{{ $loop->index }}.x_max"
                                               class="input-ghost" style="width:100%;padding:5px 8px;" step="any">
                                    </div>
                                    <div style="flex:1;min-width:70px;">
                                        <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Y min</label>
                                        <input type="number" name="blocks[{{ $block['id'] }}][y_min]"
                                               wire:model="blocks.{{ $loop->index }}.y_min"
                                               class="input-ghost" style="width:100%;padding:5px 8px;" step="any">
                                    </div>
                                    <div style="flex:1;min-width:70px;">
                                        <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Y max</label>
                                        <input type="number" name="blocks[{{ $block['id'] }}][y_max]"
                                               wire:model="blocks.{{ $loop->index }}.y_max"
                                               class="input-ghost" style="width:100%;padding:5px 8px;" step="any">
                                    </div>
                                    <div style="flex:0 0 auto;">
                                        <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Color</label>
                                        <input type="color" name="blocks[{{ $block['id'] }}][color]"
                                               wire:model="blocks.{{ $loop->index }}.color"
                                               style="width:48px;height:32px;border:none;cursor:pointer;border-radius:4px;">
                                    </div>
                                    <div style="flex:1;min-width:70px;">
                                        <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Resolution</label>
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
                                    Supports: +  −  *  /  ^  sin cos tan asin acos atan sqrt abs log ln exp pi e — both x and y variables.
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

                        @case('ext')
                            <textarea class="input-ghost content-style"
                                      placeholder="Paste HTML, iframe embed, or script code here..." rows="4"
                                      style="font-family:'JetBrains Mono', monospace;font-size:12px;background:#0d1117;color:#e2e8f0;"
                                      wire:model="blocks.{{ $loop->index }}.content"></textarea>
                            <small style="color:var(--text-faint);font-size:11px;display:block;margin-top:4px;">⚠️ Raw
                                HTML - Be careful with external scripts</small>
                            @break

                        @default
                            <textarea class="input-ghost content-style"
                                      rows="1" wire:model="blocks.{{ $loop->index }}.content"></textarea>
                    @endswitch
                </div>

                <div class="block-controls">
                    <div class="control-group">
                        <span class="control-icon" onclick="toggleTypeSelect('{{ $block['id'] }}')">✏️</span>
                        <select wire:change="updateBlockType({{ $loop->index }}, $event.target.value)" name="blocks[{{ $block['id'] }}][type]"
                                id="select-{{ $block['id'] }}">
                            <option value="header" {{ $block['type'] == 'header' ? 'selected' : '' }}>H1</option>
                            <option value="description" {{ $block['type'] == 'description' ? 'selected' : '' }}>Text
                            </option>
                            <option value="note" {{ $block['type'] == 'note' ? 'selected' : '' }}>Note</option>
                            <option value="code" {{ $block['type'] == 'code' ? 'selected' : '' }}>Code</option>
                            <option value="exercise" {{ $block['type'] == 'exercise' ? 'selected' : '' }}>Exercise
                            </option>
                            <option value="photo" {{ $block['type'] == 'photo' ? 'selected' : '' }}>Photo</option>
                            <option value="video" {{ $block['type'] == 'video' ? 'selected' : '' }}>Video</option>
                            <option value="function" {{ $block['type'] == 'function' ? 'selected' : '' }}>Function
                            </option>
                            <option value="math" {{ $block['type'] == 'math' ? 'selected' : '' }}>Math</option>
                            <option value="graph" {{ $block['type'] == 'graph' ? 'selected' : '' }}>Graph</option>
                            <option value="table" {{ $block['type'] == 'table' ? 'selected' : '' }}>Table</option>
                            <option value="ext" {{ $block['type'] == 'ext' ? 'selected' : '' }}>HTML/Ext</option>

                        </select>
                    </div>

                    <button type="button" wire:click="moveBlock({{ $loop->index }}, 'up')" class="arrow-btn">∧</button>
                    <button type="button" wire:click="moveBlock({{ $loop->index }}, 'down')" class="arrow-btn">∨
                    </button>
                    <button type="button" wire:click="deleteBlock({{ $loop->index }})"
                            class="arrow-btn" style="color:red;">✕
                    </button>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <p>No content here yet. Click the <strong>+</strong> button to add a block.</p>
            </div>
        @endforelse
    </div>

    <div class="save-container">
        <button type="submit" class="btn-save-all" wire:click="saveAll">Save All Changes</button>
    </div>
    <livewire:modular_site.block.blockcreate :lesson="$lesson"/>

</div>
