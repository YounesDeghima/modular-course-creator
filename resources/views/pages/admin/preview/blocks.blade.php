@extends('layouts.edditor')
@include('partials.markdown_block_renderer')
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
                            <pre><code>{{ $block->content }}</code></pre>
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
