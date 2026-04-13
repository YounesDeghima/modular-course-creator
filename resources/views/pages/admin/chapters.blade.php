@if(!request()->ajax())
    @extends('layouts.edditor')
@endif


@section('css')

    <link rel="stylesheet" href="{{asset('css/modular-site.css')}}">


    {{--    <link rel="stylesheet" href="{{asset('css/block-editor.css')}}">--}}
    <link rel="stylesheet" href="{{asset('css/admin-layout.css')}}">

@endsection

@section('back-button')
    <a class="back-button" href="{{route('admin.courses.index')}}">{{$course->title}}</a>
@endsection




@section('main')

    @fragment('main-content')
        <div class="blocks-wrapper">
            <div class="route-header">
                <h2>
                    <span class="course-name">{{$course->title}}</span>
                    <small>></small> {{$chapter->title}}
                    <small>></small> <span class="active-lesson">{{$lesson->title}}</span>
                </h2>
            </div>

            {{-- Single form wrapper for bulk saving --}}
            <form onsubmit="saveAllBlocks(event, this)" enctype="multipart/form-data" class="block-form" action="{{ route('admin.courses.chapters.lessons.blocks.update-all', [$course->id, $chapter->id, $lesson->id]) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="blocks-list stack-container">
                    @forelse($blocks as $block)
                        <div class="block-row type-{{ $block->type }}">
                            <input type="hidden" name="blocks[{{ $block->id }}][id]" value="{{ $block->id }}">
                            <input type="hidden" name="blocks[{{ $block->id }}][block_number]" value="{{ $block->block_number }}">

                            <div class="block-main-content">
                                @switch($block->type)
                                    @case('header')
                                        <textarea type="text" name="blocks[{{ $block->id }}][content]" class="input-ghost title-style" placeholder="Enter Title...">{{ $block->content }}</textarea>
                                        @break

                                    @case('description')
                                    @case('note')
                                    @case('code')
                                        <textarea name="blocks[{{ $block->id }}][content]" class="input-ghost content-style" rows="1" oninput="this.style.height = '';this.style.height = this.scrollHeight + 'px'">{{ $block->content }}</textarea>
                                        @break

                                    @case('exercise')
                                        <div class="exercise-container">
                                            <label>Question:</label>
                                            <textarea name="blocks[{{ $block->id }}][content]" class="input-ghost content-style" rows="1" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">{{ $block->content }}</textarea>
                                            @foreach($block->solutions as $solution)
                                                <label>Solution</label>
                                                <textarea class="input-ghost content-style" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'" name="blocks[{{ $block->id }}][solutions][{{ $solution->id}}]">{{ $solution->content }}</textarea>
                                            @endforeach
                                        </div>
                                        @break

                                    @case('photo')
                                        <div class="file-block">
                                            @if($block->content && \Storage::exists('public/' . $block->content))
                                                <div class="file-preview">
                                                    <img src="{{ asset('storage/' . $block->content) }}" onclick="window.open(this.src)" style="max-width:200px;max-height:200px;object-fit:cover;border-radius:8px;cursor:pointer;">
                                                    <small style="display:block;color:var(--text-faint);margin-top:4px;">{{ basename($block->content) }}</small>
                                                </div>
                                            @endif
                                            <input type="file" name="blocks[{{ $block->id }}][content_file]" accept="image/*" class="file-input" style="margin-top:8px;font-size:12px;">
                                            <input type="hidden" name="blocks[{{ $block->id }}][content]" value="{{ $block->content }}">
                                        </div>
                                        @break

                                    @case('video')
                                        <div class="file-block">
                                            @if($block->content && \Storage::exists('public/' . $block->content))
                                                <div class="file-preview">
                                                    <video src="{{ asset('storage/' . $block->content) }}" style="max-width:200px;max-height:200px;border-radius:8px;" controls></video>
                                                    <small style="display:block;color:var(--text-faint);margin-top:4px;">{{ basename($block->content) }}</small>
                                                </div>
                                            @endif
                                            <input type="file" name="blocks[{{ $block->id }}][content_file]" accept="video/*" class="file-input" style="margin-top:8px;font-size:12px;">
                                            <input type="hidden" name="blocks[{{ $block->id }}][content]" value="{{ $block->content }}">
                                        </div>
                                        @break

                                    @case('math')
                                        <textarea name="blocks[{{ $block->id }}][content]" class="input-ghost content-style math-input" placeholder="Enter LaTeX (e.g., x^2 + y^2 = z^2)" oninput="updateMathPreview(this)" rows="2">{{ $block->content }}</textarea>
                                        <div class="math-preview" style="margin-top:8px;padding:12px;background:var(--bg-subtle);border-radius:6px;border:1px solid var(--border);min-height:40px;font-family:'Times New Roman', serif;font-size:16px;">
                                            @if($block->content) ${{ $block->content }} $ @endif
                                        </div>
                                        @break

                                    @case('graph')
                                        @php $graphData = json_decode($block->content, true) ?? ['type' => 'line', 'labels' => ['Jan','Feb','Mar'], 'data' => [10,20,15]]; @endphp
                                        <div class="graph-editor">
                                            <select name="blocks[{{ $block->id }}][chart_type]" class="mini-type-select" style="margin-bottom:8px;width:auto;display:inline-block;">
                                                <option value="line" {{ ($graphData['type'] ?? 'line') == 'line' ? 'selected' : '' }}>Line Chart</option>
                                                <option value="bar" {{ ($graphData['type'] ?? '') == 'bar' ? 'selected' : '' }}>Bar Chart</option>
                                                <option value="pie" {{ ($graphData['type'] ?? '') == 'pie' ? 'selected' : '' }}>Pie Chart</option>
                                            </select>
                                            <textarea name="blocks[{{ $block->id }}][chart_data]" class="input-ghost content-style" placeholder="Labels: Jan, Feb, Mar (comma separated)&#10;Values: 10, 20, 15 (comma separated)" rows="3" style="font-family:monospace;font-size:12px;">{{ implode(',', $graphData['labels'] ?? []) }}&#10;{{ implode(',', $graphData['data'] ?? []) }}</textarea>
                                            <small style="color:var(--text-faint);font-size:11px;">Line 1: Labels (comma separated) | Line 2: Values</small>
                                        </div>
                                        <input type="hidden" name="blocks[{{ $block->id }}][content]" value="{{ $block->content }}">
                                        @break

                                    @case('table')
                                        @php $tableData = json_decode($block->content, true) ?? [['Header 1', 'Header 2'], ['Row 1 Col 1', 'Row 1 Col 2']]; @endphp
                                        <div class="table-editor" data-block-id="{{ $block->id }}">
                                            <div class="table-actions" style="margin-bottom:8px;display:flex;gap:6px;">
                                                <button type="button" onclick="addTableRow({{ $block->id }})" class="arrow-btn" style="width:auto;padding:4px 10px;font-size:11px;">+ Row</button>
                                                <button type="button" onclick="addTableCol({{ $block->id }})" class="arrow-btn" style="width:auto;padding:4px 10px;font-size:11px;">+ Column</button>
                                            </div>
                                            <div style="overflow-x:auto;">
                                                <table class="editable-table" style="width:100%;border-collapse:collapse;font-size:13px;">
                                                    @foreach($tableData as $rowIndex => $row)
                                                        <tr>
                                                            @foreach($row as $colIndex => $cell)
                                                                <td style="border:1px solid var(--border);padding:0;min-width:80px;">
                                                                    <input type="text" name="blocks[{{ $block->id }}][table_data][{{ $rowIndex }}][{{ $colIndex }}]" value="{{ $cell }}" style="width:100%;border:none;background:transparent;padding:8px;font-family:inherit;color:var(--text);" onchange="updateTableJSON({{ $block->id }})">
                                                                </td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </table>
                                            </div>
                                        </div>
                                        <input type="hidden" name="blocks[{{ $block->id }}][content]" class="table-content-hidden" value="{{ $block->content }}">
                                        @break

                                    @case('function')
                                        @php
                                            $funcData = json_decode($block->content, true) ?? [
                                                'function' => 'sin(x)',
                                                'x_min' => -10,
                                                'x_max' => 10,
                                                'y_min' => -5,
                                                'y_max' => 5,
                                                'color' => '#4f46e5',
                                                'step' => 0.1
                                            ];
                                        @endphp
                                        <div class="function-editor" data-block-id="{{ $block->id }}">
                                            <div class="function-input-row" style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;">
                                                <div style="flex:2;min-width:200px;">
                                                    <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">f(x) =</label>
                                                    <input type="text" name="blocks[{{ $block->id }}][func_expression]"
                                                           value="{{ $funcData['function'] ?? 'sin(x)' }}"
                                                           class="input-ghost"
                                                           style="width:100%;font-family:'JetBrains Mono',monospace;font-size:13px;padding:6px 8px;"
                                                           placeholder="e.g., sin(x), x^2, cos(x)*x">
                                                </div>
                                                <div style="flex:1;min-width:80px;">
                                                    <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">X Min</label>
                                                    <input type="number" name="blocks[{{ $block->id }}][x_min]"
                                                           value="{{ $funcData['x_min'] ?? -10 }}"
                                                           class="input-ghost" style="width:100%;padding:6px 8px;" step="any">
                                                </div>
                                                <div style="flex:1;min-width:80px;">
                                                    <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">X Max</label>
                                                    <input type="number" name="blocks[{{ $block->id }}][x_max]"
                                                           value="{{ $funcData['x_max'] ?? 10 }}"
                                                           class="input-ghost" style="width:100%;padding:6px 8px;" step="any">
                                                </div>
                                            </div>
                                            <div class="function-input-row" style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;">
                                                <div style="flex:1;min-width:80px;">
                                                    <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Y Min</label>
                                                    <input type="number" name="blocks[{{ $block->id }}][y_min]"
                                                           value="{{ $funcData['y_min'] ?? -5 }}"
                                                           class="input-ghost" style="width:100%;padding:6px 8px;" step="any">
                                                </div>
                                                <div style="flex:1;min-width:80px;">
                                                    <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Y Max</label>
                                                    <input type="number" name="blocks[{{ $block->id }}][y_max]"
                                                           value="{{ $funcData['y_max'] ?? 5 }}"
                                                           class="input-ghost" style="width:100%;padding:6px 8px;" step="any">
                                                </div>
                                                <div style="flex:1;min-width:80px;">
                                                    <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Color</label>
                                                    <input type="color" name="blocks[{{ $block->id }}][color]"
                                                           value="{{ $funcData['color'] ?? '#4f46e5' }}"
                                                           style="width:100%;height:32px;border:none;cursor:pointer;">
                                                </div>
                                                <div style="flex:1;min-width:80px;">
                                                    <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Step</label>
                                                    <input type="number" name="blocks[{{ $block->id }}][step]"
                                                           value="{{ $funcData['step'] ?? 0.1 }}"
                                                           class="input-ghost" style="width:100%;padding:6px 8px;" step="0.01" min="0.01" max="1">
                                                </div>
                                            </div>
                                            <div class="function-preview" style="margin-top:12px;padding:12px;background:var(--bg-subtle);border-radius:6px;border:1px solid var(--border);">
                                                <canvas id="func-canvas-{{ $block->id }}" width="400" height="200" style="width:100%;max-width:100%;height:auto;background:var(--bg);border-radius:4px;"></canvas>
                                            </div>
                                            <small style="color:var(--text-faint);font-size:11px;display:block;margin-top:4px;">
                                                Use JavaScript math syntax: sin(x), cos(x), tan(x), x^2, sqrt(x), log(x), abs(x), etc.
                                            </small>
                                        </div>
                                        <input type="hidden" name="blocks[{{ $block->id }}][content]" class="function-content-hidden" value="{{ $block->content }}">
                                        @break

                                    @case('ext')
                                        <textarea name="blocks[{{ $block->id }}][content]" class="input-ghost content-style" placeholder="Paste HTML, iframe embed, or script code here..." rows="4" style="font-family:'JetBrains Mono', monospace;font-size:12px;background:#0d1117;color:#e2e8f0;">{{ $block->content }}</textarea>
                                        <small style="color:var(--text-faint);font-size:11px;display:block;margin-top:4px;">⚠️ Raw HTML - Be careful with external scripts</small>
                                        @break



                                    @default
                                        <textarea name="blocks[{{ $block->id }}][content]" class="input-ghost content-style" rows="1">{{ $block->content }}</textarea>
                                @endswitch
                            </div>

                            <div class="block-controls">
                                <div class="control-group">
                                    <span class="control-icon" onclick="toggleTypeSelect('{{ $block->id }}')">✏️</span>
                                    <select onchange="updateBlockType(this)" name="blocks[{{ $block->id }}][type]" id="select-{{ $block->id }}" class="mini-type-select">
                                        <option value="header" {{ $block->type == 'header' ? 'selected' : '' }}>H1</option>
                                        <option value="description" {{ $block->type == 'description' ? 'selected' : '' }}>Text</option>
                                        <option value="note" {{ $block->type == 'note' ? 'selected' : '' }}>Note</option>
                                        <option value="code" {{ $block->type == 'code' ? 'selected' : '' }}>Code</option>
                                        <option value="exercise" {{ $block->type == 'exercise' ? 'selected' : '' }}>Exercise</option>
                                        <option value="photo" {{ $block->type == 'photo' ? 'selected' : '' }}>Photo</option>
                                        <option value="video" {{ $block->type == 'video' ? 'selected' : '' }}>Video</option>
                                        <option value="function" {{ $block->type == 'function' ? 'selected' : '' }}>Function</option>
                                        <option value="math" {{ $block->type == 'math' ? 'selected' : '' }}>Math</option>
                                        <option value="graph" {{ $block->type == 'graph' ? 'selected' : '' }}>Graph</option>
                                        <option value="table" {{ $block->type == 'table' ? 'selected' : '' }}>Table</option>
                                        <option value="ext" {{ $block->type == 'ext' ? 'selected' : '' }}>HTML/Ext</option>

                                    </select>
                                </div>

                                <button type="button" value="{{ $block->id }}:up" class="arrow-btn">∧</button>
                                <button type="button" value="{{ $block->id }}:down" class="arrow-btn">∨</button>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <p>No content here yet. Click the <strong>+</strong> button to add a block.</p>
                        </div>
                    @endforelse
                </div>

                <div class="save-container">
                    <button type="submit" class="btn-save-all">Save All Changes</button>
                </div>

            </form>
        </div>

        <div class="block-adder-container">
            <button id="block-adder" class="fab-button"
                    data-url="{{ route('admin.courses.chapters.lessons.blocks.store', [$course->id, $chapter->id, $lesson->id]) }}"
                    type="button" onclick="createHeaderBlock()">+</button>
        </div>

        <div id="block-popup" class="modal-overlay">
            <div class="modal-content">
                <span class="close-btn" onclick="closeModal('block-popup')">&times;</span>
                <h3>Add New Content Block</h3>
                <form method="POST" action="{{route('admin.courses.chapters.lessons.blocks.store', [$course->id, $chapter->id, $lesson->id])}}" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label>Block Type</label>
                        <select name="type" class="modal-input" id="new-block-type" onchange="toggleNewBlockFields(this)">
                            <option value="header">Header</option>
                            <option value="description">Description</option>
                            <option value="note">Note</option>
                            <option value="code">Code</option>
                            <option value="exercise">Exercise</option>
                            <option value="photo">Photo</option>
                            <option value="video">Video</option>
                            <option value="math">Math (LaTeX)</option>
                            <option value="graph">Graph/Chart</option>
                            <option value="table">Table</option>
                            <option value="function">Math Function</option>
                            <option value="ext">HTML/Embed</option>
                        </select>
                    </div>

                    {{-- Text content field (shown for most types) --}}
                    <div class="form-group" id="text-content-group">
                        <label>Initial Content</label>
                        <textarea class="modal-input" name="content" rows="4"></textarea>
                    </div>

                    {{-- File upload for photo/video --}}
                    <div class="form-group" id="file-content-group" style="display:none;">
                        <label>Upload File</label>
                        <input type="file" name="content_file" class="modal-input" style="padding:8px;">
                    </div>

                    {{-- Graph specific fields --}}
                    <div class="form-group" id="graph-content-group" style="display:none;">
                        <label>Chart Type</label>
                        <select name="chart_type" class="modal-input" style="margin-bottom:8px;">
                            <option value="line">Line Chart</option>
                            <option value="bar">Bar Chart</option>
                            <option value="pie">Pie Chart</option>
                        </select>
                        <label style="margin-top:12px;">Chart Data</label>
                        <textarea name="chart_data" class="modal-input" rows="3" placeholder="Jan, Feb, Mar&#10;10, 20, 15">Jan, Feb, Mar&#10;10, 20, 15</textarea>
                        <small style="color:var(--text-faint);font-size:11px;">Line 1: Labels | Line 2: Values (comma separated)</small>
                    </div>

                    {{-- Table specific fields --}}
                    <div class="form-group" id="table-content-group" style="display:none;">
                        <label>Initial Table (JSON format)</label>
                        <textarea name="table_data" class="modal-input" rows="4">[["Header 1","Header 2"],["Row 1 Col 1","Row 1 Col 2"]]</textarea>
                        <small style="color:var(--text-faint);font-size:11px;">Format: [["Header1","Header2"],["Row1Col1","Row1Col2"]]</small>
                    </div>

                    {{-- Function specific fields --}}
                    <div class="form-group" id="function-content-group" style="display:none;">
                        <label>Function f(x)</label>
                        <input type="text" name="func_expression" class="modal-input" value="sin(x)" placeholder="e.g., sin(x), x^2, cos(x)*x" style="font-family:'JetBrains Mono',monospace;margin-bottom:8px;">
                        <div style="display:flex;gap:8px;margin-bottom:8px;">
                            <div style="flex:1;">
                                <label style="font-size:11px;color:var(--text-faint);">X Range</label>
                                <div style="display:flex;gap:4px;">
                                    <input type="number" name="x_min" value="-10" class="modal-input" style="flex:1;" placeholder="Min">
                                    <input type="number" name="x_max" value="10" class="modal-input" style="flex:1;" placeholder="Max">
                                </div>
                            </div>
                            <div style="flex:1;">
                                <label style="font-size:11px;color:var(--text-faint);">Y Range</label>
                                <div style="display:flex;gap:4px;">
                                    <input type="number" name="y_min" value="-5" class="modal-input" style="flex:1;" placeholder="Min">
                                    <input type="number" name="y_max" value="5" class="modal-input" style="flex:1;" placeholder="Max">
                                </div>
                            </div>
                        </div>
                        <label style="font-size:11px;color:var(--text-faint);">Line Color</label>
                        <input type="color" name="func_color" value="#4f46e5" class="modal-input" style="height:40px;padding:4px;">
                        <small style="color:var(--text-faint);font-size:11px;">JavaScript math syntax supported</small>
                    </div>

                    <div class="form-group">
                        <label>Block Number</label>
                        <input style="visibility: hidden" class="modal-input" type="number" name="block_number" value="{{ $lesson->blocks->count() + 1 }}" min="1" required>
                    </div>

                    <button type="submit" class="btn-update">Create Block</button>
                </form>
            </div>
        </div>
    @endfragment
@endsection

@section('sidebar-elements')

    <div class="bulk-actions" style="padding: 10px; border-bottom: 1px solid #ddd;">
        <form action="{{ route('admin.courses.chapters.publish-all', $course->id) }}" method="POST" id="master-toggle-form">
            @csrf
            @method('PUT')
            <button type="submit" id="master-toggle-btn" class="btn-publish-all">
                Loading...
            </button>
        </form>
    </div>

    <livewire:modular_site.chapters :course="$course" :chapters="$chapters"/>





    <div class="chapter-group add-chapter-trigger">
        <div class="chapter-header add-header" onclick="openModal('add-chapter-modal')">
            <div class="header-left">
                <span class="plus-icon-lg">+</span>
                <strong class="chapter-title">Add New Chapter</strong>
            </div>
        </div>
    </div>

    <livewire:modular_site.chaptercreate :course="$course"/>


{{--    <div id="add-chapter-modal" class="modal-overlay">--}}
{{--        <div class="modal-content">--}}
{{--            <span class="close-btn" onclick="closeModal('add-chapter-modal')">&times;</span>--}}
{{--            <h3>Create New Chapter</h3>--}}
{{--            <form onsubmit="createChapter(event, this)" id="new-block-form" method="POST" action="{{route('admin.courses.chapters.store', $course)}}">--}}
{{--                @csrf--}}
{{--                <div class="form-group">--}}
{{--                    <label>Title:</label>--}}
{{--                    <input class="modal-input" type="text" name="title" required>--}}
{{--                </div>--}}
{{--                <div class="form-group">--}}
{{--                    <label>Chapter Number:</label>--}}
{{--                    <input class="modal-input" type="number" name="chapter_number" value="{{$chapter_count+1}}" readonly>--}}
{{--                </div>--}}
{{--                <div class="form-group">--}}
{{--                    <label>Description:</label>--}}
{{--                    <textarea class="modal-input" name="description" style="height:100px;" required></textarea>--}}
{{--                </div>--}}
{{--                <div class="form-group">--}}
{{--                    <label>Initial Status:</label>--}}
{{--                    <select name="status" class="modal-input">--}}
{{--                        <option value="draft" selected>Draft (Hidden)</option>--}}
{{--                        <option value="published">Published (Live)</option>--}}
{{--                    </select>--}}
{{--                </div>--}}
{{--                <button type="submit" class="btn-update">Create Chapter</button>--}}
{{--            </form>--}}
{{--        </div>--}}
{{--    </div>--}}
@endsection


@section('js')
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            initAutoResize();

            // --- 1. CORE UTILITIES ---
            const COURSE_ID = "{{ $course->id }}";
            const savedUrl = localStorage.getItem('activeLessonUrl');

            const SIDEBAR_SCROLL_KEY = `sidebarScroll_${COURSE_ID}`;

            const sidebar = document.querySelector('.sidebar-content'); // adjust selector if needed
            console.log(sidebar)
            if (sidebar) {
                // Restore

                const savedSidebarScroll = localStorage.getItem(SIDEBAR_SCROLL_KEY);

                if (savedSidebarScroll !== null) {
                    requestAnimationFrame(() => {
                        sidebar.scrollTop = parseInt(savedSidebarScroll, 10);
                    });
                }

                // Save
                sidebar.addEventListener('scroll', () => {
                    localStorage.setItem(SIDEBAR_SCROLL_KEY, sidebar.scrollTop);
                });
            }

            const MAIN_SCROLL_KEY = `mainScroll_${COURSE_ID}`;
            const mainContainer = document.querySelector('main');

// Restore
            const savedMainScroll = localStorage.getItem(MAIN_SCROLL_KEY);
            if (savedMainScroll !== null && mainContainer) {
                mainContainer.scrollTop = parseInt(savedMainScroll, 10);
            }

            let lastSave = 0;
            mainContainer.addEventListener('scroll', () => {
                const now = Date.now();

                if (now - lastSave > 50) { // every 50ms
                    localStorage.setItem(MAIN_SCROLL_KEY, mainContainer.scrollTop);
                    lastSave = now;
                }
            });

            if (savedUrl) {
                // 🔒 Ensure the saved lesson belongs to this course
                if (!savedUrl.includes(`/courses/${COURSE_ID}/`)) {
                    localStorage.removeItem('activeLessonUrl');
                } else {
                    fetch(savedUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(res => {
                            if (!res.ok) throw new Error();
                            loadLesson(savedUrl);
                        })
                        .catch(() => {
                            localStorage.removeItem('activeLessonUrl');
                        });
                }
            }

            const openChapters = JSON.parse(localStorage.getItem('openChapters') || '[]');

            openChapters.forEach(id => {
                const container = document.getElementById('lessons-container-' + id);
                const arrow = document.getElementById('arrow-' + id);

                if (container) {
                    container.style.display = 'flex';
                }

                if (arrow) {
                    arrow.style.transform = 'rotate(90deg)';
                }
            });

            // Auto-resize textareas based on content
            function initAutoResize() {
                document.querySelectorAll('textarea.input-ghost').forEach(el => {
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            el.style.height = 'auto';
                            el.style.height = el.scrollHeight + 'px';
                        });
                    });
                });
            }

            // Initialize UI elements for the block editor
            // function initBlockEditor() {
            //     initAutoResize();
            //
            //     // Setup Floating Action Button
            //     const btn = document.getElementById('block-adder');
            //     const popup = document.getElementById('block-popup');
            //     const close = document.getElementById('close-popup');
            //
            //     if (btn && popup) btn.onclick = () => popup.style.display = 'flex';
            //     if (close && popup) close.onclick = () => popup.style.display = 'none';
            // }

            // --- 2. AJAX LESSON LOADING ---
            document.addEventListener('click', function(e) {
                const row = e.target.closest('.lesson-row');
                if (!row) return;

                if (e.target.closest('.pen-icon')) return;

                const url = row.dataset.href;
                if (!url) return;

                e.preventDefault();
                localStorage.setItem('activeLessonUrl', url);

                loadLesson(url);
            });


            function loadLesson(url) {
                const mainContainer = document.querySelector('main');
                mainContainer.style.opacity = '0.5';

                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Invalid lesson URL');
                        }
                        return response.text();
                    })
                    .then(html => {
                        mainContainer.innerHTML = html;
                        mainContainer.style.opacity = '1';
                        history.pushState(null, '', url);

                        // ✅ FIX: reinitialize after DOM replacement
                        // initBlockEditor();   // this calls initAutoResize()
                    })
                    .catch(() => {
                        localStorage.removeItem('activeLessonUrl');
                        mainContainer.style.opacity = '1';
                    });
            }

            // --- 3. GLOBAL WINDOW FUNCTIONS ---

            // Toggle the sidebar lesson lists
            window.toggleLessons = function(id) {
                const container = document.getElementById('lessons-container-' + id);
                const arrow = document.getElementById('arrow-' + id);
                if (!container) return;

                const isHidden = container.style.display === "none" || container.style.display === "";

                container.style.display = isHidden ? "flex" : "none";
                if (arrow) arrow.style.transform = isHidden ? "rotate(90deg)" : "rotate(0deg)";

                // ✅ NEW: persist state
                let openChapters = JSON.parse(localStorage.getItem('openChapters') || '[]');

                if (isHidden) {
                    // add
                    if (!openChapters.includes(id)) {
                        openChapters.push(id);
                    }
                } else {
                    // remove
                    openChapters = openChapters.filter(chId => chId !== id);
                }

                localStorage.setItem('openChapters', JSON.stringify(openChapters));
            };

            // Open/Close Modals
            window.openModal = function(id) {
                const modal = document.getElementById(id);
                if (modal) modal.style.display = 'flex';
            }

            window.closeModal = function(id) {
                const modal = document.getElementById(id);
                if (modal) modal.style.display = 'none';
            }

            // The Pen button toggles the type dropdown (Using ID)
            window.toggleTypeSelect = function(id) {
                const el = document.getElementById('select-' + id);
                if (el) el.classList.toggle('active');
            }

            // Close modals on background click
            window.onclick = function(event) {
                if (event.target.classList.contains('modal-overlay')) {
                    event.target.style.display = 'none';
                }
            }

            // --- 4. INITIALIZATION ---
            // initBlockEditor();
            window.onpopstate = () => loadLesson(window.location.href);



            document.addEventListener('click', function(e) {
                const row = e.target.closest('.lesson-row');
                if (!row) return;

                const mainContainer = document.querySelector('main');

                // ✅ FORCE save BEFORE navigation
                if (mainContainer) {
                    localStorage.setItem(MAIN_SCROLL_KEY, mainContainer.scrollTop);
                }
            });

            window.addEventListener('beforeunload', () => {
                const mainContainer = document.querySelector('main');
                if (mainContainer) {
                    localStorage.setItem(MAIN_SCROLL_KEY, mainContainer.scrollTop);
                }

                const sidebar = document.querySelector('.sidebar-content');
                if (sidebar) {
                    localStorage.setItem(SIDEBAR_SCROLL_KEY, sidebar.scrollTop);
                }
            });


            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    const mainContainer = document.querySelector('main');
                    if (mainContainer) {
                        localStorage.setItem(MAIN_SCROLL_KEY, mainContainer.scrollTop);
                    }
                }
            });


        });

        function updateBlockNumbers() {
            document.querySelectorAll('.blocks-list .block-row').forEach((block, index) => {
                const id = block.querySelector('input[name*="[id]"]').value;
                const blockNumberInput = block.querySelector(`input[name="blocks[${id}][block_number]"]`);
                if (blockNumberInput) blockNumberInput.value = index + 1;
            });
        }

        // --- 5. CLIENT-SIDE BLOCK REORDERING ---
        const blocksList = document.querySelector('.blocks-list');

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.arrow-btn');
            if (!btn) return;

            e.preventDefault(); // stop form submit

            const blockRow = btn.closest('.block-row');
            if (!blockRow) return;

            const parent = blockRow.parentNode;

            if (btn.value.endsWith(':up')) {
                const prev = blockRow.previousElementSibling;
                if (prev && prev.classList.contains('block-row')) {
                    parent.insertBefore(blockRow, prev); // swap
                }
            } else if (btn.value.endsWith(':down')) {
                const next = blockRow.nextElementSibling;
                if (next && next.classList.contains('block-row')) {
                    parent.insertBefore(next, blockRow); // swap
                }
            }

            // Update block numbers
            updateBlockNumbers();
        });


        function initBlockReordering() {
            document.querySelectorAll('.arrow-btn').forEach(btn => {
                btn.onclick = function(e) {
                    e.preventDefault();
                    const blockRow = btn.closest('.block-row');
                    const parent = blockRow.parentNode;

                    if (btn.value.endsWith(':up')) {
                        const prev = blockRow.previousElementSibling;
                        if (prev && prev.classList.contains('block-row')) {
                            parent.insertBefore(blockRow, prev);
                        }
                    } else if (btn.value.endsWith(':down')) {
                        const next = blockRow.nextElementSibling;
                        if (next && next.classList.contains('block-row')) {
                            parent.insertBefore(next, blockRow);
                        }
                    }

                    updateBlockNumbers();
                }
            });
        }


        function toggleSingleChapter(btn, event) {

            if (event) {
                event.stopPropagation();
            }


            const chapterId = btn.dataset.chapterId;
            const currentStatus = btn.dataset.status;
            const newStatus = (currentStatus === 'published') ? 'draft' : 'published';
            const courseId = "{{ $course->id }}"; // Blade variable

            // 1. Immediate UI Feedback (Oogabooga speed)
            const chapterNumber = btn.closest('.chapter-group')
                .querySelector('input[name="chapter_number"]')?.value;

            updateButtonUI(btn, newStatus);

            // 2. Send to Server
            axios.put(`/admin/courses/${courseId}/chapters/${chapterId}`, {
                status: newStatus,
                title: btn.closest('.chapter-group').querySelector('.chapter-title').innerText,
                description: "Updated via toggle",
                chapter_number: chapterNumber
            })
                .then(() => {
                    checkMasterToggle();
                })
                .catch(() => {
                    checkMasterToggle();
                });
        }

        function updateButtonUI(btn, status) {
            btn.dataset.status = status;
            btn.innerText = status.charAt(0).toUpperCase() + status.slice(1);
            btn.classList.remove('published', 'draft');
            btn.classList.add(status);
        }

        function checkMasterToggle() {
            const allBtns = document.querySelectorAll('.status-toggle-btn');
            const masterBtn = document.querySelector('.btn-publish-all');

            // Check if EVERY button has the 'published' class
            const allPublished = Array.from(allBtns).every(btn => btn.classList.contains('published'));

            if (allPublished) {
                masterBtn.classList.add('all-green');
                masterBtn.innerText = "🚀 All Published";
                masterBtn.style.background = "#2ecc71";
            } else {
                masterBtn.classList.remove('all-green');
                masterBtn.innerText = "📂 Publish All";
                masterBtn.style.background = "#95a5a6";
            }
        }

        // Run on page load
        document.addEventListener('DOMContentLoaded', checkMasterToggle);

        function updateMasterButtonUI() {
            const masterBtn = document.getElementById('master-toggle-btn');
            // Select all status buttons (Chapters + Lessons)
            const allBtns = document.querySelectorAll('.status-toggle-btn');

            if (!masterBtn || allBtns.length === 0) return;

            // Is there at least one "draft" button visible?
            const hasAnyDrafts = Array.from(allBtns).some(btn => btn.dataset.status === 'draft');

            if (hasAnyDrafts) {
                masterBtn.innerText = "🚀 Publish Everything";
                masterBtn.style.background = "#95a5a6"; // Gray-ish
                masterBtn.className = "btn-publish-all has-drafts";
            } else {
                masterBtn.innerText = "📂 Draft Everything";
                masterBtn.style.background = "#2ecc71"; // Green
                masterBtn.className = "btn-publish-all all-published";
            }
        }

        // Run it immediately on page load
        document.addEventListener('DOMContentLoaded', updateMasterButtonUI);

        function toggleSingleLesson(btn, event) {
            if (event) event.stopPropagation();

            const lessonId = btn.dataset.lessonId;
            const chapterId = btn.dataset.chapterId;
            const currentStatus = btn.dataset.status;
            const newStatus = currentStatus === 'published' ? 'draft' : 'published';
            const courseId = "{{ $course->id }}";

            updateButtonUI(btn, newStatus);

            axios.put(`/admin/courses/${courseId}/chapters/${chapterId}/lessons/${lessonId}`, {
                status: newStatus,
                title: btn.closest('.lesson-row').querySelector('.lesson-link').innerText,
                lesson_number: 1,
                description: "Status update"
            })
                .then(() => {
                    // sync UI if needed
                })
                .catch(() => {

                });
        }

        // If you use the AJAX toggle from the previous step,
        // make sure to call updateMasterButtonUI() inside the .then() block!

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.style.display = 'none';
        }

        function deleteLesson(event, courseId, chapterId, lessonId) {
            event.stopPropagation(); // 🔥 critical

            const url = `/admin/courses/${courseId}/chapters/${chapterId}/lessons/${lessonId}`;

            axios.delete(url)
                .then(() => {
                    document.querySelector(`[data-lesson-id="${lessonId}"]`)
                        ?.closest('.lesson-row')
                        ?.remove();

                    closeModal(`lesson-modal-${lessonId}`);
                })
                .catch((error) => {
                    document.querySelector(`[data-lesson-id="${lessonId}"]`)
                        ?.closest('.lesson-row')
                        ?.remove();

                    closeModal(`lesson-modal-${lessonId}`);

                });
        }

        function deleteChapter(event, btn) {
            event.stopPropagation();
            const url = btn.dataset.url;
            const chapterId = btn.dataset.chapterId;

            axios.delete(url)
                .then(() => {
                    const el = document.querySelector(`[data-chapter-id="${chapterId}"]`)
                        ?.closest('.chapter-group');

                    if (el) el.remove();

                    closeModal('chapter-modal-' + chapterId);
                })
                .catch(() => {
                    const el = document.querySelector(`[data-chapter-id="${chapterId}"]`)
                        ?.closest('.chapter-group');

                    if (el) el.remove();

                    closeModal('chapter-modal-' + chapterId);
                });
        }

        function updateChapter(event, form) {
            event.preventDefault();

            const url = form.action;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            const chapterId = url.match(/chapters\/(\d+)/)[1]; // 🔥 reliable extraction

            axios.put(url, data)
                .then(() => {
                    // Find the correct chapter in the sidebar
                    const chapterEl = document.querySelector(
                        `.chapter-group .status-toggle-btn[data-chapter-id="${chapterId}"]`
                    )?.closest('.chapter-group');

                    const titleEl = chapterEl?.querySelector('.chapter-title');

                    if (titleEl) {
                        titleEl.innerText = data.title;
                    }

                    closeModal(`chapter-modal-${chapterId}`);
                })
                .catch(() => {
                    const chapterEl = document.querySelector(
                        `.chapter-group .status-toggle-btn[data-chapter-id="${chapterId}"]`
                    )?.closest('.chapter-group');

                    const titleEl = chapterEl?.querySelector('.chapter-title');

                    if (titleEl) {
                        titleEl.innerText = data.title;
                    }

                    closeModal(`chapter-modal-${chapterId}`);
                });
        }

        function updateLesson(event, form) {
            event.preventDefault();

            const url = form.action;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            axios.put(url, data)
                .then(() => {
                    const modal = form.closest('.modal-overlay');
                    if (modal) modal.style.display = 'none';

                    // Optional: update UI title in sidebar
                    const lessonId = url.split('/').pop();
                    const row = document.querySelector(`[data-lesson-id="${lessonId}"]`)
                        ?.closest('.lesson-row');

                    if (row) {
                        row.querySelector('.lesson-link').innerText = data.title;
                    }
                })
                .catch(() => {
                    const modal = form.closest('.modal-overlay');
                    if (modal) modal.style.display = 'none';

                    // Optional: update UI title in sidebar
                    const lessonId = url.split('/').pop();
                    const row = document.querySelector(`[data-lesson-id="${lessonId}"]`)
                        ?.closest('.lesson-row');

                    if (row) {
                        row.querySelector('.lesson-link').innerText = data.title;
                    }
                });
        }

        function saveAllBlocks(event, form) {
            event.preventDefault();

            const url = form.action;
            const formData = new FormData(form);

            const saveBtn = form.querySelector('.btn-save-all');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.innerText = 'Saving...';
            }

            axios.post(url, formData, {
                headers: {
                    'X-HTTP-Method-Override': 'PUT',
                    'Content-Type': 'multipart/form-data'
                }
            })
                .then(() => {
                    if (saveBtn) {
                        saveBtn.innerText = 'Saved ✓';
                        saveBtn.style.background = ''; // Reset to default green
                        setTimeout(() => {
                            saveBtn.innerText = 'Save All Changes';
                            saveBtn.disabled = false;
                        }, 1500);
                    }
                })
                .catch(() => {
                    if (saveBtn) {
                        saveBtn.innerText = 'Save Failed';
                        saveBtn.style.background = '#ef4444'; // Red for error
                        saveBtn.disabled = false;
                    }
                    alert('Failed to save changes');
                });
        }

        function updateBlockType(select) {
            const blockRow = select.closest('.block-row');
            const blockId = blockRow.querySelector('input[name*="[id]"]').value;
            const newType = select.value;
            const oldType = blockRow.className.match(/type-(\w+)/)?.[1];

            if (newType === oldType) return;

            // Update visual class immediately
            blockRow.classList.remove(`type-${oldType}`);
            blockRow.classList.add(`type-${newType}`);

            // Get current content to preserve if possible
            const oldContent = blockRow.querySelector('textarea[name*="[content]"]')?.value || '';

            // Generate new HTML based on type
            const mainContent = blockRow.querySelector('.block-main-content');
            mainContent.innerHTML = generateBlockHTML(blockId, newType, oldContent);

            // Re-initialize any special handlers
            if (newType === 'math') {
                const textarea = mainContent.querySelector('.math-input');
                if (textarea) updateMathPreview(textarea);
            }
        }

        function generateBlockHTML(blockId, type, existingContent) {
            switch(type) {
                case 'header':
                    return `<textarea type="text" name="blocks[${blockId}][content]" class="input-ghost title-style" placeholder="Enter Title...">${existingContent}</textarea>`;

                case 'description':
                case 'note':
                case 'code':
                    return `<textarea name="blocks[${blockId}][content]" class="input-ghost content-style" rows="1" oninput="this.style.height = '';this.style.height = this.scrollHeight + 'px'">${existingContent}</textarea>`;

                case 'exercise':
                    return `
                <div class="exercise-container">
                    <label>Question:</label>
                    <textarea name="blocks[${blockId}][content]" class="input-ghost content-style" rows="1" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'">${existingContent}</textarea>
                    <label>Solution</label>
                    <textarea class="input-ghost content-style" oninput="this.style.height='';this.style.height=this.scrollHeight+'px'" name="blocks[${blockId}][solutions][new]" placeholder="nothing here yet"></textarea>
                </div>`;

                case 'photo':
                    return `
                <div class="file-block">
                    <input type="file" name="blocks[${blockId}][content_file]" accept="image/*" class="file-input" style="margin-top:8px;font-size:12px;">
                    <input type="hidden" name="blocks[${blockId}][content]" value="${existingContent}">
                    ${existingContent ? `<small style="color:var(--text-faint);">Current: ${existingContent.split('/').pop()}</small>` : ''}
                </div>`;

                case 'video':
                    return `
                <div class="file-block">
                    <input type="file" name="blocks[${blockId}][content_file]" accept="video/*" class="file-input" style="margin-top:8px;font-size:12px;">
                    <input type="hidden" name="blocks[${blockId}][content]" value="${existingContent}">
                    ${existingContent ? `<small style="color:var(--text-faint);">Current: ${existingContent.split('/').pop()}</small>` : ''}
                </div>`;

                case 'math':
                    return `
                <textarea name="blocks[${blockId}][content]" class="input-ghost content-style math-input" placeholder="Enter LaTeX (e.g., x^2 + y^2 = z^2)" oninput="updateMathPreview(this)" rows="2">${existingContent}</textarea>
                <div class="math-preview" style="margin-top:8px;padding:12px;background:var(--bg-subtle);border-radius:6px;border:1px solid var(--border);min-height:40px;font-family:'Times New Roman', serif;font-size:16px;">
                    ${existingContent ? '$' + existingContent + '$' : 'Preview will appear here...'}
                </div>`;

                case 'graph':
                    // Try to parse existing content as JSON, or use defaults
                    let graphData = {type: 'line', labels: ['Jan','Feb','Mar'], data: [10,20,15]};
                    try {
                        const parsed = JSON.parse(existingContent);
                        if (parsed && parsed.labels) graphData = parsed;
                    } catch(e) {}

                    return `
                <div class="graph-editor">
                    <select name="blocks[${blockId}][chart_type]" class="mini-type-select" style="margin-bottom:8px;width:auto;display:inline-block;">
                        <option value="line" ${graphData.type == 'line' ? 'selected' : ''}>Line Chart</option>
                        <option value="bar" ${graphData.type == 'bar' ? 'selected' : ''}>Bar Chart</option>
                        <option value="pie" ${graphData.type == 'pie' ? 'selected' : ''}>Pie Chart</option>
                    </select>
                    <textarea name="blocks[${blockId}][chart_data]" class="input-ghost content-style" placeholder="Labels: Jan, Feb, Mar (comma separated)&#10;Values: 10, 20, 15 (comma separated)" rows="3" style="font-family:monospace;font-size:12px;">${graphData.labels.join(',')}\n${graphData.data.join(',')}</textarea>
                    <small style="color:var(--text-faint);font-size:11px;">Line 1: Labels | Line 2: Values</small>
                </div>
                <input type="hidden" name="blocks[${blockId}][content]" value='${JSON.stringify(graphData)}'>`;

                case 'function':
                    let funcData = {
                        function: 'sin(x)',
                        x_min: -10,
                        x_max: 10,
                        y_min: -5,
                        y_max: 5,
                        color: '#4f46e5',
                        step: 0.1
                    };
                    try {
                        const parsed = JSON.parse(existingContent);
                        if (parsed && parsed.function) funcData = parsed;
                    } catch(e) {}

                    return `
                            <div class="function-editor" data-block-id="${blockId}">
                                <div class="function-input-row" style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;">
                                    <div style="flex:2;min-width:200px;">
                                        <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">f(x) =</label>
                                        <input type="text" name="blocks[${blockId}][func_expression]"
                                               value="${funcData.function}"
                                               class="input-ghost"
                                               style="width:100%;font-family:'JetBrains Mono',monospace;font-size:13px;padding:6px 8px;">
                                    </div>
                                    <div style="flex:1;min-width:80px;">
                                        <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">X Min</label>
                                        <input type="number" name="blocks[${blockId}][x_min]"
                                               value="${funcData.x_min}"
                                               class="input-ghost" style="width:100%;padding:6px 8px;" step="any">
                                    </div>
                                    <div style="flex:1;min-width:80px;">
                                        <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">X Max</label>
                                        <input type="number" name="blocks[${blockId}][x_max]"
                                               value="${funcData.x_max}"
                                               class="input-ghost" style="width:100%;padding:6px 8px;" step="any">
                                    </div>
                                </div>
                                <div class="function-input-row" style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;">
                                    <div style="flex:1;min-width:80px;">
                                        <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Y Min</label>
                                        <input type="number" name="blocks[${blockId}][y_min]"
                                               value="${funcData.y_min}"
                                               class="input-ghost" style="width:100%;padding:6px 8px;" step="any">
                                    </div>
                                    <div style="flex:1;min-width:80px;">
                                        <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Y Max</label>
                                        <input type="number" name="blocks[${blockId}][y_max]"
                                               value="${funcData.y_max}"
                                               class="input-ghost" style="width:100%;padding:6px 8px;" step="any">
                                    </div>
                                    <div style="flex:1;min-width:80px;">
                                        <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Color</label>
                                        <input type="color" name="blocks[${blockId}][color]"
                                               value="${funcData.color}"
                                               style="width:100%;height:32px;border:none;cursor:pointer;">
                                    </div>
                                    <div style="flex:1;min-width:80px;">
                                        <label style="font-size:11px;color:var(--text-faint);display:block;margin-bottom:2px;">Step</label>
                                        <input type="number" name="blocks[${blockId}][step]"
                                               value="${funcData.step}"
                                               class="input-ghost" style="width:100%;padding:6px 8px;" step="0.01" min="0.01" max="1">
                                    </div>
                                </div>
                                <div class="function-preview" style="margin-top:12px;padding:12px;background:var(--bg-subtle);border-radius:6px;border:1px solid var(--border);">
                                    <canvas id="func-canvas-${blockId}" width="400" height="200" style="width:100%;max-width:100%;height:auto;background:#ffffff;border-radius:4px;"></canvas>
                                </div>
                                <small style="color:var(--text-faint);font-size:11px;display:block;margin-top:4px;">
                                    Use JavaScript math syntax: sin(x), cos(x), tan(x), x^2, sqrt(x), log(x), abs(x), etc.
                                </small>
                            </div>
                            <input type="hidden" name="blocks[${blockId}][content]" class="function-content-hidden" value='${JSON.stringify(funcData)}'>
                            <script>
                                (function() {
                                    // Delay to ensure DOM is ready
                                    if (document.readyState === 'loading') {
                                        document.addEventListener('DOMContentLoaded', function() {
                                            setTimeout(() => renderFunctionPreview('${blockId}'), 50);
                                        });
                                    } else {
                                        setTimeout(() => renderFunctionPreview('${blockId}'), 50);
                                    }
                                })();
                            <\/script>`;


                case 'table':
                    let tableData = [['Header 1', 'Header 2'], ['Row 1', 'Row 2']];
                    try {
                        const parsed = JSON.parse(existingContent);
                        if (Array.isArray(parsed)) tableData = parsed;
                    } catch(e) {}

                    let tableHTML = '<table class="editable-table" style="width:100%;border-collapse:collapse;font-size:13px;">';
                    tableData.forEach((row, rIdx) => {
                        tableHTML += '<tr>';
                        row.forEach((cell, cIdx) => {
                            tableHTML += `<td style="border:1px solid var(--border);padding:0;min-width:80px;"><input type="text" name="blocks[${blockId}][table_data][${rIdx}][${cIdx}]" value="${cell}" style="width:100%;border:none;background:transparent;padding:8px;font-family:inherit;color:var(--text);" onchange="updateTableJSON(${blockId})"></td>`;
                        });
                        tableHTML += '</tr>';
                    });
                    tableHTML += '</table>';

                    return `
                <div class="table-editor" data-block-id="${blockId}">
                    <div class="table-actions" style="margin-bottom:8px;display:flex;gap:6px;">
                        <button type="button" onclick="addTableRow(${blockId})" class="arrow-btn" style="width:auto;padding:4px 10px;font-size:11px;">+ Row</button>
                        <button type="button" onclick="addTableCol(${blockId})" class="arrow-btn" style="width:auto;padding:4px 10px;font-size:11px;">+ Column</button>
                    </div>
                    <div style="overflow-x:auto;">
                        ${tableHTML}
                    </div>
                </div>
                <input type="hidden" name="blocks[${blockId}][content]" class="table-content-hidden" value='${JSON.stringify(tableData)}'>`;

                case 'ext':
                    return `
                <textarea name="blocks[${blockId}][content]" class="input-ghost content-style" placeholder="Paste HTML, iframe embed, or script code here..." rows="4" style="font-family:'JetBrains Mono', monospace;font-size:12px;background:#0d1117;color:#e2e8f0;">${existingContent}</textarea>
                <small style="color:var(--text-faint);font-size:11px;display:block;margin-top:4px;">⚠️ Raw HTML - Be careful with external scripts</small>`;

                default:
                    return `<textarea name="blocks[${blockId}][content]" class="input-ghost content-style" rows="1">${existingContent}</textarea>`;
            }
        }



        function createLesson(event, form) {
            event.preventDefault();

            const url = form.action;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            axios.post(url, data)
                .then((response) => {
                    const lesson = response.data.lesson;
                    const chapterId = response.data.chapter_id;
                    const courseId = "{{ $course->id }}";

                    const container = document.getElementById(`lessons-container-${chapterId}`);

                    const newLesson = document.createElement('div');
                    newLesson.classList.add('lesson-row');
                    newLesson.dataset.href = `/admin/courses/${courseId}/chapters/${chapterId}/lessons/${lesson.id}`;

                    newLesson.innerHTML = `
            <div class="lesson-content">
                <span class="bullet">•</span>
                <a class="lesson-link">${lesson.title}</a>

                <button type="button"
                    class="status-toggle-btn lesson-status ${lesson.status}"
                    data-lesson-id="${lesson.id}"
                    data-chapter-id="${chapterId}"
                    data-status="${lesson.status}"
                    onclick="toggleSingleLesson(this, event)">
                    ${lesson.status.charAt(0).toUpperCase() + lesson.status.slice(1)}
                </button>
            </div>
            <span class="pen-icon lesson-pen">✏️</span>
        `;

                    const addRow = container.querySelector('.add-lesson-row');
                    container.insertBefore(newLesson, addRow);

                    closeModal(`add-lesson-modal-${chapterId}`);

                    form.reset();
                    window.location.reload();
                })
                .catch((error) => {



                    const lesson = response.data.lesson;
                    const chapterId = response.data.chapter_id;
                    const courseId = "{{ $course->id }}";

                    const container = document.getElementById(`lessons-container-${chapterId}`);

                    const newLesson = document.createElement('div');
                    newLesson.classList.add('lesson-row');
                    newLesson.dataset.href = `/admin/courses/${courseId}/chapters/${chapterId}/lessons/${lesson.id}`;

                    newLesson.innerHTML = `
            <div class="lesson-content">
                <span class="bullet">•</span>
                <a class="lesson-link">${lesson.title}</a>

                <button type="button"
                    class="status-toggle-btn lesson-status ${lesson.status}"
                    data-lesson-id="${lesson.id}"
                    data-chapter-id="${chapterId}"
                    data-status="${lesson.status}"
                    onclick="toggleSingleLesson(this, event)">
                    ${lesson.status.charAt(0).toUpperCase() + lesson.status.slice(1)}
                </button>
            </div>
            <span class="pen-icon lesson-pen">✏️</span>
        `;

                    container.appendChild(newLesson);

                    closeModal(`add-lesson-modal-${chapterId}`);
                    form.reset();
                    window.location.reload();
                });
        }

        document.getElementById('master-toggle-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = this;
            const masterBtn = document.getElementById('master-toggle-btn');
            masterBtn.disabled = true;
            masterBtn.innerText = 'Processing...';

            const allChapters = document.querySelectorAll('.status-toggle-btn[data-chapter-id]');
            const allLessons = document.querySelectorAll('.status-toggle-btn[data-lesson-id]');

            // Determine target status (if any draft exists -> publish all, else draft all)
            const hasDrafts = Array.from(allChapters).concat(Array.from(allLessons))
                .some(btn => btn.dataset.status === 'draft');
            const newStatus = hasDrafts ? 'published' : 'draft';

            // Update all UI buttons immediately
            allChapters.forEach(btn => updateButtonUI(btn, newStatus));
            allLessons.forEach(btn => updateButtonUI(btn, newStatus));

            // Send AJAX PUT requests for each chapter
            const courseId = "{{ $course->id }}";
            const chapterRequests = Array.from(allChapters).map(btn => {
                const chapterId = btn.dataset.chapterId;
                const chapterGroup = btn.closest('.chapter-group');
                const title = chapterGroup.querySelector('.chapter-title').innerText;
                const chapterNumber = chapterGroup.querySelector('input[name="chapter_number"]')?.value || 1;
                return axios.put(`/admin/courses/${courseId}/chapters/${chapterId}`, {
                    status: newStatus,
                    title: title,
                    description: 'Bulk status update',
                    chapter_number: chapterNumber
                });
            });

            // AJAX PUT for lessons
            const lessonRequests = Array.from(allLessons).map(btn => {
                const lessonId = btn.dataset.lessonId;
                const chapterId = btn.dataset.chapterId;
                const title = btn.closest('.lesson-row').querySelector('.lesson-link').innerText;
                return axios.put(`/admin/courses/${courseId}/chapters/${chapterId}/lessons/${lessonId}`, {
                    status: newStatus,
                    title: title,
                    description: 'Bulk status update',
                    lesson_number: 1
                });
            });

            // Wait for all requests
            Promise.all([...chapterRequests, ...lessonRequests])
                .then(() => {
                    masterBtn.disabled = false;
                    updateMasterButtonUI();
                })
                .catch(() => {
                    masterBtn.disabled = false;
                    updateMasterButtonUI();
                });
        });

        function createHeaderBlock() {
            const btn = document.getElementById('block-adder');
            const typeSelect = document.querySelector('#block-popup select[name="type"]');
            const selectedType = typeSelect ? typeSelect.value : 'header';

            btn.disabled = true;
            btn.innerText = '...';

            const url = btn.dataset.url;
            const blockCount = document.querySelectorAll('.blocks-list .block-row').length;

            // Prepare data based on type
            const formData = new FormData();
            formData.append('type', selectedType);
            formData.append('block_number', blockCount + 1);

            // Set appropriate default content based on type
            let defaultContent = '';
            if (selectedType === 'graph') {
                defaultContent = JSON.stringify({type: 'line', labels: ['Jan','Feb','Mar'], data: [10,20,15]});
                formData.append('chart_type', 'line');
                formData.append('chart_data', "Jan,Feb,Mar\n10,20,15");
            } else if (selectedType === 'table') {
                defaultContent = JSON.stringify([['Header 1', 'Header 2'], ['Row 1', 'Row 2']]);
                formData.append('table_data', JSON.stringify([['Header 1', 'Header 2'], ['Row 1', 'Row 2']]));
            } else if (selectedType === 'photo' || selectedType === 'video') {
                const fileInput = document.querySelector('#block-popup input[name="content_file"]');
                if (fileInput && fileInput.files[0]) {
                    formData.append('content_file', fileInput.files[0]);
                }
                defaultContent = '';

            }else if (selectedType === 'function') {
                    defaultContent = JSON.stringify({
                        function: document.querySelector('#block-popup input[name="func_expression"]')?.value || 'sin(x)',
                        x_min: parseFloat(document.querySelector('#block-popup input[name="x_min"]')?.value) || -10,
                        x_max: parseFloat(document.querySelector('#block-popup input[name="x_max"]')?.value) || 10,
                        y_min: parseFloat(document.querySelector('#block-popup input[name="y_min"]')?.value) || -5,
                        y_max: parseFloat(document.querySelector('#block-popup input[name="y_max"]')?.value) || 5,
                        color: document.querySelector('#block-popup input[name="func_color"]')?.value || '#4f46e5',
                        step: 0.1
                    });
                    formData.append('content', defaultContent);

            } else {
                defaultContent = document.querySelector('#block-popup textarea[name="content"]')?.value || 'New content';
                formData.append('content', defaultContent);
            }

            axios.post(url, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
                .then((response) => {
                    const block = response.data.block;
                    const list = document.querySelector('.blocks-list');

                    // Generate the HTML for the selected type
                    const contentHTML = generateBlockHTML(block.id, selectedType, block.content || defaultContent);

                    const optionsHTML = `
            <option value="header" ${selectedType == 'header' ? 'selected' : ''}>H1</option>
            <option value="description" ${selectedType == 'description' ? 'selected' : ''}>Text</option>
            <option value="note" ${selectedType == 'note' ? 'selected' : ''}>Note</option>
            <option value="code" ${selectedType == 'code' ? 'selected' : ''}>Code</option>
            <option value="exercise" ${selectedType == 'exercise' ? 'selected' : ''}>Exercise</option>
            <option value="photo" ${selectedType == 'photo' ? 'selected' : ''}>Photo</option>
            <option value="video" ${selectedType == 'video' ? 'selected' : ''}>Video</option>
            <option value="math" ${selectedType == 'math' ? 'selected' : ''}>Math</option>
            <option value="graph" ${selectedType == 'graph' ? 'selected' : ''}>Graph</option>
            <option value="table" ${selectedType == 'table' ? 'selected' : ''}>Table</option>
            <option value="ext" ${selectedType == 'ext' ? 'selected' : ''}>HTML/Ext</option>
            <option value="function" ${selectedType == 'function' ? 'selected' : ''}>Function</option>
        `;

                    const newRow = document.createElement('div');
                    newRow.classList.add('block-row', `type-${selectedType}`);
                    newRow.innerHTML = `
            <input type="hidden" name="blocks[${block.id}][id]" value="${block.id}">
            <input type="hidden" name="blocks[${block.id}][block_number]" value="${block.block_number}">

            <div class="block-main-content">
                ${contentHTML}
            </div>

            <div class="block-controls">
                <div class="control-group">
                    <span class="control-icon" onclick="toggleTypeSelect('${block.id}')">✏️</span>
                    <select onchange="updateBlockType(this)" name="blocks[${block.id}][type]" id="select-${block.id}" class="mini-type-select">
                        ${optionsHTML}
                    </select>
                </div>
                <button type="button" value="${block.id}:up" class="arrow-btn">∧</button>
                <button type="button" value="${block.id}:down" class="arrow-btn">∨</button>
            </div>
        `;

                    // Remove empty state if present
                    list.querySelector('.empty-state')?.remove();
                    list.appendChild(newRow);

                    // Focus the new input
                    const firstInput = newRow.querySelector('textarea, input[type="text"]');
                    if (firstInput) firstInput.focus();

                    updateBlockNumbers();

                    // Close modal and reset
                    closeModal('block-popup');
                    if (typeSelect) typeSelect.value = 'header';
                    toggleNewBlockFields(typeSelect);

                    // Show "unsaved changes" indicator if you have one
                    const saveBtn = document.querySelector('.btn-save-all');
                    if (saveBtn) {
                        saveBtn.style.background = '#f59e0b'; // Orange to indicate unsaved
                        saveBtn.innerText = 'Save Changes *';
                    }
                })
                .catch(() => {
                    alert('Failed to create block.');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerText = '+';
                });
        }

        // Table management functions
        function addTableRow(blockId) {
            const container = document.querySelector(`.table-editor[data-block-id="${blockId}"]`);
            const table = container.querySelector('table');
            const colCount = table.rows[0]?.cells.length || 2;
            const rowIndex = table.rows.length;

            const newRow = document.createElement('tr');
            for(let i=0; i<colCount; i++) {
                newRow.innerHTML += `<td style="border:1px solid var(--border);padding:0;"><input type="text" name="blocks[${blockId}][table_data][${rowIndex}][${i}]" value="" style="width:100%;border:none;background:transparent;padding:8px;" onchange="updateTableJSON(${blockId})"></td>`;
            }
            table.appendChild(newRow);
            updateTableJSON(blockId);
        }

        function addTableCol(blockId) {
            const container = document.querySelector(`.table-editor[data-block-id="${blockId}"]`);
            const rows = container.querySelectorAll('tr');
            rows.forEach((row, idx) => {
                const colIndex = row.cells.length;
                const td = document.createElement('td');
                td.style.cssText = 'border:1px solid var(--border);padding:0;';
                td.innerHTML = `<input type="text" name="blocks[${blockId}][table_data][${idx}][${colIndex}]" value="" style="width:100%;border:none;background:transparent;padding:8px;" onchange="updateTableJSON(${blockId})">`;
                row.appendChild(td);
            });
            updateTableJSON(blockId);
        }

        function updateTableJSON(blockId) {
            const container = document.querySelector(`.table-editor[data-block-id="${blockId}"]`);
            const rows = container.querySelectorAll('tr');
            const data = [];
            rows.forEach(row => {
                const rowData = [];
                row.querySelectorAll('input').forEach(input => rowData.push(input.value));
                if(rowData.some(cell => cell !== '')) data.push(rowData);
            });
            container.nextElementSibling.value = JSON.stringify(data);
        }

        // Math preview updater (simple text representation)
        function updateMathPreview(textarea) {
            const preview = textarea.nextElementSibling;
            const val = textarea.value.trim();
            preview.textContent = val ? `$ ${val} $` : 'Preview will appear here...';
        }

        // Update the updateBlockType function to handle new types


        // Toggle fields in new block modal based on type

            function toggleNewBlockFields(select) {
                const type = select.value;
                const textGroup = document.getElementById('text-content-group');
                const fileGroup = document.getElementById('file-content-group');
                const graphGroup = document.getElementById('graph-content-group');
                const tableGroup = document.getElementById('table-content-group');
                const functionGroup = document.getElementById('function-content-group');  // ← ADD THIS

                // Hide all first
                textGroup.style.display = 'none';
                fileGroup.style.display = 'none';
                graphGroup.style.display = 'none';
                tableGroup.style.display = 'none';
                functionGroup.style.display = 'none';

                // Show relevant
                if (type === 'photo' || type === 'video') {
                    fileGroup.style.display = 'flex';
                    fileGroup.style.flexDirection = 'column';
                } else if (type === 'graph') {
                    graphGroup.style.display = 'flex';
                    graphGroup.style.flexDirection = 'column';
                } else if (type === 'table') {
                    tableGroup.style.display = 'flex';
                    tableGroup.style.flexDirection = 'column';
                } else if (type === 'function') {  // ← FIXED: Proper condition
                    functionGroup.style.display = 'flex';
                    functionGroup.style.flexDirection = 'column';
                } else {
                    textGroup.style.display = 'flex';
                    textGroup.style.flexDirection = 'column';
                }
            }

        // Function graph rendering
        // Function graph rendering
        function renderFunctionPreview(blockId) {
            const container = document.querySelector(`.function-editor[data-block-id="${blockId}"]`);
            if (!container) return;

            const canvas = document.getElementById(`func-canvas-${blockId}`);
            if (!canvas) return;

            // Force canvas size
            canvas.width = 400;
            canvas.height = 200;

            const ctx = canvas.getContext('2d');
            const width = canvas.width;
            const height = canvas.height;

            // Get values with fallbacks
            const funcExprInput = container.querySelector('input[name*="[func_expression]"]');
            const xMinInput = container.querySelector('input[name*="[x_min]"]');
            const xMaxInput = container.querySelector('input[name*="[x_max]"]');
            const yMinInput = container.querySelector('input[name*="[y_min]"]');
            const yMaxInput = container.querySelector('input[name*="[y_max]"]');
            const colorInput = container.querySelector('input[name*="[color]"]');
            const stepInput = container.querySelector('input[name*="[step]"]');

            const funcExpr = funcExprInput ? funcExprInput.value || 'sin(x)' : 'sin(x)';
            const xMin = parseFloat(xMinInput ? xMinInput.value : -10) || -10;
            const xMax = parseFloat(xMaxInput ? xMaxInput.value : 10) || 10;
            const yMin = parseFloat(yMinInput ? yMinInput.value : -5) || -5;
            const yMax = parseFloat(yMaxInput ? yMaxInput.value : 5) || 5;
            const color = colorInput ? colorInput.value || '#4f46e5' : '#4f46e5';
            const step = parseFloat(stepInput ? stepInput.value : 0.1) || 0.1;

            // Clear canvas with white background (fallback if CSS var fails)
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, width, height);

            // Try to get CSS variables, fallback to defaults
            const rootStyles = getComputedStyle(document.documentElement);
            const bgColor = rootStyles.getPropertyValue('--bg').trim() || '#ffffff';
            const borderColor = rootStyles.getPropertyValue('--border').trim() || '#e5e7eb';
            const textFaintColor = rootStyles.getPropertyValue('--text-faint').trim() || '#9ca3af';

            // Redraw background with CSS var if available
            ctx.fillStyle = bgColor;
            ctx.fillRect(0, 0, width, height);

            // Draw grid
            ctx.strokeStyle = borderColor;
            ctx.lineWidth = 1;
            ctx.beginPath();

            // Vertical grid lines
            for (let i = 0; i <= 10; i++) {
                const x = (i / 10) * width;
                ctx.moveTo(x, 0);
                ctx.lineTo(x, height);
            }
            // Horizontal grid lines
            for (let i = 0; i <= 10; i++) {
                const y = (i / 10) * height;
                ctx.moveTo(0, y);
                ctx.lineTo(width, y);
            }
            ctx.stroke();

            // Draw axes
            ctx.strokeStyle = textFaintColor;
            ctx.lineWidth = 2;
            ctx.beginPath();

            // X-axis (y=0)
            const yZero = height - ((0 - yMin) / (yMax - yMin)) * height;
            if (yZero >= 0 && yZero <= height) {
                ctx.moveTo(0, yZero);
                ctx.lineTo(width, yZero);
            }

            // Y-axis (x=0)
            const xZero = ((0 - xMin) / (xMax - xMin)) * width;
            if (xZero >= 0 && xZero <= width) {
                ctx.moveTo(xZero, 0);
                ctx.lineTo(xZero, height);
            }
            ctx.stroke();

            // Prepare function - handle empty or invalid input
            if (!funcExpr || funcExpr.trim() === '') {
                // Update hidden input even if empty
                updateFunctionHiddenInput(container, funcExpr, xMin, xMax, yMin, yMax, color, step);
                return;
            }

            const funcStr = funcExpr
                .replace(/\^/g, '**')
                .replace(/sin/g, 'Math.sin')
                .replace(/cos/g, 'Math.cos')
                .replace(/tan/g, 'Math.tan')
                .replace(/sqrt/g, 'Math.sqrt')
                .replace(/log/g, 'Math.log')
                .replace(/abs/g, 'Math.abs')
                .replace(/pi/g, 'Math.PI')
                .replace(/e(?![a-z])/g, 'Math.E');

            // Draw function
            ctx.strokeStyle = color;
            ctx.lineWidth = 3;
            ctx.beginPath();

            let firstPoint = true;
            let hasValidPoint = false;

            for (let x = xMin; x <= xMax; x += step) {
                let y;
                try {
                    // Use Function constructor for safer eval
                    const fn = new Function('x', `return ${funcStr}`);
                    y = fn(x);
                } catch (e) {
                    continue;
                }

                if (!isFinite(y) || isNaN(y)) continue;

                const canvasX = ((x - xMin) / (xMax - xMin)) * width;
                const canvasY = height - ((y - yMin) / (yMax - yMin)) * height;

                // Skip points outside canvas bounds for line continuity
                if (canvasY < -1000 || canvasY > height + 1000) {
                    firstPoint = true;
                    continue;
                }

                if (firstPoint) {
                    ctx.moveTo(canvasX, canvasY);
                    firstPoint = false;
                    hasValidPoint = true;
                } else {
                    ctx.lineTo(canvasX, canvasY);
                }
            }
            ctx.stroke();

            // Update hidden input
            updateFunctionHiddenInput(container, funcExpr, xMin, xMax, yMin, yMax, color, step);
        }

        // Helper function to update hidden input
        function updateFunctionHiddenInput(container, funcExpr, xMin, xMax, yMin, yMax, color, step) {
            const hiddenInput = container.nextElementSibling;
            if (hiddenInput && hiddenInput.classList.contains('function-content-hidden')) {
                hiddenInput.value = JSON.stringify({
                    function: funcExpr,
                    x_min: xMin,
                    x_max: xMax,
                    y_min: yMin,
                    y_max: yMax,
                    color: color,
                    step: step
                });
            }
        }

        // Auto-render on input change
        document.addEventListener('input', function(e) {
            if (e.target.closest('.function-editor')) {
                const blockId = e.target.closest('.function-editor').dataset.blockId;
                // Debounce
                clearTimeout(window.funcRenderTimeout);
                window.funcRenderTimeout = setTimeout(() => renderFunctionPreview(blockId), 100);
            }
        });

        // Initial render for existing function blocks
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for styles to load
            setTimeout(() => {
                document.querySelectorAll('.function-editor').forEach(editor => {
                    renderFunctionPreview(editor.dataset.blockId);
                });
            }, 100);
        });
    </script>

@endsection

