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
                                $codeData = json_decode($block->content ?? '{}', true);
                                $isJson   = is_array($codeData);
                                $cbMode   = $isJson ? ($codeData['mode']     ?? 'free') : 'free';
                                $cbLang   = $isJson ? ($codeData['language']  ?? '')     : '';
                                $cbVer    = $isJson ? ($codeData['version']   ?? '')     : '';
                                $cbCode   = $isJson ? ($codeData['code']      ?? '')     : ($block->content ?? '');
                                $cbProblem= $isJson ? ($codeData['problem']   ?? '')     : '';
                                $cbTests  = $isJson ? ($codeData['test_cases'] ?? [])    : [];
                                $cbId     = $block->id;
                            @endphp

                            <div class="scb-wrap" id="scb-{{ $cbId }}" data-block-id="{{ $cbId }}"
                                 data-lang="{{ $cbLang }}" data-version="{{ $cbVer }}"
                                 data-mode="{{ $cbMode }}" data-code="{{ e(json_encode($cbCode)) }}">

                                {{-- Problem statement (judge mode) --}}
                                @if($cbMode === 'judge' && $cbProblem)
                                    <div class="scb-problem">
                                        <div class="scb-problem-label">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                                            Problem
                                        </div>
                                        <p class="scb-problem-text">{{ $cbProblem }}</p>
                                    </div>
                                @endif

                                {{-- Toolbar --}}
                                <div class="scb-toolbar">
                                    <div class="scb-toolbar-left">
                                        <span class="scb-dot" style="background:#ff5f57"></span>
                                        <span class="scb-dot" style="background:#febc2e"></span>
                                        <span class="scb-dot" style="background:#28c840"></span>
                                        <span class="scb-lang-badge">{{ strtoupper($cbLang ?: 'Code') }}</span>
                                    </div>
                                    <div class="scb-toolbar-right">
                                        <button type="button" class="scb-btn-reset" data-block="{{ $cbId }}" onclick="scbReset('{{ $cbId }}')">Reset</button>
                                        @if($cbMode === 'judge')
                                            <button type="button" class="scb-btn-run" data-block="{{ $cbId }}" onclick="scbSubmit('{{ $cbId }}')">
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                                Submit
                                            </button>
                                        @else
                                            <button type="button" class="scb-btn-run" data-block="{{ $cbId }}" onclick="scbRun('{{ $cbId }}')">
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                                Run
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                {{-- CodeMirror host --}}
                                <div class="scb-cm-host" id="scb-cm-{{ $cbId }}"></div>

                                {{-- stdin (free mode only) --}}
                                @if($cbMode === 'free')
                                    <div class="scb-stdin-wrap" id="scb-stdin-wrap-{{ $cbId }}">
                                        <div class="scb-stdin-label">stdin (optional)</div>
                                        <textarea class="scb-stdin" id="scb-stdin-{{ $cbId }}" placeholder="Type input for your program…"></textarea>
                                    </div>
                                @endif

                                {{-- Output / terminal --}}
                                <div class="scb-output-wrap" id="scb-output-wrap-{{ $cbId }}" style="display:none;">
                                    <div class="scb-output-header">
                                        <span class="scb-output-label">Output</span>
                                        <div style="display:flex;gap:6px;align-items:center;">
                                            <span id="scb-badge-{{ $cbId }}" class="scb-badge" style="display:none;"></span>
                                            <button type="button" class="scb-btn-tiny" onclick="document.getElementById('scb-output-wrap-{{ $cbId }}').style.display='none'">Hide</button>
                                        </div>
                                    </div>
                                    <div class="scb-terminal" id="scb-terminal-{{ $cbId }}"></div>
                                </div>

                                {{-- Judge results --}}
                                @if($cbMode === 'judge')
                                    <div class="scb-judge-wrap" id="scb-judge-{{ $cbId }}" style="display:none;"></div>
                                @endif

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

@once
    <style>
        .scb-wrap {
            border: 1px solid #30363d;
            border-radius: 8px;
            overflow: hidden;
            background: #0d1117;
            margin: 12px 0;
        }
        .scb-problem {
            padding: 12px 14px;
            border-bottom: 1px solid #30363d;
            background: #161b22;
        }
        .scb-problem-label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #60a5fa;
            margin-bottom: 6px;
        }
        .scb-problem-text { font-size: 13px; color: #e2e8f0; line-height: 1.6; margin: 0; }
        .scb-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 10px;
            background: #161b22;
            border-bottom: 1px solid #30363d;
        }
        .scb-toolbar-left, .scb-toolbar-right { display: flex; align-items: center; gap: 6px; }
        .scb-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
        .scb-lang-badge {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .08em;
            color: #8b949e;
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 4px;
            padding: 2px 7px;
        }
        .scb-btn-run {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 11px;
            border-radius: 5px;
            border: none;
            font-size: 11px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            background: #4f46e5;
            color: #fff;
            transition: background .15s;
        }
        .scb-btn-run:hover { background: #4338ca; }
        .scb-btn-run:disabled { opacity: .5; cursor: not-allowed; }
        .scb-btn-reset {
            font-size: 10px;
            padding: 3px 8px;
            border: 1px solid #30363d;
            border-radius: 4px;
            background: none;
            color: #8b949e;
            cursor: pointer;
            font-family: inherit;
        }
        .scb-btn-reset:hover { background: #161b22; color: #e2e8f0; }
        .scb-btn-tiny {
            font-size: 9px;
            padding: 2px 7px;
            border: 1px solid #30363d;
            border-radius: 4px;
            background: none;
            color: #8b949e;
            cursor: pointer;
            font-family: inherit;
        }
        .scb-cm-host {
            min-height: 80px;
            max-height: 500px;
            overflow: auto;
        }
        .scb-cm-host .cm-editor { background: #0d1117; }
        .scb-cm-host .cm-scroller { overflow: auto; }
        .scb-stdin-wrap {
            border-top: 1px solid #30363d;
            background: #0d1117;
        }
        .scb-stdin-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: #4a5568; padding: 5px 10px 0; }
        .scb-stdin {
            width: 100%;
            background: transparent;
            border: none;
            outline: none;
            color: #e2e8f0;
            font-size: 11px;
            font-family: 'JetBrains Mono','Fira Code',monospace;
            padding: 6px 10px 8px;
            resize: vertical;
            min-height: 40px;
            max-height: 120px;
        }
        .scb-stdin::placeholder { color: #4a5568; }
        .scb-output-wrap { border-top: 1px solid #30363d; }
        .scb-output-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 5px 10px;
            background: #161b22;
            border-bottom: 1px solid #30363d;
        }
        .scb-output-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: #4a5568; }
        .scb-terminal {
            padding: 10px 12px;
            font-family: 'JetBrains Mono','Fira Code',monospace;
            font-size: 12px;
            line-height: 1.65;
            background: #0d1117;
            color: #e2e8f0;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 300px;
            overflow-y: auto;
        }
        .scb-out-stderr  { color: #f87171; display: block; }
        .scb-out-compile { color: #fbbf24; display: block; }
        .scb-out-system  { color: #60a5fa; font-style: italic; display: block; }
        .scb-badge { font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 20px; }
        .scb-badge.ok   { background: #064e3b; color: #6ee7b7; }
        .scb-badge.fail { background: #450a0a; color: #fca5a5; }
        /* judge */
        .scb-judge-wrap { padding: 10px; border-top: 1px solid #30363d; display: flex; flex-direction: column; gap: 6px; }
        .scb-judge-summary { font-size: 12px; font-weight: 600; padding: 7px 12px; border-radius: 6px; }
        .scb-judge-summary.all-pass { background: #064e3b; color: #6ee7b7; }
        .scb-judge-summary.has-fail { background: #450a0a; color: #fca5a5; }
        .scb-test-result { display: flex; flex-direction: column; gap: 4px; padding: 7px 9px; border-radius: 6px; border: 1px solid; font-size: 11px; font-family: 'JetBrains Mono','Fira Code',monospace; }
        .scb-test-result.pass { border-color: #064e3b; background: #0a1f18; }
        .scb-test-result.fail { border-color: #450a0a; background: #1a0808; }
        .scb-test-result-header { font-weight: 700; font-size: 10px; }
        .scb-test-result.pass .scb-test-result-header { color: #6ee7b7; }
        .scb-test-result.fail .scb-test-result-header { color: #fca5a5; }
        .scb-test-diff { color: #8b949e; margin-top: 2px; }
        .scb-test-diff span { color: #e2e8f0; }
    </style>
@endonce

{{-- ════════ SCRIPT (once per page) ════════ --}}
@once
    <script type="module">
        import { EditorState } from 'https://esm.sh/@codemirror/state@6';
        import { EditorView, keymap, lineNumbers, highlightActiveLine, drawSelection } from 'https://esm.sh/@codemirror/view@6';
        import { defaultKeymap, history, historyKeymap, indentWithTab } from 'https://esm.sh/@codemirror/commands@6';
        import { indentOnInput, bracketMatching } from 'https://esm.sh/@codemirror/language@6';
        import { python }     from 'https://esm.sh/@codemirror/lang-python@6';
        import { javascript } from 'https://esm.sh/@codemirror/lang-javascript@6';
        import { cpp }        from 'https://esm.sh/@codemirror/lang-cpp@6';
        import { java }       from 'https://esm.sh/@codemirror/lang-java@6';
        import { rust }       from 'https://esm.sh/@codemirror/lang-rust@6';
        import { oneDark }    from 'https://esm.sh/@codemirror/theme-one-dark@6';
        import { autocompletion } from 'https://esm.sh/@codemirror/autocomplete@6';

        const CM_LANG = {
            python: python(), javascript: javascript(), typescript: javascript({ typescript: true }),
            cpp: cpp(), 'c++': cpp(), c: cpp(), java: java(), rust: rust(),
        };
        const SCB_VIEWS = {};
        const SCB_INITIAL = {};

        function initStudentBlock(el) {
            const id      = el.dataset.blockId;
            const lang    = el.dataset.lang || 'python';
            const code = JSON.parse(el.dataset.code || '""');
            const host    = document.getElementById(`scb-cm-${id}`);
            if (!host || SCB_VIEWS[id]) return;

            SCB_INITIAL[id] = code;

            SCB_VIEWS[id] = new EditorView({
                state: EditorState.create({
                    doc: code,
                    extensions: [
                        lineNumbers(), highlightActiveLine(), history(), drawSelection(),
                        indentOnInput(), bracketMatching(), autocompletion(),
                        keymap.of([...defaultKeymap, ...historyKeymap, indentWithTab]),
                        CM_LANG[lang.toLowerCase()] ?? [],
                        oneDark,
                    ],
                }),
                parent: host,
            });
        }

        document.querySelectorAll('.scb-wrap').forEach(initStudentBlock);

        // ── Run (free mode) ──
        window.scbRun = async function (id) {
            const el      = document.getElementById(`scb-${id}`);
            const view    = SCB_VIEWS[id];
            const btn     = document.querySelector(`.scb-btn-run[data-block="${id}"]`);
            if (!view || !el) return;

            const code    = view.state.doc.toString();
            const lang    = el.dataset.lang;
            const version = el.dataset.version;
            const stdin   = document.getElementById(`scb-stdin-${id}`)?.value ?? '';

            btn.disabled = true;
            const origHTML = btn.innerHTML;
            btn.textContent = '…';

            const outputWrap = document.getElementById(`scb-output-wrap-${id}`);
            const terminal   = document.getElementById(`scb-terminal-${id}`);
            const badge      = document.getElementById(`scb-badge-${id}`);

            outputWrap.style.display = '';
            terminal.innerHTML = '<span class="scb-out-system">Running…</span>';
            badge.style.display = 'none';

            try {
                const res  = await fetch('/api/code/execute', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ language: lang, version, code, stdin }),
                });
                const data = await res.json();

                if (data.error) {
                    terminal.innerHTML = `<span class="scb-out-stderr">✗ ${data.error}</span>`;
                    return;
                }

                let html = '';
                if (data.compile?.stderr) html += `<span class="scb-out-compile">[Compile Error]\n${data.compile.stderr}</span>`;
                if (data.stdout)          html += data.stdout;
                if (data.stderr)          html += `<span class="scb-out-stderr">${data.stderr}</span>`;
                if (!html)                html  = '<span class="scb-out-system">(no output)</span>';
                terminal.innerHTML = html;

                const code2 = data.exit_code;
                badge.className = `scb-badge ${code2 === 0 ? 'ok' : 'fail'}`;
                badge.textContent = code2 === 0 ? 'Exit 0 ✓' : `Exit ${code2}`;
                badge.style.display = 'inline-flex';

            } catch (e) {
                terminal.innerHTML = `<span class="scb-out-stderr">✗ ${e.message}</span>`;
            } finally {
                btn.disabled = false;
                btn.innerHTML = origHTML;
            }
        };

        // ── Submit (judge mode) ──
        window.scbSubmit = async function (id) {
            const el      = document.getElementById(`scb-${id}`);
            const view    = SCB_VIEWS[id];
            const btn     = document.querySelector(`.scb-btn-run[data-block="${id}"]`);
            if (!view || !el) return;

            const code    = view.state.doc.toString();
            const lang    = el.dataset.lang;
            const version = el.dataset.version;
            // test_cases are embedded in block data — fetch from server
            // We'll POST to judge with the serialised test cases from data attribute
            const testCases = JSON.parse(el.dataset.testCases || '[]');

            btn.disabled = true;
            const origHTML = btn.innerHTML;
            btn.textContent = '…';

            const judgeWrap = document.getElementById(`scb-judge-${id}`);
            judgeWrap.style.display = '';
            judgeWrap.innerHTML = '<span style="color:#8b949e;font-size:12px;">Judging…</span>';

            try {
                const res  = await fetch('/api/code/judge', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ language: lang, version, code, test_cases: testCases }),
                });
                const data = await res.json();

                if (data.error) {
                    judgeWrap.innerHTML = `<span style="color:#f87171;">✗ ${data.error}</span>`;
                    return;
                }

                const summaryClass = data.all_passed ? 'all-pass' : 'has-fail';
                const summaryText  = data.all_passed
                    ? `✓ All ${data.total} tests passed!`
                    : `✗ ${data.passed}/${data.total} tests passed`;

                let html = `<div class="scb-judge-summary ${summaryClass}">${summaryText}</div>`;
                data.results.forEach((r, i) => {
                    const cls  = r.passed ? 'pass' : 'fail';
                    const icon = r.passed ? '✓' : '✗';
                    html += `<div class="scb-test-result ${cls}">
                <div class="scb-test-result-header">${icon} Test ${i + 1}</div>`;
                    if (!r.passed) {
                        html += `<div class="scb-test-diff">Expected: <span>${r.expected}</span></div>`;
                        html += `<div class="scb-test-diff">Got:      <span>${r.actual || r.error || '(empty)'}</span></div>`;
                    }
                    if (r.stderr) html += `<div class="scb-test-diff" style="color:#fbbf24;">Stderr: <span>${r.stderr}</span></div>`;
                    html += `</div>`;
                });

                judgeWrap.innerHTML = html;

            } catch (e) {
                judgeWrap.innerHTML = `<span style="color:#f87171;">✗ ${e.message}</span>`;
            } finally {
                btn.disabled = false;
                btn.innerHTML = origHTML;
            }
        };

        // ── Reset to teacher's starter code ──
        window.scbReset = function (id) {
            const view = SCB_VIEWS[id];
            if (!view) return;
            const starter = SCB_INITIAL[id] ?? '';
            view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: starter } });
        };
    </script>
@endonce
