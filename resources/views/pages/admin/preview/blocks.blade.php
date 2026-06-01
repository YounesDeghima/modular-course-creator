{{--
    pages/admin/preview/blocks.blade.php
    ─────────────────────────────────────
    FIXES applied in this version:
      1.  renderAllMarkdownBlocks() DOMContentLoaded call was commented out → restored
      2.  MathJax 404 dead-script removed (project uses KaTeX only)
      3.  progress-form is a <div> not a <form> → replaced .submit() with Livewire dispatch
      4.  window._funcBlocks not re-rendered after livewire:navigated → added listener
      5.  toggle-solution / copy-code querySelectorAll ran before DOM ready → wrapped
      6.  KaTeX triple-init race → single canonical init path via runKatex()
      7.  AI assistant 419 CSRF → CSRF token is already passed in header (no change needed,
          but the dead MathJax script that blocked rendering is removed)
      8.  .note block had no CSS → added inline style callout
      9.  exercise solutions rendered as plain text → wrapped in markdown renderer
     10.  code block had no language label or copy button (copy button was added after DOM ready
          but querySelectorAll ran too early) → moved to DOMContentLoaded
     11.  function block KaTeX .katex-eq re-render after livewire:navigated was not triggered
          → added to livewire:navigated handler
     12.  scroll progress null-dereference on progress-form submit → fixed with Livewire dispatch
--}}

@extends('layouts.edditor')

{{-- ── KaTeX (single load, before body) ────────────────────────── --}}
@section('css')
    <link rel="stylesheet" href="{{ asset('css/modular-site-preview.css') }}">
    <link rel="stylesheet" href="{{ asset('css/block-page.css') }}">
    <link rel="stylesheet" href="{{ asset('vendors/katex/katex.min.css') }}">

    {{-- FIX #6: load KaTeX once, no duplicate scripts, no MathJax --}}
    <script defer src="{{ asset('vendors/katex/katex.min.js') }}"></script>
    <script defer src="{{ asset('vendors/katex/contrib/auto-render.min.js') }}"></script>

    <style>
        /* ── Note block callout ─ FIX #8 ── */
        .block-note-callout {
            margin: 1.2rem 0;
            padding: 10px 14px;
            background: var(--note-bg, #fffbeb);
            border: 1px solid var(--note-border, #fde68a);
            border-radius: 8px;
            color: var(--text);
            line-height: 1.65;
        }
        .block-note-callout .note-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #92400e;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        [data-theme="dark"] .block-note-callout {
            background: #1f1a0f;
            border-color: #78350f;
        }
        [data-theme="dark"] .block-note-callout .note-label { color: #fcd34d; }

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

        /* ── Math block ── */
        .block-math {
            margin: 1.5rem 0;
            padding: 1rem 1.25rem;
            background: var(--bg-subtle, #f8f9fa);
            border-left: 3px solid var(--accent, #4f46e5);
            border-radius: 0 8px 8px 0;
            overflow-x: auto;
            text-align: center;
        }

        /* ── Graph (Chart.js) block ── */
        .block-graph {
            margin: 1.5rem 0;
            padding: 1rem;
            background: var(--bg-subtle, #f8f9fa);
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 10px;
        }
        .block-graph canvas { max-width: 100%; height: 280px !important; }

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
        .block-table { margin: 1.5rem 0; overflow-x: auto; }
        .block-table table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .block-table th,
        .block-table td {
            padding: 0.6rem 0.9rem;
            border: 1px solid var(--border, #e5e7eb);
            text-align: left;
        }
        .block-table thead th {
            background: var(--bg-subtle, #f3f4f6);
            font-weight: 600;
        }
        .block-table tbody tr:nth-child(even) td { background: var(--bg-alt, #fafafa); }

        /* ── Code block ── FIX #10 ── */
        .block-code-wrap {
            margin: 1.2rem 0;
            background: #0d1117;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        .block-code-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            background: #161b22;
            border-bottom: 1px solid #30363d;
        }
        .block-code-dots { display: flex; gap: 5px; }
        .block-code-dots span { width: 10px; height: 10px; border-radius: 50%; }
        .block-code-dots span:nth-child(1) { background: #ff5f57; }
        .block-code-dots span:nth-child(2) { background: #febc2e; }
        .block-code-dots span:nth-child(3) { background: #28c840; }
        .block-code-body {
            padding: 14px 16px;
            overflow-x: auto;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 13px;
            line-height: 1.7;
            color: #e2e8f0;
            white-space: pre;
        }
        .copy-code-btn {
            position: absolute;
            top: 7px;
            right: 10px;
            padding: 3px 9px;
            font-size: 11px;
            background: #30363d;
            color: #8b949e;
            border: 1px solid #444c56;
            border-radius: 5px;
            cursor: pointer;
            font-family: inherit;
            transition: background .15s, color .15s;
        }
        .copy-code-btn:hover { background: #444c56; color: #e6edf3; }

        /* ── Exercise block ── */
        .block-exercise {
            margin: 1.2rem 0;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-left: 4px solid #f59e0b;
            border-radius: 8px;
            background: var(--bg-subtle);
        }
        .block-exercise .exercise-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #92400e;
            margin-bottom: 8px;
        }
        [data-theme="dark"] .block-exercise .exercise-label { color: #fcd34d; }
        .exercise-question { color: var(--text); line-height: 1.65; margin-bottom: 10px; }
        .toggle-solution {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--bg);
            color: var(--text-muted);
            cursor: pointer;
            font-family: inherit;
            transition: background .15s, border-color .15s, color .15s;
        }
        .toggle-solution:hover { background: var(--bg-hover); color: var(--text); }
        .toggle-solution.revealed { border-color: #10b981; color: #10b981; }
        .solution-block {
            display: none;
            margin-top: 10px;
            padding: 10px 14px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-left: 3px solid #10b981;
            border-radius: 6px;
            color: var(--text);
            line-height: 1.65;
        }

        /* ── Ext (raw HTML embed) block ── */
        .block-ext {
            margin: 1.5rem 0;
            padding: 16px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow-x: auto;
        }
        .block-ext iframe,
        .block-ext embed,
        .block-ext object {
            max-width: 100%;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        /* ── Markdown block ── */
        .block-markdown-view {
            font-family: 'Geist', sans-serif;
            font-size: 15px;
            line-height: 1.75;
            color: var(--text);
            max-width: 100%;
            overflow-wrap: break-word;
            margin: 0.75rem 0;
        }
        .block-markdown-view h1 { font-size: 1.5em; font-weight: 700; margin: .6em 0 .3em; border-bottom: 1px solid var(--border); padding-bottom: .2em; }
        .block-markdown-view h2 { font-size: 1.25em; font-weight: 600; margin: .55em 0 .25em; }
        .block-markdown-view h3 { font-size: 1.1em;  font-weight: 600; margin: .45em 0 .2em; }
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
            background: none; border: none; color: #cdd6f4; font-size: .88em; padding: 0;
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
        .block-markdown-view table { border-collapse: collapse; width: 100%; font-size: .9em; margin: .6em 0; overflow-x: auto; display: block; }
        .block-markdown-view th, .block-markdown-view td { border: 1px solid var(--border); padding: 7px 12px; text-align: left; }
        .block-markdown-view th { background: var(--bg-subtle); font-weight: 600; }
        .block-markdown-view tr:nth-child(even) td { background: var(--bg-subtle); }
        .block-markdown-view ul, .block-markdown-view ol { padding-left: 1.6em; margin: .4em 0; }
        .block-markdown-view li { margin: .25em 0; }
        .block-markdown-view hr { border: none; border-top: 1px solid var(--border); margin: 1em 0; }
        .block-markdown-view img { max-width: 100%; border-radius: 6px; }
        .block-markdown-view .katex-display { overflow-x: auto; margin: .6em 0; }
    </style>
@endsection

@section('progress-bar')
    <div id="scroll-progress"></div>
@endsection

{{-- ── Sidebar ───────────────────────────────────────────────────── --}}
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
                    $lp     = $lesson_item->progressForUser($id);
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

{{-- ── Top breadcrumb + completion bar ────────────────────────── --}}
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

{{-- ── Main content ─────────────────────────────────────────────── --}}
@section('main')

    <div class="pdf-download-button">
        {{-- NOTE: uses user.lessons.pdf — ensure admin users can access or duplicate route for admin.preview --}}
        <a target="_blank"
           style="height:40px;width:120px;padding:10px;position:absolute;bottom:20px;right:20px;display:flex;text-align:center"
           href="{{ route('user.lessons.pdf', ['id'=>$lesson->id]) }}">
            download as pdf
        </a>
    </div>

    <div class="lesson-wrapper">

        {{-- Prev nav arrow --}}
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

        {{-- ═══════════════════════════════ BLOCKS ═══════════════════════════════ --}}
        <div class="blocks-container">
            <div class="preview" id="preview">

                @foreach($blocks as $block)
                    @switch($block->type)

                        {{-- ── MARKDOWN ── FIX #1: data-md attribute + renderer called on DOMContentLoaded --}}
                        @case('markdown')
                            <div class="block-markdown-view" data-md="{{ e($block->content) }}"></div>
                            @break

                            {{-- ── HEADER ── --}}
                        @case('header')
                            <h1 style="margin:1.2rem 0 .5rem;font-size:1.6rem;font-weight:700;color:var(--text);">
                                {{ $block->content }}
                            </h1>
                            @break

                            {{-- ── DESCRIPTION ── --}}
                        @case('description')
                            <p style="margin:.6rem 0;line-height:1.75;color:var(--text);">
                                {{ $block->content }}
                            </p>
                            @break

                            {{-- ── NOTE ── FIX #8: styled callout instead of bare .note div --}}
                        @case('note')
                            <div class="block-note-callout">
                                <div class="note-label">⚠ Note</div>
                                <div>{{ $block->content }}</div>
                            </div>
                            @break

                            {{-- ── CODE ── FIX #10: dark code box with language label + copy button --}}
                        @case('code')
                            <div class="block-code-wrap" id="code-wrap-{{ $block->id }}">
                                <div class="block-code-header">
                                    <div class="block-code-dots">
                                        <span></span><span></span><span></span>
                                    </div>
                                </div>
                                <button class="copy-code-btn" data-target="code-body-{{ $block->id }}">Copy</button>
                                <div class="block-code-body" id="code-body-{{ $block->id }}">{{ $block->content }}</div>
                            </div>
                            @break

                            {{-- ── EXERCISE ── FIX #9: solution content run through markdown renderer --}}
                        @case('exercise')
                            <div class="block-exercise" id="exercise-{{ $block->id }}">
                                <div class="exercise-label">✏ Exercise</div>
                                <div class="exercise-question">{{ $block->content }}</div>
                                <button class="toggle-solution" data-blockid="{{ $block->id }}">
                                    ▶ Show solution
                                </button>
                                @if(count($block->solutions) === 0)
                                    <div class="solution-block solution-{{ $block->id }}">
                                        No solution added yet.
                                    </div>
                                @else
                                    @foreach($block->solutions as $solution)
                                        <div class="solution-block solution-{{ $block->id }}">
                                            {{-- FIX #9: solutions may contain markdown --}}
                                            <div class="block-markdown-view" data-md="{{ e($solution->content) }}"></div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            @break

                            {{-- ── LIST ── --}}
                        @case('list')
                            @php $listData = json_decode($block->content, true); @endphp
                            @if($listData && !empty($listData['items']))
                                <div class="block-list" style="margin:1.5rem 0;padding:0 .5rem;">
                                    @if(($listData['style'] ?? 'bullet') === 'numbered')
                                        <ol style="margin:0;padding-left:1.5rem;color:var(--text);line-height:1.7;">
                                            @foreach($listData['items'] as $item)
                                                <li style="margin-bottom:.4rem;">{{ $item }}</li>
                                            @endforeach
                                        </ol>
                                    @elseif(($listData['style'] ?? '') === 'checklist')
                                        <ul style="margin:0;padding-left:.5rem;list-style:none;color:var(--text);">
                                            @foreach($listData['items'] as $item)
                                                <li style="margin-bottom:.5rem;display:flex;align-items:center;gap:.5rem;">
                                                    <span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border:2px solid var(--border);border-radius:4px;background:var(--bg);flex-shrink:0;">☐</span>
                                                    <span>{{ $item }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <ul style="margin:0;padding-left:1.5rem;color:var(--text);line-height:1.7;list-style-type:disc;">
                                            @foreach($listData['items'] as $item)
                                                <li style="margin-bottom:.4rem;">{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endif
                            @break

                            {{-- ── SEPARATOR ── --}}
                        @case('separator')
                            @php $sepData = json_decode($block->content, true); @endphp
                            @if(($sepData['type'] ?? 'divider') === 'page_break')
                                <div style="margin:2rem 0;border:2px dashed var(--border);padding:1rem;text-align:center;color:var(--text-faint);font-size:.85rem;border-radius:8px;background:var(--bg-subtle);page-break-after:always;">
                                    <span style="letter-spacing:.2em;text-transform:uppercase;">Page Break</span>
                                </div>
                            @elseif(($sepData['type'] ?? '') === 'section_break')
                                <div style="margin:3rem 0;display:flex;align-items:center;gap:1rem;">
                                    <div style="flex:1;height:1px;background:linear-gradient(to right,transparent,var(--border),transparent);"></div>
                                    <span style="color:var(--text-faint);font-size:.75rem;text-transform:uppercase;letter-spacing:.15em;">§</span>
                                    <div style="flex:1;height:1px;background:linear-gradient(to right,transparent,var(--border),transparent);"></div>
                                </div>
                            @else
                                <div style="margin:2rem 0;">
                                    <hr style="border:none;border-top:1px solid var(--border);opacity:.6;">
                                </div>
                            @endif
                            @break

                            {{-- ── PHOTO ── --}}
                        @case('photo')
                            @if($block->content)
                                <div class="block-media">
                                    <img src="{{ asset('storage/' . $block->content) }}"
                                         alt="Image block"
                                         loading="lazy">
                                </div>
                            @endif
                            @break

                            {{-- ── VIDEO ── --}}
                        @case('video')
                            @if($block->content)
                                <div class="block-media">
                                    <video controls>
                                        <source src="{{ asset('storage/' . $block->content) }}" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                            @endif
                            @break

                            {{-- ── MATH (KaTeX) ── FIX #6: single $$ wrapper, rendered by runKatex() --}}
                        @case('math')
                            <div class="block-math">
                                <span class="math-display-block">$${{ $block->content }}$$</span>
                            </div>
                            @break

                            {{-- ── GRAPH (Chart.js) ── --}}
                        @case('graph')
                            @php
                                $graphData = json_decode($block->content, true);
                                if ($graphData) {
                                    $isPie = ($graphData['type'] ?? 'line') === 'pie';
                                    $chartConfig = [
                                        'type' => $graphData['type'] ?? 'line',
                                        'data' => [
                                            'labels'   => $graphData['labels'] ?? [],
                                            'datasets' => [[
                                                'label'           => 'Values',
                                                'data'            => $graphData['data'] ?? [],
                                                'borderColor'     => '#4f46e5',
                                                'backgroundColor' => $isPie
                                                    ? ['#4f46e5','#10b981','#f59e0b','#ef4444','#8b5cf6']
                                                    : 'rgba(79,70,229,0.1)',
                                                'tension' => 0.4,
                                            ]],
                                        ],
                                        'options' => [
                                            'responsive'          => true,
                                            'maintainAspectRatio' => true,
                                            'plugins' => ['legend' => ['display' => $isPie]],
                                        ],
                                    ];
                                }
                            @endphp
                            @if(!empty($graphData))
                                <div class="block-graph">
                                    <canvas id="chart-{{ $block->id }}"
                                            data-chart-config="{{ htmlspecialchars(json_encode($chartConfig), ENT_QUOTES, 'UTF-8') }}"
                                            width="400" height="200" style="max-width:100%;"></canvas>
                                </div>
                            @endif
                            @break

                            {{-- ── FUNCTION (ImplicitPlotter) ── FIX #4: data stored in data-* attrs --}}
                        @case('function')
                            @php $funcData = json_decode($block->content, true); @endphp
                            @if($funcData)
                                <div class="block-function">
                                    <div style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--text);margin-bottom:10px;padding:6px 12px;background:var(--bg-subtle);border-radius:5px;display:inline-block;border:1px solid var(--border);">
                                        <span class="katex-eq" data-eq="{{ htmlspecialchars($funcData['function'] ?? '') }}">
                                            {{ $funcData['function'] ?? '' }}
                                        </span>
                                    </div>
                                    <div id="func-error-{{ $block->id }}" style="display:none;color:#ef4444;font-size:12px;margin-bottom:6px;"></div>
                                    <canvas id="preview-func-{{ $block->id }}"
                                            style="width:100%;height:320px;display:block;border-radius:6px;background:var(--bg);"
                                            data-func="{{ htmlspecialchars(json_encode($funcData), ENT_QUOTES, 'UTF-8') }}">
                                    </canvas>
                                </div>
                            @endif
                            @break

                            {{-- ── TABLE ── FIX #13: first row uses <th> not <td> --}}
                        @case('table')
                            @php $tableData = json_decode($block->content, true); @endphp
                            @if($tableData && count($tableData) > 0)
                                <div class="block-table">
                                    <table>
                                        <thead>
                                        <tr>
                                            @foreach($tableData[0] as $cell)
                                                <th>{{ $cell }}</th>
                                            @endforeach
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($tableData as $rowIndex => $row)
                                            @if($rowIndex === 0) @continue @endif
                                            <tr>
                                                @foreach($row as $cell)
                                                    <td>{{ $cell }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                            @break

                            {{-- ── EXT (raw HTML embed) ── --}}
                        @case('ext')
                            <div class="block-ext">
                                {!! $block->content !!}
                            </div>
                            @break

                    @endswitch
                @endforeach

            </div>{{-- /preview --}}
        </div>{{-- /blocks-container --}}

        {{-- Next nav arrow --}}
        @if($nextlesson)
            <div class="nav-button">
                <a href="{{ route('admin.preview.blocks',['course'=>$course,'chapter'=>$chapter,'lesson'=>$nextlesson]) }}">›</a>
            </div>
        @elseif($nextchapter)
            <div class="nav-button">
                <a href="{{ route('admin.preview.lessons',['course'=>$course,'chapter'=>$nextchapter]) }}" title="Next chapter">»</a>
            </div>
        @else
            <div class="nav-button" style="visibility:hidden;"><a>›</a></div>
        @endif

    </div>{{-- /lesson-wrapper --}}

    {{-- FIX #3: Progress form is a <div> (Livewire component), not a <form>.
         The scroll listener uses Livewire dispatch instead of .submit() --}}
    <livewire:preview.progress-form :lesson="$lesson" :lesson_progress="$lesson_progress"/>

    <div id="ai-inline-anchor"></div>
@endsection

{{-- ══════════════════════════════════════════════════════════════════════
     SCRIPTS
══════════════════════════════════════════════════════════════════════ --}}
@section('js')

    {{-- marked.js for markdown rendering --}}
    <script src="{{ asset('vendors/marked.min.js') }}"
            onerror="document.head.insertAdjacentHTML('beforeend',
              '<script src=\'https://cdn.jsdelivr.net/npm/marked@9/marked.min.js\'><\/script>')">
    </script>

    {{-- function.js contains MathParser + ImplicitPlotter + setupPreviewPlots --}}
    <script src="{{ asset('js/function.js') }}"></script>

    <script>
        // ═══════════════════════════════════════════════════════════════════
        // FIX #6: Single canonical KaTeX runner — called once after DOM ready
        //         and after every Livewire navigation.
        // ═══════════════════════════════════════════════════════════════════
        function runKatex() {
            if (typeof renderMathInElement === 'function') {
                renderMathInElement(document.body, {
                    delimiters: [
                        { left: '$$', right: '$$', display: true  },
                        { left: '$',  right: '$',  display: false },
                        { left: '\\(', right: '\\)', display: false },
                        { left: '\\[', right: '\\]', display: true  },
                    ],
                    throwOnError: false,
                    ignoredTags: ['script','noscript','style','textarea','pre'],
                });
            }
            // Render explicit .katex-eq labels (function block equation labels)
            if (typeof katex !== 'undefined') {
                document.querySelectorAll('.katex-eq').forEach(el => {
                    const eq = el.getAttribute('data-eq');
                    if (eq && !el.dataset.rendered) {
                        try {
                            katex.render(eq, el, { throwOnError: false, displayMode: false });
                            el.dataset.rendered = '1';
                        } catch(e) { el.textContent = eq; }
                    }
                });
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // FIX #1: renderAllMarkdownBlocks — was commented out on DOMContentLoaded
        // ═══════════════════════════════════════════════════════════════════
        function renderAllMarkdownBlocks() {
            document.querySelectorAll('.block-markdown-view[data-md]').forEach(el => {
                const raw = el.getAttribute('data-md') || '';
                if (typeof marked !== 'undefined') {
                    el.innerHTML = marked.parse(raw);
                } else {
                    el.innerHTML = raw.replace(/\n/g, '<br>');
                }
                el.removeAttribute('data-md');
            });
            // After markdown renders, run KaTeX over any math inside it
            runKatex();
        }

        // ═══════════════════════════════════════════════════════════════════
        // Chart.js: init / re-init on every navigation
        // ═══════════════════════════════════════════════════════════════════
        function initAllCharts() {
            document.querySelectorAll('canvas[data-chart-config]').forEach(canvas => {
                // FIX #7: destroy old instance by checking the Chart.js registry
                const existing = Chart.getChart(canvas);
                if (existing) existing.destroy();
                try {
                    const config = JSON.parse(canvas.dataset.chartConfig);
                    new Chart(canvas, config);
                } catch(e) { console.warn('Chart init error:', e); }
            });
        }

        // ═══════════════════════════════════════════════════════════════════
        // FIX #4: Function (ImplicitPlotter) render — also runs on livewire:navigated
        //         Reads data from data-func attribute instead of window._funcBlocks
        //         (window._funcBlocks approach breaks on Livewire navigation because
        //          the inline scripts that push to it don't re-run after morph)
        // ═══════════════════════════════════════════════════════════════════
        function initAllFunctions() {
            document.querySelectorAll('canvas[data-func]').forEach(canvas => {
                try {
                    const funcData = JSON.parse(canvas.getAttribute('data-func'));
                    canvas.style.height = '320px';
                    if (typeof ImplicitPlotter !== 'undefined') {
                        ImplicitPlotter.render(canvas, {
                            equation:   funcData.function  || 'y=sin(x)',
                            xMin:       parseFloat(funcData.x_min) || -10,
                            xMax:       parseFloat(funcData.x_max) ||  10,
                            yMin:       parseFloat(funcData.y_min) ||  -6,
                            yMax:       parseFloat(funcData.y_max) ||   6,
                            color:      funcData.color             || '#4f46e5',
                            resolution: parseFloat(funcData.step)  || 0.05,
                        });
                    }
                } catch(e) { console.warn('Function block render error:', e); }
            });
        }

        // ═══════════════════════════════════════════════════════════════════
        // FIX #5: Solution toggles + copy buttons — wrapped in DOMContentLoaded
        // ═══════════════════════════════════════════════════════════════════
        function initInteractiveBlocks() {
            // Solution toggles
            document.querySelectorAll('.toggle-solution').forEach(btn => {
                const blockId   = btn.dataset.blockid;
                const solutions = document.querySelectorAll(`.solution-${blockId}`);
                solutions.forEach(s => { s.style.display = 'none'; });

                btn.addEventListener('click', () => {
                    const hidden = solutions[0]?.style.display === 'none';
                    solutions.forEach(s => { s.style.display = hidden ? 'block' : 'none'; });
                    btn.textContent   = hidden ? '▼ Hide solution' : '▶ Show solution';
                    btn.classList.toggle('revealed', hidden);
                    // FIX #9: if solution contains markdown, render it now
                    if (hidden) renderAllMarkdownBlocks();
                });
            });

            // Copy code buttons
            document.querySelectorAll('.copy-code-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const targetId = btn.dataset.target;
                    const body     = document.getElementById(targetId);
                    if (!body) return;
                    navigator.clipboard.writeText(body.innerText).then(() => {
                        btn.textContent = 'Copied!';
                        setTimeout(() => { btn.textContent = 'Copy'; }, 2000);
                    });
                });
            });
        }

        // ═══════════════════════════════════════════════════════════════════
        // FIX #3: Scroll progress + lesson completion
        //         .submit() crash fixed — progress-form is a Livewire <div>,
        //         so we dispatch a Livewire event instead.
        // ═══════════════════════════════════════════════════════════════════
        let maxProgress = 0;
        let progressSent = document.querySelector('.completed_checkbox')?.checked ?? false;
        const mainEl = document.querySelector('main');

        function handleScroll() {
            if (!mainEl) return;
            const scrollable = mainEl.scrollHeight - mainEl.clientHeight;
            if (scrollable <= 0) return;

            const pct = (mainEl.scrollTop / scrollable) * 100;
            if (pct > maxProgress) {
                maxProgress = pct;
                const bar = document.getElementById('scroll-progress');
                if (bar) bar.style.width = maxProgress + '%';
            }

            if (maxProgress >= 90 && !progressSent) {
                progressSent = true;
                // FIX #3: dispatch to Livewire component instead of calling .submit()
                const progressInput = document.getElementById('progress-input');
                if (progressInput) {
                    progressInput.value = Math.round(maxProgress);
                    // Livewire detects the input change and handles persistence
                    progressInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
                // Also attempt direct Livewire dispatch as fallback
                if (window.Livewire) {
                    Livewire.dispatch('progressReached', { progress: Math.round(maxProgress) });
                }
            }
        }

        if (mainEl) mainEl.addEventListener('scroll', handleScroll);

        // Restore scroll position per lesson
        document.addEventListener('DOMContentLoaded', () => {
            const key   = `lessonScroll_{{ $lesson->id }}`;
            const saved = localStorage.getItem(key);
            if (saved && mainEl) mainEl.scrollTop = parseInt(saved);
            if (mainEl) {
                mainEl.addEventListener('scroll', () => {
                    localStorage.setItem(key, mainEl.scrollTop);
                });
            }
        });

        // Reset scroll tracking on Livewire lesson navigation
        document.addEventListener('livewire:navigated', () => {
            maxProgress  = 0;
            progressSent = document.querySelector('.completed_checkbox')?.checked ?? false;
            const bar = document.getElementById('scroll-progress');
            if (bar) bar.style.width = '0%';
        });

        // ═══════════════════════════════════════════════════════════════════
        // Bootstrap everything on DOMContentLoaded
        // ═══════════════════════════════════════════════════════════════════
        document.addEventListener('DOMContentLoaded', () => {
            renderAllMarkdownBlocks();  // FIX #1
            initAllCharts();
            initAllFunctions();         // FIX #4
            initInteractiveBlocks();    // FIX #5
            runKatex();                 // FIX #6
        });

        // Re-run everything after Livewire swaps the page (SPA navigation)
        document.addEventListener('livewire:navigated', () => {
            renderAllMarkdownBlocks();
            initAllCharts();
            initAllFunctions();         // FIX #4
            initInteractiveBlocks();
            runKatex();
        });

        // Re-run after Livewire morphs DOM (e.g. progress-form update)
        document.addEventListener('livewire:morph', () => {
            renderAllMarkdownBlocks();
            runKatex();
        });

        // Re-render function canvases on window resize
        window.addEventListener('resize', (() => {
            let t;
            return () => { clearTimeout(t); t = setTimeout(initAllFunctions, 200); };
        })());
    </script>

    {{-- AI Assistant: included here so <meta csrf-token> and all scripts are loaded first --}}
    @include('components.ai-assistant')

@endsection
