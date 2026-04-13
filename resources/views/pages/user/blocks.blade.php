@extends('layouts.user-base')

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
                   href="{{ route('user.preview.blocks', ['course'=>$course,'chapter'=>$chapter,'lesson'=>$lesson_item]) }}">
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
               href="{{ route('user.preview.blocks', ['course'=>$course,'chapter'=>$chapter,'lesson'=>$prevlesson]) }}">
                ‹ Prev
            </a>
        @elseif($prevchapter)
            <a class="sb-nav-btn"
               href="{{ route('user.preview.lessons', ['course'=>$course,'chapter'=>$prevchapter]) }}">
                ‹ Prev chapter
            </a>
        @else
            <span class="sb-nav-btn disabled">‹ Prev</span>
        @endif

        @if($nextlesson)
            <a class="sb-nav-btn"
               href="{{ route('user.preview.blocks', ['course'=>$course,'chapter'=>$chapter,'lesson'=>$nextlesson]) }}">
                Next ›
            </a>
        @elseif($nextchapter)
            <a class="sb-nav-btn"
               href="{{ route('user.preview.lessons', ['course'=>$course,'chapter'=>$nextchapter]) }}">
                Next chapter ›
            </a>
        @else
            <a class="sb-nav-btn"
               href="{{ route('user.preview.chapters', ['course'=>$course]) }}">
                Back to course ›
            </a>
        @endif
    </div>
@endsection

@section('navigation')
    <div class="navigation">
        <a href="{{ route('user.preview.courses') }}">{{ $course->year }}-{{ $course->branch }}</a>
        <span>›</span>
        <a href="{{ route('user.preview.chapters', ['course'=>$course]) }}">{{ $chapter->title }}</a>
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
@endsection

@section('main')
    <div class="lesson-wrapper">

        {{-- Prev nav button --}}
        @if($prevlesson)
            <div class="nav-button">
                <a href="{{ route('user.preview.blocks',['course'=>$course,'chapter'=>$chapter,'lesson'=>$prevlesson]) }}">‹</a>
            </div>
        @elseif($prevchapter)
            <div class="nav-button">
                <a href="{{ route('user.preview.lessons',['course'=>$course,'chapter'=>$prevchapter]) }}" title="Previous chapter">«</a>
            </div>
        @else
            <div class="nav-button" style="visibility:hidden;"><a>‹</a></div>
        @endif

        <div class="blocks-container">
            <div class="preview" id="preview">
                @foreach($blocks as $block)
                    @switch($block->type)
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
                                <small style="display:block;margin-top:8px;color:var(--text-faint);text-align:center;">LaTeX: {{ $block->content }}</small>
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

        {{-- Next nav button --}}
        @if($nextlesson)
            <div class="nav-button">
                <a href="{{ route('user.preview.blocks',['course'=>$course,'chapter'=>$chapter,'lesson'=>$nextlesson]) }}">›</a>
            </div>
        @elseif($nextchapter)
            <div class="nav-button">
                <a href="{{ route('user.preview.lessons',['course'=>$course,'chapter'=>$nextchapter]) }}" title="Next chapter">»</a>
            </div>
        @else
            <div class="nav-button">
                <a href="{{ route('user.preview.chapters',['course'=>$course]) }}" title="Back to course">⌂</a>
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

    {{-- Pass graph/function data to JS --}}
    @php
        $graphBlocks    = $blocks->where('type', 'graph');
        $functionBlocks = $blocks->where('type', 'function');
    @endphp

    @if($graphBlocks->count() || $functionBlocks->count())
        <script>
            window.__blockData = {
                graphs: {
                    @foreach($graphBlocks as $b)
                    "{{ $b->id }}": @json(json_decode($b->content, true) ?? []),
                    @endforeach
                },
                functions: {
                    @foreach($functionBlocks as $b)
                    "{{ $b->id }}": @json(json_decode($b->content, true) ?? []),
                    @endforeach
                }
            };
        </script>
    @endif
@endsection

@section('js')

    <script src="{{ asset('vendors/katex/katex.min.js') }}"></script>
    <script src="{{ asset('vendors/katex/contrib/auto-render.min.js') }}"></script>
    <script src="{{ asset('vendors/chart.js') }}"></script>


    <script src="{{ asset('js/function.js') }}"></script>
    <script>
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
            btn.className   = 'copy-code-btn';
            btn.textContent = 'Copy';
            pre.style.position = 'relative';
            pre.appendChild(btn);
            btn.addEventListener('click', () => {
                navigator.clipboard.writeText(pre.querySelector('code').innerText).then(() => {
                    btn.textContent = 'Copied!';
                    setTimeout(() => btn.textContent = 'Copy', 2000);
                });
            });
        });

        // ── Chart.js graphs ──
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.__blockData) return;

            // Graphs
            Object.entries(window.__blockData.graphs || {}).forEach(([id, data]) => {
                const canvas = document.getElementById('graph-' + id);
                if (!canvas || !data.labels) return;
                new Chart(canvas, {
                    type: data.type || 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: '',
                            data: data.data.map(Number),
                            backgroundColor: 'rgba(79,70,229,0.15)',
                            borderColor: '#4f46e5',
                            borderWidth: 2,
                            pointRadius: 4,
                            tension: 0.3,
                            fill: data.type === 'line',
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: data.type === 'pie' ? {} : {
                            x: { grid: { color: 'rgba(0,0,0,0.05)' } },
                            y: { grid: { color: 'rgba(0,0,0,0.05)' } }
                        }
                    }
                });
            });

            // Function plots
            Object.entries(window.__blockData.functions || {}).forEach(([id, d]) => {
                const canvas = document.getElementById('func-' + id);
                if (!canvas) return;
                drawFunction(canvas, d);
            });
        });

        // function drawFunction(canvas, d) {
        //     const ctx    = canvas.getContext('2d');
        //     const W      = canvas.width;
        //     const H      = canvas.height;
        //     const xMin   = parseFloat(d.x_min ?? -10);
        //     const xMax   = parseFloat(d.x_max ?? 10);
        //     const yMin   = parseFloat(d.y_min ?? -5);
        //     const yMax   = parseFloat(d.y_max ?? 5);
        //     const color  = d.color || '#4f46e5';
        //     const step   = parseFloat(d.step ?? 0.05);
        //     const expr   = (d.function || 'sin(x)')
        //         .replace(/\^/g,    '**')
        //         .replace(/sin/g,   'Math.sin')
        //         .replace(/cos/g,   'Math.cos')
        //         .replace(/tan/g,   'Math.tan')
        //         .replace(/sqrt/g,  'Math.sqrt')
        //         .replace(/log/g,   'Math.log')
        //         .replace(/abs/g,   'Math.abs')
        //         .replace(/pi/g,    'Math.PI')
        //         .replace(/e(?![a-z])/g, 'Math.E');
        //
        //     ctx.clearRect(0, 0, W, H);
        //     ctx.fillStyle = '#ffffff';
        //     ctx.fillRect(0, 0, W, H);
        //
        //     // Grid
        //     ctx.strokeStyle = '#e5e7eb';
        //     ctx.lineWidth   = 1;
        //     for (let i = 0; i <= 10; i++) {
        //         ctx.beginPath(); ctx.moveTo((i/10)*W, 0); ctx.lineTo((i/10)*W, H); ctx.stroke();
        //         ctx.beginPath(); ctx.moveTo(0, (i/10)*H); ctx.lineTo(W, (i/10)*H); ctx.stroke();
        //     }
        //
        //     // Axes
        //     ctx.strokeStyle = '#9ca3af';
        //     ctx.lineWidth   = 1.5;
        //     const yZero = H - ((0 - yMin) / (yMax - yMin)) * H;
        //     const xZero = ((0 - xMin) / (xMax - xMin)) * W;
        //     if (yZero >= 0 && yZero <= H) { ctx.beginPath(); ctx.moveTo(0, yZero); ctx.lineTo(W, yZero); ctx.stroke(); }
        //     if (xZero >= 0 && xZero <= W) { ctx.beginPath(); ctx.moveTo(xZero, 0); ctx.lineTo(xZero, H); ctx.stroke(); }
        //
        //     // Curve
        //     ctx.strokeStyle = color;
        //     ctx.lineWidth   = 2.5;
        //     ctx.beginPath();
        //     let first = true;
        //     const fn = new Function('x', `try { return ${expr}; } catch(e) { return NaN; }`);
        //     for (let x = xMin; x <= xMax; x += step) {
        //         const y = fn(x);
        //         if (!isFinite(y) || isNaN(y)) { first = true; continue; }
        //         const cx = ((x - xMin) / (xMax - xMin)) * W;
        //         const cy = H - ((y - yMin) / (yMax - yMin)) * H;
        //         if (cy < -500 || cy > H + 500) { first = true; continue; }
        //         first ? ctx.moveTo(cx, cy) : ctx.lineTo(cx, cy);
        //         first = false;
        //     }
        //     ctx.stroke();
        // }





            // Your existing KaTeX logic for function blocks
            document.querySelectorAll('.katex-eq').forEach(el => {
                const eq = el.getAttribute('data-eq');
                if (eq) {
                    katex.render(eq, el, { throwOnError: false });
                }
            });
        });

        // ── Restore scroll position ──
        document.addEventListener('DOMContentLoaded', () => {
            const key   = `lessonScroll_{{ $lesson->id }}`;
            const saved = localStorage.getItem(key);
            if (saved && main) main.scrollTop = parseInt(saved);
            main.addEventListener('scroll', () => localStorage.setItem(key, main.scrollTop));
        });
    </script>
@endsection
