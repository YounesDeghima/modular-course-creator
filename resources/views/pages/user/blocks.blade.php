@extends('layouts.user-base')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/modular-site-preview.css') }}">
    <link rel="stylesheet" href="{{ asset('css/block-page.css') }}">
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
        @if($prevlesson)
            <div class="nav-button">
                <a href="{{ route('user.preview.blocks',['course'=>$course,'chapter'=>$chapter,'lesson'=>$prevlesson]) }}">‹</a>
            </div>
        @elseif($prevchapter)
            <div class="nav-button">
                <a href="{{ route('user.preview.lessons',['course'=>$course,'chapter'=>$prevchapter]) }}" title="Previous chapter">«</a>
            </div>
        @else
            <div class="nav-button" style="visibility:hidden;">
                <a>‹</a>
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
                    @endswitch
                @endforeach
            </div>
        </div>

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
@endsection

@section('js')
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
    </script>
@endsection
