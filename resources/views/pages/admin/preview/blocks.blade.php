@extends('layouts.edditor')
@include('components._markdown_block_renderer')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/modular-site-preview.css') }}">
    <link rel="stylesheet" href="{{ asset('css/block-page.css') }}">


    <link rel="stylesheet" href="{{ asset('vendors/katex/katex.min.css') }}">
    <style>
        /* ── Photo & Video blocks ── */
        .block-media {
            margin: 1.5rem 0;
            border-radius: 10px;
            overflow: hidden;
            background: var(--bg-subtle, #f8f9fa);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            border: 1px solid var(--border, #e5e7eb);
        }
        .block-media img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            display: block;
            cursor: zoom-in;
            transition: transform 0.2s;
        }
        .block-media img:hover { transform: scale(1.01); }
        .block-media video {
            max-width: 100%;
            width: 100%;
            border-radius: 8px;
            display: block;
            background: #000;
        }
        .block-media-caption {
            font-size: 0.78rem;
            color: var(--text-faint, #9ca3af);
            text-align: center;
        }

        /* ── Math (LaTeX) block ── */
        .block-math {
            margin: 1.5rem 0;
            padding: 1rem 1.25rem;
            background: var(--bg-subtle, #f8f9fa);
            border-left: 3px solid var(--accent, #4f46e5);
            border-radius: 0 8px 8px 0;
            font-family: 'Times New Roman', serif;
            font-size: 1.1rem;
            overflow-x: auto;
        }

        /* ── Graph (Chart.js) block ── */
        .block-graph {
            margin: 1.5rem 0;
            padding: 1rem;
            background: var(--bg-subtle, #f8f9fa);
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 10px;
        }
        .block-graph canvas {
            max-width: 100%;
            height: 280px !important;
        }

        /* ── Function plot block ── */
        .block-function {
            margin: 1.5rem 0;
            padding: 1rem;
            background: var(--bg-subtle, #f8f9fa);
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 10px;
        }
        .block-function canvas {
            width: 100% !important;
            height: auto;
            border-radius: 6px;
            display: block;
        }

        /* ── Table block ── */
        .block-table {
            margin: 1.5rem 0;
            overflow-x: auto;
        }
        .block-table table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .block-table th,
        .block-table td {
            padding: 0.6rem 0.9rem;
            border: 1px solid var(--border, #e5e7eb);
            text-align: left;
        }
        .block-table tr:first-child th,
        .block-table tr:first-child td {
            background: var(--bg-subtle, #f3f4f6);
            font-weight: 600;
        }
        .block-table tr:nth-child(even) td {
            background: var(--bg-alt, #fafafa);
        }

        /* ── Ext (raw HTML embed) block ── */
        .block-ext {
            margin: 1.5rem 0;
        }
        .block-ext iframe,
        .block-ext embed,
        .block-ext object {
            max-width: 100%;
            border-radius: 8px;
            border: 1px solid var(--border, #e5e7eb);
        }
    </style>

    <style>
        /* ── Code Runner Block ── */
        .code-runner-block {
            margin: 1.5rem 0;
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 10px;
            overflow: hidden;
            background: #0d1117;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
        }

        .crb-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #161b22;
            border-bottom: 1px solid #30363d;
        }

        .crb-lang-badge {
            font-size: 11px;
            font-weight: 700;
            color: #79c0ff;
            background: #1f3548;
            padding: 2px 8px;
            border-radius: 4px;
            letter-spacing: .05em;
        }

        .crb-ver-badge {
            font-size: 11px;
            color: #8b949e;
            background: #21262d;
            padding: 2px 7px;
            border-radius: 4px;
        }

        .crb-run-btn {
            background: #238636;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 5px 14px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
            letter-spacing: .03em;
        }
        .crb-run-btn:hover { background: #2ea043; }
        .crb-run-btn:disabled { background: #484f58; cursor: not-allowed; }

        .crb-copy-btn {
            background: transparent;
            color: #8b949e;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 11px;
            cursor: pointer;
        }
        .crb-copy-btn:hover { color: #e6edf3; border-color: #8b949e; }

        .crb-code-wrap {
            overflow-x: auto;
            padding: 16px;
        }

        .crb-pre {
            margin: 0;
            white-space: pre;
            font-size: 13px;
            line-height: 1.65;
            color: #e6edf3;
        }

        /* ── Terminal ── */
        .crb-terminal-wrap {
            border-top: 1px solid #30363d;
            background: #010409;
        }

        .crb-terminal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 12px;
            background: #0d1117;
            border-bottom: 1px solid #21262d;
            font-size: 11px;
            color: #8b949e;
            letter-spacing: .05em;
        }

        .crb-clear-btn {
            background: none;
            border: none;
            color: #8b949e;
            font-size: 11px;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .crb-clear-btn:hover { color: #e6edf3; background: #21262d; }

        .crb-output {
            min-height: 80px;
            max-height: 320px;
            overflow-y: auto;
            padding: 10px 14px;
            font-size: 13px;
            line-height: 1.55;
            color: #e6edf3;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .crb-line-stdout { color: #e6edf3; }
        .crb-line-stderr { color: #ff7b72; }
        .crb-line-info   { color: #8b949e; font-style: italic; }
        .crb-line-success{ color: #3fb950; }
        .crb-line-stdin  { color: #79c0ff; }

        .crb-stdin-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px 8px;
            border-top: 1px solid #21262d;
            background: #0d1117;
        }

        .crb-prompt { color: #3fb950; font-weight: bold; font-size: 14px; }

        .crb-stdin-input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: #79c0ff;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            caret-color: #3fb950;
        }

        .crb-send-btn {
            background: #21262d;
            border: 1px solid #30363d;
            border-radius: 4px;
            color: #8b949e;
            cursor: pointer;
            padding: 3px 8px;
            font-size: 13px;
        }
        .crb-send-btn:hover { color: #e6edf3; }

        .crb-status {
            padding: 4px 14px 6px;
            font-size: 11px;
            color: #8b949e;
            min-height: 20px;
        }

        .crb-spinner { display: inline-block; animation: spin .7s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
@endsection

@section('progress-bar')
    <div id="scroll-progress"></div>
@endsection

@section('sidebar-elements')
    <div class="sb-course-head">
        <div class="sb-course-label">Chapter</div>
        <div class="sb-chapter-name">{{ $chapter->title }}</div>
        <div class="sb-ch-progress">
            <div class="sb-ch-prog-label">
                <span>Chapter progress</span>
                <span>{{ $chapter->progressForUser($id) }}%</span>
            </div>
            <div class="sb-ch-bar">
                <div class="sb-ch-fill" style="width: {{ $chapter->progressForUser($id) }}%"></div>
            </div>
        </div>
    </div>

    <nav class="lesson-nav-list">
        @foreach($chapter->lessons as $i => $lesson_item)
            @if($lesson_item->status === 'published')
                @php
                    $lp = $lesson_item->progressForUser($id);
                    $isDone = $lp && $lp->progress >= 90;
                @endphp
                <a class="lesson-nav-item {{ $lesson_item->id === $lesson->id ? 'active' : '' }}"
                   href="{{ route('admin.preview.blocks', ['course'=>$course,'chapter'=>$chapter,'lesson'=>$lesson_item]) }}">
                    <span class="lesson-nav-num">{{ $chapter->chapter_number }}.{{ $i+1 }}</span>
                    <span class="lesson-nav-title">{{ $lesson_item->title }}</span>
                    <span class="lesson-nav-check {{ $isDone ? 'lnc-done' : 'lnc-none' }}">
                {{ $isDone ? '✓' : '' }}
            </span>
                </a>
            @endif
        @endforeach
    </nav>

    <div class="sb-lesson-nav">
        @if($prevlesson)
            <a class="sb-nav-btn"
               href="{{ route('admin.preview.blocks', ['course'=>$course,'chapter'=>$chapter,'lesson'=>$prevlesson]) }}">
                ‹ Prev
            </a>
        @elseif($prevchapter)
            <a class="sb-nav-btn"
               href="{{ route('admin.preview.lessons', ['course'=>$course,'chapter'=>$prevchapter]) }}">
                ‹ Prev chapter
            </a>
        @else
            <span class="sb-nav-btn disabled">‹ Prev</span>
        @endif

        @if($nextlesson)
            <a class="sb-nav-btn"
               href="{{ route('admin.preview.blocks', ['course'=>$course,'chapter'=>$chapter,'lesson'=>$nextlesson]) }}">
                Next ›
            </a>
        @elseif($nextchapter)
            <a class="sb-nav-btn"
               href="{{ route('admin.preview.lessons', ['course'=>$course,'chapter'=>$nextchapter]) }}">
                Next chapter ›
            </a>
        @else
            <a class="sb-nav-btn"
               href="{{ route('admin.preview.chapters', ['course'=>$course]) }}">
                Back to course ›
            </a>
        @endif
    </div>
@endsection

@section('navigation')
    <div class="nav-box">


        <div class="navigation">
            <a href="{{ route('admin.preview.courses') }}">{{ $course->year }}-{{ $course->branch }}</a>
            <span>›</span>
            <a href="{{ route('admin.preview.chapters', ['course'=>$course]) }}">{{ $chapter->title }}</a>
            <span>›</span>
            <span style="color:var(--text);font-weight:500;">{{ $lesson->title }}</span>
        </div>

        <div class="lesson-complete">
            <label>
                <input class="completed_checkbox" type="checkbox" disabled
                       @if($lesson_progress && $lesson_progress->progress >= 90) checked @endif>
                {{ ($lesson_progress && $lesson_progress->progress >= 90) ? 'Lesson completed ✓' : 'Complete by scrolling to the end' }}
            </label>
        </div>
    </div>
@endsection

@section('main')


    <div class="lesson-wrapper">

        {{-- Prev nav button --}}
        @if($prevlesson)
            <div class="nav-button">
                <a href="{{ route('admin.preview.blocks',['course'=>$course,'chapter'=>$chapter,'lesson'=>$prevlesson]) }}">‹</a>
            </div>
        @elseif($prevchapter)
            <div class="nav-button">
                <a href="{{ route('admin.preview.lessons',['course'=>$course,'chapter'=>$prevchapter]) }}" title="Previous chapter">«</a>
            </div>
        @else
            <div class="nav-button" style="visibility:hidden;"><a>‹</a></div>
        @endif

        <div class="blocks-container">
            <div class="preview" id="preview">
                @foreach($blocks as $block)
                    @switch($block->type)
                        @case('markdown')
                            <div class="block-markdown-view" data-md="{{ e($block->content) }}"></div>
                            @break

                        @case('header')
                            <h1>{{ $block->content }}</h1>
                            @break

                        @case('description')
                            <p>{{ $block->content }}</p>
                            @break

                        @case('note')
                            <div class="note">{{ $block->content }}</div>
                            @break

                        @case('code')
                            @php
                                $codeData = json_decode($block->content ?? '', true);
                                if (!is_array($codeData)) {
                                    $codeData = ['language' => 'javascript', 'version' => '*', 'code' => $block->content ?? ''];
                                }
                                $codeLang    = $codeData['language'] ?? 'javascript';
                                $codeVersion = $codeData['version']  ?? '*';
                                $codeBody    = $codeData['code']     ?? '';
                            @endphp

                            <div class="code-runner-block" data-block-id="{{ $block->id }}">

                                {{-- Header bar --}}
                                <div class="crb-header">
                                    <span class="crb-lang-badge">{{ strtoupper($codeLang) }}</span>
                                    <span class="crb-ver-badge">v{{ $codeVersion }}</span>
                                    <div style="flex:1"></div>
                                    <button class="crb-run-btn" onclick="crbRun({{ $block->id }})">
                                        ▶ Run
                                    </button>
                                    <button class="crb-copy-btn" onclick="crbCopy({{ $block->id }})">Copy</button>
                                </div>

                                {{-- Code display (read-only, syntax highlighted) --}}
                                <div class="crb-code-wrap">
                                    <pre class="crb-pre"><code class="crb-code" data-block-id="{{ $block->id }}">{{ $codeBody }}</code></pre>
                                </div>

                                {{-- Terminal output area (hidden until first Run) --}}
                                <div class="crb-terminal-wrap" id="crb-terminal-{{ $block->id }}" style="display:none;">

                                    <div class="crb-terminal-header">
                                        <span>▸ Terminal</span>
                                        <button class="crb-clear-btn" onclick="crbClear({{ $block->id }})">Clear</button>
                                    </div>

                                    {{-- Output lines --}}
                                    <div class="crb-output" id="crb-output-{{ $block->id }}"></div>

                                    {{-- Stdin input row (shown only when program asks for input) --}}
                                    <div class="crb-stdin-row" id="crb-stdin-row-{{ $block->id }}" style="display:none;">
                                        <span class="crb-prompt">›</span>
                                        <input
                                            type="text"
                                            class="crb-stdin-input"
                                            id="crb-stdin-{{ $block->id }}"
                                            placeholder="Type input and press Enter…"
                                            onkeydown="if(event.key==='Enter') crbSendStdin({{ $block->id }})"
                                            autocomplete="off"
                                            spellcheck="false"
                                        >
                                        <button class="crb-send-btn" onclick="crbSendStdin({{ $block->id }})">↵</button>
                                    </div>

                                    {{-- Status bar --}}
                                    <div class="crb-status" id="crb-status-{{ $block->id }}"></div>
                                </div>

                            </div>
                            @break

                        @case('exercise')
                            <div class="exercise">
                                <strong>{{ $block->content }}</strong>
                                <button class="toggle-solution" data-blockid="{{ $block->id }}">
                                    Show solution
                                </button>
                                @if(count($block->solutions) === 0)
                                    <div class="solution solution-{{ $block->id }}">No solution added yet.</div>
                                @else
                                    @foreach($block->solutions as $solution)
                                        <div class="solution solution-{{ $block->id }}">{{ $solution->content }}</div>
                                    @endforeach
                                @endif
                            </div>
                            @break

                        @case('list')
                            @php $listData = json_decode($block->content, true); @endphp
                            @if($listData && !empty($listData['items']))
                                <div class="block-list" style="margin: 1.5rem 0; padding: 0 0.5rem;">
                                    @if(($listData['style'] ?? 'bullet') === 'numbered')
                                        <ol style="margin: 0; padding-left: 1.5rem; color: var(--text); line-height: 1.7;">
                                            @foreach($listData['items'] as $item)
                                                <li style="margin-bottom: 0.4rem;">{{ $item }}</li>
                                            @endforeach
                                        </ol>
                                    @elseif(($listData['style'] ?? '') === 'checklist')
                                        <ul style="margin: 0; padding-left: 0.5rem; list-style: none; color: var(--text);">
                                            @foreach($listData['items'] as $item)
                                                <li style="margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border: 2px solid var(--border); border-radius: 4px; background: var(--bg); flex-shrink: 0;">☐</span>
                                                    <span>{{ $item }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <ul style="margin: 0; padding-left: 1.5rem; color: var(--text); line-height: 1.7; list-style-type: disc;">
                                            @foreach($listData['items'] as $item)
                                                <li style="margin-bottom: 0.4rem;">{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endif
                            @break

                        @case('separator')
                            @php $sepData = json_decode($block->content, true); @endphp
                            @if(($sepData['type'] ?? 'divider') === 'page_break')
                                <div class="block-separator page-break" style="margin: 2rem 0; border: 2px dashed var(--border); padding: 1rem; text-align: center; color: var(--text-faint); font-size: 0.85rem; border-radius: 8px; background: var(--bg-subtle); page-break-after: always;">
                                    <span style="letter-spacing: 0.2em; text-transform: uppercase;">Page Break</span>
                                </div>
                            @elseif(($sepData['type'] ?? '') === 'section_break')
                                <div class="block-separator section-break" style="margin: 3rem 0; display: flex; align-items: center; gap: 1rem;">
                                    <div style="flex: 1; height: 1px; background: linear-gradient(to right, transparent, var(--border), transparent);"></div>
                                    <span style="color: var(--text-faint); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.15em;">§</span>
                                    <div style="flex: 1; height: 1px; background: linear-gradient(to right, transparent, var(--border), transparent);"></div>
                                </div>
                            @else
                                <div class="block-separator divider" style="margin: 2rem 0;">
                                    <hr style="border: none; border-top: 1px solid var(--border); opacity: 0.6;">
                                </div>
                            @endif
                            @break

                        @case('photo')
                            @if($block->content)
                                <div style="margin: 20px 0;">
                                    <img src="{{ asset('storage/' . $block->content) }}" style="max-width: 100%; border-radius: 8px; border: 1px solid var(--border);">
                                </div>
                            @endif
                            @break

                        @case('video')
                            @if($block->content)
                                <div style="margin: 20px 0;">
                                    <video controls style="max-width: 100%; border-radius: 8px; border: 1px solid var(--border);">
                                        <source src="{{ asset('storage/' . $block->content) }}" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                            @endif
                            @break

                        @case('math')
                            <div style="margin: 20px 0; padding: 20px; background: var(--bg-subtle); border-radius: 8px; border-left: 4px solid #e11d48; overflow-x: auto;">
                                <div style="font-family: 'Times New Roman', Times, serif; font-size: 18px; font-style: italic; text-align: center;">
                                    $${{ $block->content }}$$
                                </div>

                            </div>
                            @break

                        @case('graph')
                            @php $graphData = json_decode($block->content, true); @endphp
                            @if($graphData)
                                <div style="margin: 20px 0; padding: 20px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px;">
                                    <canvas id="chart-{{ $block->id }}" width="400" height="200" style="max-width:100%;"></canvas>
                                </div>
                                <script>
                                    (function() {
                                        var ctx = document.getElementById('chart-{{ $block->id }}');
                                        if(ctx && typeof Chart !== 'undefined') {
                                            new Chart(ctx, {
                                                type: '{{ $graphData['type'] ?? 'line' }}',
                                                data: {
                                                    labels: {!! json_encode($graphData['labels'] ?? []) !!},
                                                    datasets: [{
                                                        label: 'Values',
                                                        data: {!! json_encode($graphData['data'] ?? []) !!},
                                                        borderColor: '#4f46e5',
                                                        backgroundColor: '{{ $graphData['type'] == 'pie' ? json_encode(['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']) : 'rgba(79, 70, 229, 0.1)' }}',
                                                        tension: 0.4
                                                    }]
                                                },
                                                options: {
                                                    responsive: true,
                                                    maintainAspectRatio: true,
                                                    plugins: { legend: { display: {{ ($graphData['type'] ?? 'line') == 'pie' ? 'true' : 'false' }} } }
                                                }
                                            });
                                        }
                                    })();
                                </script>
                            @endif
                            @break

                        @case('function')
                            @php $funcData = json_decode($block->content, true); @endphp
                            @if($funcData)
                                <div class="func-block-preview"
                                     style="margin:20px 0;padding:16px;background:var(--bg);
                    border:1px solid var(--border);border-radius:10px;">
                                    {{-- equation label (KaTeX rendered if available) --}}
                                    <div class="func-eq-label"
                                         style="font-family:'JetBrains Mono',monospace;font-size:13px;
                        color:var(--text);margin-bottom:10px;padding:6px 12px;
                        background:var(--bg-subtle);border-radius:5px;
                        display:inline-block;border:1px solid var(--border);">
                <span class="katex-eq" data-eq="{{ htmlspecialchars($funcData['function'] ?? '') }}">
                    {{ $funcData['function'] ?? '' }}
                </span>
                                    </div>
                                    <canvas id="preview-func-{{ $block->id }}"
                                            style="width:100%;height:auto;display:block;border-radius:6px;
                           background:var(--bg);">
                                    </canvas>
                                </div>
                                <script>
                                    (function(){
                                        const funcData = {!! json_encode($funcData) !!};
                                        window._funcBlocks = window._funcBlocks || [];
                                        window._funcBlocks.push({ id: '{{ $block->id }}', data: funcData });
                                    })();
                                </script>
                            @endif
                            @break

                        @case('table')
                            @php $tableData = json_decode($block->content, true); @endphp
                            @if($tableData && count($tableData) > 0)
                                <div style="margin: 20px 0; overflow-x: auto;">
                                    <table style="width: 100%; border-collapse: collapse; border: 1px solid var(--border); font-size: 14px;">
                                        @foreach($tableData as $rowIndex => $row)
                                            <tr style="{{ $rowIndex === 0 ? 'background: var(--bg-subtle); font-weight: 600;' : 'background: var(--bg);' }}">
                                                @foreach($row as $cell)
                                                    <td style="border: 1px solid var(--border); padding: 12px; text-align: left;">{{ $cell }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            @endif
                            @break

                        @case('ext')
                            <div style="margin: 20px 0; padding: 16px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; overflow-x: auto;">
                                {!! $block->content !!}
                            </div>
                            @break

                    @endswitch
                @endforeach
            </div>
        </div>

        @if($nextlesson)
            <div class="nav-button">
                <a href="{{ route('admin.preview.blocks',['course'=>$course,'chapter'=>$chapter,'lesson'=>$nextlesson]) }}">›</a>
            </div>
        @endif
    </div>

    <form id="progress-form" method="POST"
          action="{{ route('user.lesson.progress.store', ['lesson'=>$lesson]) }}"
          style="display:none;">
        @csrf
        <input type="hidden" name="lesson_id" value="{{ $lesson->id }}">
        <input type="hidden" name="progress" id="progress-input"
               value="{{ $lesson_progress ? $lesson_progress->progress : 0 }}">
        <button type="submit">Send</button>
    </form>
@endsection

@section('js')

    <script>
        // ─────────────────────────────────────────────────────────────
        //  Code Runner Block — student-facing runtime
        //
        //  Strategy:
        //  1. For programs that need NO stdin (most demos): single POST to /code/run
        //  2. For programs with scanf/input(): we run in two passes —
        //     first run collects prompts, then we ask for input,
        //     then re-run with that stdin pre-loaded.
        //
        //  True PTY-level interactivity requires WebSockets + a server-side
        //  process manager (Phase 2). This version handles 90% of cases
        //  including scanf with pre-collected stdin.
        // ─────────────────────────────────────────────────────────────

        const _crbState = {};

        function crbGetState(id) {
            if (!_crbState[id]) {
                _crbState[id] = {
                    running:   false,
                    stdinBuffer: [],
                    stdinResolve: null,
                };
            }
            return _crbState[id];
        }

        function crbPrintLine(id, text, cls = 'crb-line-stdout') {
            const output = document.getElementById(`crb-output-${id}`);
            if (!output) return;

            const div = document.createElement('div');
            div.className = cls;
            div.textContent = text;
            output.appendChild(div);
            output.scrollTop = output.scrollHeight;
        }

        function crbSetStatus(id, text) {
            const el = document.getElementById(`crb-status-${id}`);
            if (el) el.innerHTML = text;
        }

        function crbShowTerminal(id) {
            const wrap = document.getElementById(`crb-terminal-${id}`);
            if (wrap) wrap.style.display = 'block';
        }

        function crbClear(id) {
            const output = document.getElementById(`crb-output-${id}`);
            if (output) output.innerHTML = '';
            crbSetStatus(id, '');
            const stdinRow = document.getElementById(`crb-stdin-row-${id}`);
            if (stdinRow) stdinRow.style.display = 'none';
        }

        function crbCopy(id) {
            const code = document.querySelector(`.crb-code[data-block-id="${id}"]`);
            if (code) navigator.clipboard.writeText(code.innerText);
        }

        // Show stdin input and wait for user to press Enter
        function crbRequestStdin(id, promptText) {
            return new Promise(resolve => {
                const state = crbGetState(id);
                state.stdinResolve = resolve;

                crbShowTerminal(id);
                if (promptText) crbPrintLine(id, promptText, 'crb-line-info');

                const row = document.getElementById(`crb-stdin-row-${id}`);
                const inp = document.getElementById(`crb-stdin-${id}`);
                if (row) row.style.display = 'flex';
                if (inp) { inp.value = ''; inp.focus(); }
            });
        }

        function crbSendStdin(id) {
            const inp   = document.getElementById(`crb-stdin-${id}`);
            const state = crbGetState(id);
            const value = inp ? inp.value : '';

            if (inp) inp.value = '';

            // Echo what the user typed
            crbPrintLine(id, '› ' + value, 'crb-line-stdin');

            // Hide input row
            const row = document.getElementById(`crb-stdin-row-${id}`);
            if (row) row.style.display = 'none';

            // Collect into buffer and resolve the promise
            state.stdinBuffer.push(value);

            if (state.stdinResolve) {
                state.stdinResolve(value);
                state.stdinResolve = null;
            }
        }

        async function crbRun(id) {
            const state = crbGetState(id);
            if (state.running) return;

            const codeEl = document.querySelector(`.crb-code[data-block-id="${id}"]`);
            const blockEl = document.querySelector(`.code-runner-block[data-block-id="${id}"]`);
            if (!codeEl || !blockEl) return;

            // Grab language/version from badges
            const lang    = blockEl.querySelector('.crb-lang-badge')?.textContent?.trim()?.toLowerCase() || 'javascript';
            const verText = blockEl.querySelector('.crb-ver-badge')?.textContent?.trim() || '*';
            const version = verText.startsWith('v') ? verText.slice(1) : verText;
            const code    = codeEl.innerText;

            // Find correct version from Piston runtimes
            let resolvedVersion = version;
            if (window._lessonRuntimes) {
                const match = window._lessonRuntimes.find(r =>
                    r.language.toLowerCase() === lang.toLowerCase()
                );
                if (match) resolvedVersion = match.version;
            }

            state.running = true;
            state.stdinBuffer = [];
            crbShowTerminal(id);
            crbClear(id);

            const runBtn = blockEl.querySelector('.crb-run-btn');
            if (runBtn) runBtn.disabled = true;

            crbPrintLine(id, `Running ${lang}…`, 'crb-line-info');
            crbSetStatus(id, '<span class="crb-spinner">⟳</span> Executing…');

            try {
                // ── Phase 1: dry run with empty stdin to detect input prompts ──
                const phase1 = await fetch('/code/run', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        language: lang,
                        version:  resolvedVersion,
                        code:     code,
                        stdin:    '',
                    }),
                });
                const result1 = await phase1.json();

                // Compile error check
                if (result1.compile?.code !== 0 && result1.compile?.stderr) {
                    crbPrintLine(id, '── Compile Error ──', 'crb-line-info');
                    result1.compile.stderr.split('\n').forEach(l => crbPrintLine(id, l, 'crb-line-stderr'));
                    crbSetStatus(id, '✗ Compile error');
                    return;
                }

                // If stdout contains text and no empty-stdin-caused crash, and program exited 0 → done
                const needsInput = result1.code !== 0 || result1.stdout === '' || result1.stderr.includes('EOF');

                if (!needsInput) {
                    // Program ran fine without stdin
                    if (result1.stdout) {
                        result1.stdout.split('\n').forEach(l => crbPrintLine(id, l, 'crb-line-stdout'));
                    }
                    if (result1.stderr) {
                        result1.stderr.split('\n').forEach(l => crbPrintLine(id, l, 'crb-line-stderr'));
                    }
                    const exitColor = result1.code === 0 ? 'crb-line-success' : 'crb-line-stderr';
                    crbPrintLine(id, `── Exited with code ${result1.code} ──`, exitColor);
                    crbSetStatus(id, result1.code === 0 ? '✓ Done' : `✗ Exit code ${result1.code}`);
                    return;
                }

                // ── Phase 2: program needs stdin — collect input from user ──
                // Show any stdout the program printed before blocking
                if (result1.stdout) {
                    result1.stdout.split('\n').forEach(l => {
                        if (l.trim()) crbPrintLine(id, l, 'crb-line-stdout');
                    });
                }

                // Estimate how many stdin lines needed by counting common prompt patterns
                const promptCount = Math.max(1,
                    (result1.stdout.match(/[:?]\s*$/gm) || []).length
                );

                const stdinLines = [];
                for (let i = 0; i < promptCount; i++) {
                    const promptLine = result1.stdout.split('\n').filter(l => l.trim())[i] || 'Input:';
                    const val = await crbRequestStdin(id, i === 0 ? null : promptLine);
                    stdinLines.push(val);
                }

                crbPrintLine(id, '', 'crb-line-info'); // blank line separator
                crbSetStatus(id, '<span class="crb-spinner">⟳</span> Re-running with input…');

                // ── Phase 3: re-run with collected stdin ──
                const phase3 = await fetch('/code/run', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        language: lang,
                        version:  resolvedVersion,
                        code:     code,
                        stdin:    stdinLines.join('\n'),
                    }),
                });
                const result3 = await phase3.json();

                if (result3.stdout) {
                    result3.stdout.split('\n').forEach(l => crbPrintLine(id, l, 'crb-line-stdout'));
                }
                if (result3.stderr) {
                    result3.stderr.split('\n').forEach(l => crbPrintLine(id, l, 'crb-line-stderr'));
                }

                const exitColor3 = result3.code === 0 ? 'crb-line-success' : 'crb-line-stderr';
                crbPrintLine(id, `── Exited with code ${result3.code} ──`, exitColor3);
                crbSetStatus(id, result3.code === 0 ? '✓ Done' : `✗ Exit code ${result3.code}`);

            } catch (e) {
                crbPrintLine(id, 'Error: ' + e.message, 'crb-line-stderr');
                crbSetStatus(id, '✗ Network error');
            } finally {
                state.running = false;
                if (runBtn) runBtn.disabled = false;
            }
        }

        // ── Pre-load runtimes for lesson page (for version resolution) ──
        (async () => {
            try {
                const res  = await fetch('/code/runtimes');
                const data = await res.json();
                if (Array.isArray(data)) window._lessonRuntimes = data;
            } catch {}
        })();
    </script>






    <script src="{{ asset('js/function.js') }}"></script>


    <script>
        // ── Solution toggle ──
        document.querySelectorAll('.toggle-solution').forEach(btn => {
            const blockId   = btn.dataset.blockid;
            const solutions = document.querySelectorAll(`.solution-${blockId}`);
            solutions.forEach(s => s.style.display = 'none');

            btn.addEventListener('click', () => {
                const hidden = solutions[0].style.display === 'none';
                solutions.forEach(s => s.style.display = hidden ? 'block' : 'none');
                btn.textContent = hidden ? 'Hide solution' : 'Show solution';
                btn.classList.toggle('revealed', hidden);
            });
        });

        // ── Copy code buttons ──
        document.querySelectorAll('.preview pre').forEach(pre => {
            const btn = document.createElement('button');
            btn.className = 'copy-code-btn';
            btn.textContent = 'Copy';
            pre.style.position = 'relative';
            pre.appendChild(btn);
            btn.addEventListener('click', () => {
                const code = pre.querySelector('code');
                navigator.clipboard.writeText(code.innerText).then(() => {
                    btn.textContent = 'Copied!';
                    setTimeout(() => btn.textContent = 'Copy', 2000);
                });
            });
        });

        // ── Scroll progress + lesson completion ──
        let maxProgress = 0;
        let sent = document.querySelector('.completed_checkbox').checked;
        const main = document.querySelector('main');

        main.addEventListener('scroll', () => {
            const scrollable = main.scrollHeight - main.clientHeight;
            if (scrollable <= 0) return;

            const progress = (main.scrollTop / scrollable) * 100;

            if (progress > maxProgress) {
                maxProgress = progress;
                document.getElementById('scroll-progress').style.width = maxProgress + '%';
            }

            if (maxProgress >= 90 && !sent) {
                sent = true;
                document.getElementById('progress-input').value = Math.round(maxProgress);
                document.getElementById('progress-form').submit();
            }
        });

        // ── Restore scroll position ──
        document.addEventListener('DOMContentLoaded', () => {
            const key   = `lessonScroll_{{ $lesson->id }}`;
            const saved = localStorage.getItem(key);
            if (saved && main) main.scrollTop = parseInt(saved);
            if (main) {
                main.addEventListener('scroll', () => {
                    localStorage.setItem(key, main.scrollTop);
                });
            }
        });

        document.addEventListener("DOMContentLoaded", function() {
            // THIS IS THE TRIGGER YOU ARE MISSING
            renderMathInElement(document.body, {
                delimiters: [
                    {left: '$$', right: '$$', display: true},
                    {left: '$', right: '$', display: false},
                    {left: '\\(', right: '\\)', display: false},
                    {left: '\\[', right: '\\]', display: true}
                ],
                throwOnError : false
            });

            // Your existing KaTeX logic for function blocks
            document.querySelectorAll('.katex-eq').forEach(el => {
                const eq = el.getAttribute('data-eq');
                if (eq) {
                    katex.render(eq, el, { throwOnError: false });
                }
            });
        });
    </script>
@endsection


@once
    <script src="{{ asset('vendors/marked.min.js') }}"
            onerror="document.head.insertAdjacentHTML('beforeend',
              '<script src=\'https://cdn.jsdelivr.net/npm/marked@9/marked.min.js\'><\/script>')">
    </script>

    <script>
        window.MathJax = {
            tex: {
                inlineMath:  [['$','$'], ['\\(','\\)']],
                displayMath: [['$$','$$'], ['\\[','\\]']],
                processEscapes: true,
            },
            options: {
                skipHtmlTags: ['script','noscript','style','textarea','pre'],
            },
            startup: { typeset: false },
        };
    </script>
    <script src="{{ asset('vendors/mathjax/tex-chtml.js') }}"
            onerror="document.head.insertAdjacentHTML('beforeend',
              '<script src=\'https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.js\'><\/script>')">
    </script>

    <style>
        .block-markdown-view {
            font-family: 'Geist', sans-serif;
            font-size: 15px;
            line-height: 1.75;
            color: var(--text);
            max-width: 100%;
            overflow-wrap: break-word;
        }
        .block-markdown-view h1 { font-size: 1.5em; font-weight: 700; margin: .6em 0 .3em; border-bottom: 1px solid var(--border); padding-bottom: .2em; }
        .block-markdown-view h2 { font-size: 1.25em; font-weight: 600; margin: .55em 0 .25em; }
        .block-markdown-view h3 { font-size: 1.1em; font-weight: 600; margin: .45em 0 .2em; }
        .block-markdown-view p  { margin: .5em 0; }
        .block-markdown-view a  { color: var(--accent); text-decoration: underline; }
        .block-markdown-view code {
            font-family: 'JetBrains Mono', monospace;
            background: var(--bg-subtle);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 1px 5px;
            font-size: .88em;
        }
        .block-markdown-view pre {
            background: #1e1e2e;
            border-radius: 8px;
            padding: 14px 16px;
            overflow-x: auto;
            margin: .6em 0;
        }
        .block-markdown-view pre code {
            background: none;
            border: none;
            color: #cdd6f4;
            font-size: .88em;
            padding: 0;
        }
        .block-markdown-view blockquote {
            border-left: 3px solid var(--accent);
            margin: .6em 0;
            padding: 6px 14px;
            background: var(--bg-subtle);
            border-radius: 0 6px 6px 0;
            color: var(--text-muted);
            font-style: italic;
        }
        .block-markdown-view table {
            border-collapse: collapse;
            width: 100%;
            font-size: .9em;
            margin: .6em 0;
            overflow-x: auto;
            display: block;
        }
        .block-markdown-view th,
        .block-markdown-view td {
            border: 1px solid var(--border);
            padding: 7px 12px;
            text-align: left;
        }
        .block-markdown-view th { background: var(--bg-subtle); font-weight: 600; }
        .block-markdown-view tr:nth-child(even) td { background: var(--bg-subtle); }
        .block-markdown-view ul, .block-markdown-view ol { padding-left: 1.6em; margin: .4em 0; }
        .block-markdown-view li { margin: .25em 0; }
        .block-markdown-view hr { border: none; border-top: 1px solid var(--border); margin: 1em 0; }
        .block-markdown-view img { max-width: 100%; border-radius: 6px; }
        /* MathJax display math centering */
        .block-markdown-view .MathJax_Display { overflow-x: auto; }
    </style>

    <script>
        // Render all .block-markdown-view elements on the page
        function renderAllMarkdownBlocks() {
            const els = document.querySelectorAll('.block-markdown-view[data-md]');
            els.forEach(el => {
                const raw = el.dataset.md || '';
                if (typeof marked !== 'undefined') {
                    el.innerHTML = marked.parse(raw);
                } else {
                    el.innerHTML = raw.replace(/\n/g, '<br>');
                }
                el.removeAttribute('data-md'); // prevent double-render
            });

            // Single MathJax pass after all blocks are rendered
            if (window.MathJax && MathJax.typesetPromise) {
                MathJax.typesetPromise(
                    Array.from(document.querySelectorAll('.block-markdown-view'))
                ).catch(console.warn);
            }
        }

        // Run after DOM + MathJax are both ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => setTimeout(renderAllMarkdownBlocks, 100));
        } else {
            setTimeout(renderAllMarkdownBlocks, 100);
        }

        // Re-run when Livewire swaps content
        document.addEventListener('livewire:navigated', () => setTimeout(renderAllMarkdownBlocks, 150));
        document.addEventListener('livewire:morph',     () => setTimeout(renderAllMarkdownBlocks, 150));
    </script>
@endonce
