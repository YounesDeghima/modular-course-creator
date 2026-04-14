<?php

use App\Models\block;
use App\Models\lesson;
use Livewire\Component;

new class extends Component {
    public $course;
    public $chapter;
    public $lesson;
    public $blocks;

    public $listeners = ['LessonChanged' => 'updateLesson',
                         'BlockCreated' =>'addBlock'];

    public function mount($course, $chapter, $lesson, $blocks)
    {

        $this->course = $course;
        $this->chapter = $chapter;
        $this->lesson = $lesson;
        $this->blocks = $blocks->map(function ($block) {
            return $block->toArray();
        })->toArray();

    }
    public function addBlock($id){
        $block = block::findOrFail($id);

        if ($block) {
            $this->blocks[] = $block->toArray();
        }

    }


    public function updateLesson($id, $chapterId)
    {
        $this->blocks = block::where('lesson_id', $id)
            ->orderBy('block_number')
            ->get()
            ->map(fn($b) => $b->toArray())
            ->toArray();
        $this->lesson = lesson::findorfail($id);
        $this->chapter = $this->lesson->chapter;
    }

    public function saveAll() {
        foreach ($this->blocks as $blockData) {
            block::where('id', $blockData['id'])
                ->update([
                    'content'      => $blockData['content'],
                    'type'         => $blockData['type'],
                    'block_number' => $blockData['block_number'],
                ]);
        }

        session()->flash('message', 'Saved!');
    }

    public function deleteBlock(int $index) {
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

    public function moveBlock(int $index, string $direction) {
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

    public function updateBlockType(int $index, string $newType) {
        $this->blocks[$index]['type'] = $newType;
        Block::where('id', $this->blocks[$index]['id'])->update(['type' => $newType]);
    }

};
?>

<div>

    <div class="blocks-list stack-container">

        @forelse($blocks as $block)

            <div class="block-row type-{{ $block['type'] }}">
                <input type="hidden"  wire:model="blocks.{{$loop->index}}.id">
                <input type="hidden"  wire:model="blocks.{{$loop->index}}.block_number">

                <div class="block-main-content">
                    @switch($block['type'])
                        @case('header')
                            <textarea type="text"
                                      class="input-ghost title-style"
                                      placeholder="Enter Title..."  wire:model="blocks.{{ $loop->index }}.content"></textarea>
                            @break

                        @case('description')
                        @case('note')
                        @case('code')
                            <textarea  class="input-ghost content-style"
                                      rows="1"
                                      oninput="this.style.height = '';this.style.height = this.scrollHeight + 'px'"   wire:model="blocks.{{ $loop->index }}.content"></textarea>
                            @break

                        @case('exercise')
                            <div class="exercise-container">
                                <label>Question:</label>
                                <textarea  class="input-ghost content-style"
                                          rows="1"
                                          oninput="this.style.height='';this.style.height=this.scrollHeight+'px'"    wire:model="blocks.{{ $loop->index }}.content"></textarea>
                                @foreach($block['solutions'] ?? [] as $sIndex => $solution)
                                    <label>Solution</label>
                                    <textarea class="input-ghost content-style"
                                              oninput="this.style.height='';this.style.height=this.scrollHeight+'px'" wire:model="blocks.{{ $loop->index }}.solutions.{{ $sIndex }}.content"></textarea>
                                @endforeach
                            </div>
                            @break

                        @case('photo')
                            <div class="file-block">
                                @if($block['content'] && \Storage::exists('public/' . $block['content']))
                                    <div class="file-preview">
                                        <img src="{{ asset('storage/' . $block['content']) }}"
                                             onclick="window.open(this.src)"
                                             style="max-width:200px;max-height:200px;object-fit:cover;border-radius:8px;cursor:pointer;">
                                        <small
                                            style="display:block;color:var(--text-faint);margin-top:4px;">{{ basename($block['content']) }}</small>
                                    </div>
                                    @dd($block['content'])
                                @endif
                                <input type="file" name="blocks[{{ $block['id'] }}][content_file]" accept="image/*"
                                       class="file-input" style="margin-top:8px;font-size:12px;">
                                <input type="hidden"
                                       value="">
                            </div>
                            @break

                        @case('video')
                            <div class="file-block">
                                @if($block['content'] && \Storage::exists('public/' . $block['content']))
                                    <div class="file-preview">
                                        <video src="{{ asset('storage/' . $block['content']) }}"
                                               style="max-width:200px;max-height:200px;border-radius:8px;"
                                               controls></video>
                                        <small
                                            style="display:block;color:var(--text-faint);margin-top:4px;">{{ basename($block['content']) }}</small>
                                    </div>
                                @endif
                                <input type="file" name="blocks[{{ $block['id'] }}][content_file]" accept="video/*"
                                       class="file-input" style="margin-top:8px;font-size:12px;">
                                <input type="hidden"
                                       value="">
                            </div>
                            @break

                        @case('math')
                            <textarea
                                      class="input-ghost content-style math-input"
                                      placeholder="Enter LaTeX (e.g., x^2 + y^2 = z^2)"
                                      oninput="updateMathPreview(this)" rows="2"  wire:model="blocks.{{ $loop->index }}.content"></textarea>
                            <div class="math-preview"
                                 style="margin-top:8px;padding:12px;background:var(--bg-subtle);border-radius:6px;border:1px solid var(--border);min-height:40px;font-family:'Times New Roman', serif;font-size:16px;">
                                @if($block['content'])
                                    $ $
                                @endif
                            </div>
                            @break

                        @case('graph')
                            @php $graphData = json_decode($block['content'], true) ?? ['type' => 'line', 'labels' => ['Jan','Feb','Mar'], 'data' => [10,20,15]]; @endphp
                            <div class="graph-editor">
                                <select name="blocks[{{ $block['id'] }}][chart_type]" class="mini-type-select"
                                        style="margin-bottom:8px;width:auto;display:inline-block;">
                                    <option
                                        value="line" {{ ($graphData['type'] ?? 'line') == 'line' ? 'selected' : '' }}>
                                        Line Chart
                                    </option>
                                    <option value="bar" {{ ($graphData['type'] ?? '') == 'bar' ? 'selected' : '' }}>Bar
                                        Chart
                                    </option>
                                    <option value="pie" {{ ($graphData['type'] ?? '') == 'pie' ? 'selected' : '' }}>Pie
                                        Chart
                                    </option>
                                </select>
                                <textarea name="blocks[{{ $block['id'] }}][chart_data]" class="input-ghost content-style"
                                          placeholder="Labels: Jan, Feb, Mar (comma separated)&#10;Values: 10, 20, 15 (comma separated)"
                                          rows="3" style="font-family:monospace;font-size:12px;">{{ implode(',', $graphData['labels'] ?? []) }}&#10;{{ implode(',', $graphData['data'] ?? []) }}</textarea>
                                <small style="color:var(--text-faint);font-size:11px;">Line 1: Labels (comma separated)
                                    | Line 2: Values</small>
                            </div>
                            <input type="hidden"  value="">
                            @break

                        @case('table')
                            @php $tableData = json_decode($block['content'], true) ?? [['Header 1', 'Header 2'], ['Row 1 Col 1', 'Row 1 Col 2']]; @endphp
                            <div class="table-editor" data-block-id="{{ $block['id'] }}">
                                <div class="table-actions" style="margin-bottom:8px;display:flex;gap:6px;">
                                    <button type="button" onclick="addTableRow({{ $block['id'] }})" class="arrow-btn"
                                            style="width:auto;padding:4px 10px;font-size:11px;">+ Row
                                    </button>
                                    <button type="button" onclick="addTableCol({{ $block['id'] }})" class="arrow-btn"
                                            style="width:auto;padding:4px 10px;font-size:11px;">+ Column
                                    </button>
                                </div>
                                <div style="overflow-x:auto;">
                                    <table class="editable-table"
                                           style="width:100%;border-collapse:collapse;font-size:13px;">
                                        @foreach($tableData as $rowIndex => $row)
                                            <tr>
                                                @foreach($row as $colIndex => $cell)
                                                    <td style="border:1px solid var(--border);padding:0;min-width:80px;">
                                                        <input type="text"
                                                               name="blocks[{{ $block['id'] }}][table_data][{{ $rowIndex }}][{{ $colIndex }}]"
                                                               value="{{ $cell }}"
                                                               style="width:100%;border:none;background:transparent;padding:8px;font-family:inherit;color:var(--text);"
                                                               onchange="updateTableJSON({{ $block['id'] }})">
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                            <input type="hidden"  class="table-content-hidden"
                                   value="">
                            @break

                        @case('function')
                            @php
                                $funcData = json_decode($block['content'], true) ?? [
                                    'function' => 'sin(x)',
                                    'x_min' => -10,
                                    'x_max' => 10,
                                    'y_min' => -5,
                                    'y_max' => 5,
                                    'color' => '#4f46e5',
                                    'step' => 0.1
                                ];
                            @endphp
                            <div class="function-editor" data-block-id="{{ $block['id'] }}">
                                <div class="function-input-row"
                                     style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;">
                                    <div style="flex:2;min-width:200px;">
                                        <label
                                            style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">f(x)
                                            =</label>
                                        <input type="text" name="blocks[{{ $block['id'] }}][func_expression]"
                                               value="{{ $funcData['function'] ?? 'sin(x)' }}"
                                               class="input-ghost"
                                               style="width:100%;font-family:'JetBrains Mono',monospace;font-size:13px;padding:6px 8px;"
                                               placeholder="e.g., sin(x), x^2, cos(x)*x">
                                    </div>
                                    <div style="flex:1;min-width:80px;">
                                        <label
                                            style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">X
                                            Min</label>
                                        <input type="number" name="blocks[{{ $block['id'] }}][x_min]"
                                               value="{{ $funcData['x_min'] ?? -10 }}"
                                               class="input-ghost" style="width:100%;padding:6px 8px;" step="any">
                                    </div>
                                    <div style="flex:1;min-width:80px;">
                                        <label
                                            style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">X
                                            Max</label>
                                        <input type="number" name="blocks[{{ $block['id'] }}][x_max]"
                                               value="{{ $funcData['x_max'] ?? 10 }}"
                                               class="input-ghost" style="width:100%;padding:6px 8px;" step="any">
                                    </div>
                                </div>
                                <div class="function-input-row"
                                     style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;">
                                    <div style="flex:1;min-width:80px;">
                                        <label
                                            style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Y
                                            Min</label>
                                        <input type="number" name="blocks[{{ $block['id'] }}][y_min]"
                                               value="{{ $funcData['y_min'] ?? -5 }}"
                                               class="input-ghost" style="width:100%;padding:6px 8px;" step="any">
                                    </div>
                                    <div style="flex:1;min-width:80px;">
                                        <label
                                            style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Y
                                            Max</label>
                                        <input type="number" name="blocks[{{ $block['id'] }}][y_max]"
                                               value="{{ $funcData['y_max'] ?? 5 }}"
                                               class="input-ghost" style="width:100%;padding:6px 8px;" step="any">
                                    </div>
                                    <div style="flex:1;min-width:80px;">
                                        <label
                                            style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Color</label>
                                        <input type="color" name="blocks[{{ $block['id'] }}][color]"
                                               value="{{ $funcData['color'] ?? '#4f46e5' }}"
                                               style="width:100%;height:32px;border:none;cursor:pointer;">
                                    </div>
                                    <div style="flex:1;min-width:80px;">
                                        <label
                                            style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Step</label>
                                        <input type="number" name="blocks[{{ $block['id'] }}][step]"
                                               value="{{ $funcData['step'] ?? 0.1 }}"
                                               class="input-ghost" style="width:100%;padding:6px 8px;" step="0.01"
                                               min="0.01" max="1">
                                    </div>
                                </div>
                                <div class="function-preview"
                                     style="margin-top:12px;padding:12px;background:var(--bg-subtle);border-radius:6px;border:1px solid var(--border);">
                                    <canvas id="func-canvas-{{ $block['id'] }}" width="400" height="200"
                                            style="width:100%;max-width:100%;height:auto;background:var(--bg);border-radius:4px;"></canvas>
                                </div>
                                <small style="color:var(--text-faint);font-size:11px;display:block;margin-top:4px;">
                                    Use JavaScript math syntax: sin(x), cos(x), tan(x), x^2, sqrt(x), log(x), abs(x),
                                    etc.
                                </small>
                            </div>
                            <input type="hidden"
                                   class="function-content-hidden" value="">
                            @break

                        @case('ext')
                            <textarea  class="input-ghost content-style"
                                      placeholder="Paste HTML, iframe embed, or script code here..." rows="4"
                                      style="font-family:'JetBrains Mono', monospace;font-size:12px;background:#0d1117;color:#e2e8f0;"  wire:model="blocks.{{ $loop->index }}.content"></textarea>
                            <small style="color:var(--text-faint);font-size:11px;display:block;margin-top:4px;">⚠️ Raw
                                HTML - Be careful with external scripts</small>
                            @break



                        @default
                            <textarea  class="input-ghost content-style"
                                      rows="1"  wire:model="blocks.{{ $loop->index }}.content"></textarea>
                    @endswitch
                </div>

                <div class="block-controls">
                    <div class="control-group">
                        <span class="control-icon" onclick="toggleTypeSelect('{{ $block['id'] }}')">✏️</span>
                        <select wire:change="updateBlockType({{ $loop->index }}, $event.target.value)"
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

                    <button type="button" wire:click="moveBlock({{ $loop->index }}, 'up')"   class="arrow-btn">∧</button>
                    <button type="button" wire:click="moveBlock({{ $loop->index }}, 'down')" class="arrow-btn">∨</button>
                    <button type="button" wire:click="deleteBlock({{ $loop->index }})"
                            class="arrow-btn" style="color:red;">✕</button>
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
