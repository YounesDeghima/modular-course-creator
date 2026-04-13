@extends('layouts.edditor')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/modular-site-preview.css') }}">
    <link rel="stylesheet" href="{{ asset('css/block-page.css') }}">


    <link rel="stylesheet" href="{{ asset('vendors/katex/katex.min.css') }}">
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
        @else
            <span class="sb-nav-btn disabled">‹ Prev</span>
        @endif

        @if($nextlesson)
            <a class="sb-nav-btn"
               href="{{ route('admin.preview.blocks', ['course'=>$course,'chapter'=>$chapter,'lesson'=>$nextlesson]) }}">
                Next ›
            </a>
        @else
            <span class="sb-nav-btn disabled">Next ›</span>
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
        @if($prevlesson)
            <div class="nav-button">
                <a href="{{ route('admin.preview.blocks',['course'=>$course,'chapter'=>$chapter,'lesson'=>$prevlesson]) }}">‹</a>
            </div>
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


    <script src="{{ asset('vendors/chart.js') }}"></script>
    <script src="{{ asset('vendors/katex/katex.min.js') }}"></script>
    <script src="{{ asset('vendors/katex/contrib/auto-render.min.js') }}"></script>


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
