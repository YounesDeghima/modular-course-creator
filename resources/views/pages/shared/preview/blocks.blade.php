
@extends('layouts.app')

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

        /* ── Code block (pv) ── */
        .pv-code-wrap {
            margin: 1.2rem 0;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #30363d;
            background: #0d1117;
        }
        .pv-code-header {
            display: flex; align-items: center; gap: 8px;
            padding: 7px 12px;
            background: #161b22;
            border-bottom: 1px solid #30363d;
            flex-wrap: wrap; row-gap: 6px;
        }
        .pv-code-dots { display: flex; gap: 5px; }
        .pv-code-dots span { width: 10px; height: 10px; border-radius: 50%; }
        .pv-code-dots span:nth-child(1) { background: #ff5f57; }
        .pv-code-dots span:nth-child(2) { background: #febc2e; }
        .pv-code-dots span:nth-child(3) { background: #28c840; }
        .pv-lang-badge {
            font-size: 10px; font-weight: 700; font-family: 'JetBrains Mono', monospace;
            padding: 2px 8px; border-radius: 20px;
            background: #21262d; color: #8b949e;
            text-transform: uppercase; letter-spacing: .06em;
            border: 1px solid #30363d;
        }
        .pv-btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 5px;
            font-size: 11px; font-weight: 600; font-family: inherit;
            border: 1px solid; cursor: pointer;
            transition: all .13s; white-space: nowrap;
        }
        .pv-btn-run  { background: #4f46e5; color: #fff; border-color: #4f46e5; }
        .pv-btn-run:hover  { background: #4338ca; }
        .pv-btn-kill { background: #7f1d1d; color: #fca5a5; border-color: #991b1b; }
        .pv-btn-kill:hover { background: #991b1b; }
        .pv-btn-ghost { background: #21262d; color: #8b949e; border-color: #30363d; }
        .pv-btn-ghost:hover { background: #30363d; color: #e6edf3; }
        .pv-btn-try { background: linear-gradient(135deg, #0ea5e9, #6366f1); color: #fff; border-color: transparent; }
        .pv-btn-try:hover { opacity: .88; }
        .pv-code-body {
            padding: 14px 16px; overflow-x: auto;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 13px; line-height: 1.7; color: #e2e8f0;
            white-space: pre; user-select: text; cursor: text; tab-size: 4;
        }
        .pv-term-panel { border-top: 1px solid #21262d; background: #0c0e12; display: flex; flex-direction: column; }
        .pv-term-topbar {
            display: flex; align-items: center; gap: 8px; padding: 5px 12px;
            background: #161b22; border-bottom: 1px solid #21262d; flex-shrink: 0;
        }
        .pv-term-title { display: flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: #4d5566; }
        .pv-exit-badge { font-size: 10px; font-weight: 700; padding: 1px 7px; border-radius: 20px; font-family: monospace; }
        .pv-exit-badge.ok   { background: #0d4429; color: #3fb950; }
        .pv-exit-badge.fail { background: #3d0f0e; color: #f85149; }
        .pv-run-time { font-size: 10px; color: #4d5566; font-family: 'JetBrains Mono', monospace; }
        .pv-term-btn { padding: 2px 8px; font-size: 10px; font-family: inherit; border: 1px solid #30363d; border-radius: 4px; background: #0d1117; color: #4d5566; cursor: pointer; transition: background .12s; }
        .pv-term-btn:hover { background: #21262d; color: #8b949e; }
        .pv-collapse-btn { margin-left: auto; }
        .pv-terminal { padding: 10px 14px; min-height: 80px; max-height: 260px; overflow-y: auto; font-family: 'JetBrains Mono', 'Fira Code', monospace; font-size: 12.5px; line-height: 1.75; color: #c9d1d9; white-space: pre-wrap; word-break: break-all; }
        .pv-terminal::-webkit-scrollbar { width: 5px; }
        .pv-terminal::-webkit-scrollbar-track { background: #0c0e12; }
        .pv-terminal::-webkit-scrollbar-thumb { background: #21262d; border-radius: 3px; }
        .pv-out-stdout { color: #c9d1d9; }
        .pv-out-stderr { color: #f85149; }
        .pv-out-system { color: #58a6ff; font-style: italic; }
        .pv-out-stdin-echo { color: #7c3aed; }
        .pv-term-welcome { color: #30363d; font-size: 12px; padding: 4px 0; }
        .pv-stdin-row { display: flex; align-items: center; gap: 8px; padding: 5px 12px; border-top: 1px solid #21262d; background: #0c0e12; flex-shrink: 0; }
        .pv-prompt { font-size: 14px; color: #4ade80; font-family: 'JetBrains Mono', monospace; }
        .pv-stdin-input { flex: 1; background: none; border: none; outline: none; color: #c9d1d9; font-family: 'JetBrains Mono', monospace; font-size: 12px; caret-color: #58a6ff; }

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
    <livewire:preview.lesson-sidebar
        :id="$id"
        :chapter="$chapter"
        :lesson="$lesson"
        :course="$course"
        :prevlesson="$prevlesson"
        :nextlesson="$nextlesson"
        :prevchapter="$prevchapter"
        :nextchapter="$nextchapter"
    />
@endsection

{{-- ── Top breadcrumb + completion bar ────────────────────────── --}}
@section('navigation')
    <div class="nav-box">
        <div class="navigation">
            <a href="{{ route($routePrefix . '.preview.courses') }}">{{ $course->year }}-{{ $course->branch }}</a>
            <span>›</span>
            <a href="{{ route($routePrefix . '.preview.chapters', ['course'=>$course]) }}">{{ $chapter->title }}</a>
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

    <div class="pdf-download-button" >

        {{-- NOTE: uses user.lessons.pdf — ensure admin users can access or duplicate route for admin.preview --}}
        <a target="_blank"
           style="height:40px;width:120px;padding:10px;position:absolute;bottom:20px;right:20px;display:flex;text-align:center"
           href="{{ route('user.lessons.pdf', ['id'=>$lesson->id]) }}">

        </a>
    </div>

    <div class="lesson-wrapper">

        {{-- Prev nav arrow --}}
        @if($prevlesson)
            <div class="nav-button">
                <a href="{{ route($routePrefix . '.preview.blocks',['course'=>$course,'chapter'=>$chapter,'lesson'=>$prevlesson]) }}">‹</a>
            </div>
        @elseif($prevchapter)
            <div class="nav-button">
                <a href="{{ route($routePrefix . '.preview.lessons',['course'=>$course,'chapter'=>$prevchapter]) }}" title="Previous chapter">«</a>
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

                            {{-- ── CODE ── pv code block with run/copy/try-it-yourself ── --}}
                        @case('code')
                            @php
                                $codeJson   = json_decode($block->content ?? '{}', true);
                                $codeLang   = $codeJson['language'] ?? 'python';
                                $codeText   = $codeJson['code']     ?? ($block->content ?? '');
                                if (!is_array($codeJson)) {
                                    $codeLang = 'python';
                                    $codeText = $block->content ?? '';
                                }
                                $codeText = str_replace('\n', "\n", $codeText);
                                $langLabels = [
                                    'python'=>'Python','javascript'=>'JavaScript','typescript'=>'TypeScript',
                                    'c'=>'C','cpp'=>'C++','java'=>'Java','rust'=>'Rust','go'=>'Go',
                                    'ruby'=>'Ruby','php'=>'PHP','lua'=>'Lua','perl'=>'Perl',
                                    'kotlin'=>'Kotlin','bash'=>'Bash','swift'=>'Swift',
                                ];
                                $langLabel = $langLabels[$codeLang] ?? ucfirst($codeLang);
                                $editorRoute = auth()->check()
                                    ? ($routePrefix === 'user' ? route('user.editor') : route('admin.editor'))
                                    : route('user.editor');
                            @endphp
                            <div class="pv-code-wrap" data-block-id="{{ $block->id }}"
                                 data-lang="{{ $codeLang }}"
                                 data-code="{{ e($codeText) }}"
                                 data-editor-url="{{ $editorRoute }}">
                                <div class="pv-code-header">
                                    <div class="pv-code-dots"><span></span><span></span><span></span></div>
                                    <span class="pv-lang-badge">{{ $langLabel }}</span>
                                    <div style="flex:1"></div>
                                    <button type="button" class="pv-btn pv-btn-try"
                                            onclick="pvTryItYourself({{ $block->id }})" title="Open in standalone editor">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                            <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>
                                            <polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
                                        </svg>
                                        Try it yourself
                                    </button>
                                    <button type="button" class="pv-btn pv-btn-ghost pv-copy-btn"
                                            onclick="pvCopy({{ $block->id }})" title="Copy code">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                            <rect x="9" y="9" width="13" height="13" rx="2"/>
                                            <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                                        </svg>
                                        Copy
                                    </button>
                                    <button type="button" class="pv-btn pv-btn-run"
                                            onclick="pvRun({{ $block->id }})" id="pv-run-{{ $block->id }}">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                        Run
                                    </button>
                                    <button type="button" class="pv-btn pv-btn-kill" style="display:none"
                                            onclick="pvKill({{ $block->id }})" id="pv-kill-{{ $block->id }}">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                        </svg>
                                        Kill
                                    </button>
                                </div>
                                <div class="pv-code-body" id="pv-body-{{ $block->id }}">{{ $codeText }}</div>
                                <div class="pv-term-panel" id="pv-term-{{ $block->id }}" style="display:none">
                                    <div class="pv-term-topbar">
                                    <span class="pv-term-title">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>
                                        </svg>
                                        Output
                                    </span>
                                        <span class="pv-exit-badge" id="pv-exit-{{ $block->id }}" style="display:none"></span>
                                        <span class="pv-run-time"   id="pv-time-{{ $block->id }}" style="display:none"></span>
                                        <button type="button" class="pv-term-btn" onclick="pvClearTerm({{ $block->id }})">Clear</button>
                                        <button type="button" class="pv-term-btn pv-collapse-btn"
                                                id="pv-collapse-{{ $block->id }}"
                                                onclick="pvToggleTerm({{ $block->id }})">▾ Hide</button>
                                    </div>
                                    <div class="pv-terminal" id="pv-out-{{ $block->id }}">
                                        <div class="pv-term-welcome">Press <strong>Run</strong> to execute this code.</div>
                                    </div>
                                    <div class="pv-stdin-row" id="pv-stdin-{{ $block->id }}" style="display:none">
                                        <span class="pv-prompt">❯</span>
                                        <input type="text" class="pv-stdin-input" id="pv-stdin-input-{{ $block->id }}"
                                               placeholder="Type input and press Enter…" autocomplete="off" spellcheck="false"
                                               onkeydown="if(event.key==='Enter'){pvSendInput({{ $block->id }});event.preventDefault()}">
                                        <button type="button" class="pv-term-btn" onclick="pvSendInput({{ $block->id }})">Send ↵</button>
                                    </div>
                                </div>
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
                                    <div class="solution-block" data-solution-for="{{ $block->id }}">

                                    No solution added yet.
                                    </div>
                                @else
                                    @foreach($block->solutions as $solution)
                                        <div class="solution-block" data-solution-for="{{ $block->id }}">

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
                <a href="{{ route($routePrefix . '.preview.blocks',['course'=>$course,'chapter'=>$chapter,'lesson'=>$nextlesson]) }}">›</a>
            </div>
        @elseif($nextchapter)
            <div class="nav-button">
                <a href="{{ route($routePrefix . '.preview.lessons',['course'=>$course,'chapter'=>$nextchapter]) }}" title="Next chapter">»</a>
            </div>
        @else
            <div class="nav-button" style="visibility:hidden;"><a>›</a></div>
        @endif

    </div>


    <livewire:preview.progress-form :lesson="$lesson" :lesson_progress="$lesson_progress"/>

   <div id="ai-inline-anchor"></div>
@endsection

@section('js')


    <script src="{{ asset('vendors/marked.min.js') }}"
            onerror="document.head.insertAdjacentHTML('beforeend',
              '<script src=\'https://cdn.jsdelivr.net/npm/marked@9/marked.min.js\'><\/script>')">
    </script>


    <script src="{{ asset('js/function.js') }}"></script>

    <script>

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
            // Always reset state (important after Livewire updates)
            document.querySelectorAll('.solution-block').forEach(s => {
                s.style.display = 'none';
            });

            document.querySelectorAll('.toggle-solution').forEach(btn => {
                btn.textContent = '▶ Show solution';
                btn.classList.remove('revealed');
            });
        }

        // delegated click handler (IMPORTANT FIX)
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.toggle-solution');
            if (!btn) return;

            const blockId = btn.dataset.blockid;

            const solutions = document.querySelectorAll(
                `.solution-block[data-solution-for="${blockId}"]`
            );

            const isHidden = solutions[0]?.style.display === 'none';

            solutions.forEach(s => {
                s.style.display = isHidden ? 'block' : 'none';
            });

            btn.textContent = isHidden ? '▼ Hide solution' : '▶ Show solution';
            btn.classList.toggle('revealed', isHidden);

            // re-run markdown if needed
            if (isHidden && typeof renderAllMarkdownBlocks === 'function') {
                renderAllMarkdownBlocks();
            }
        });

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

            // Trigger update when user clears 90% threshold and it hasn't been sent in this lifecycle
            if (maxProgress >= 90 && !progressSent) {
                progressSent = true;

                // Clean canonical communication directly to our Livewire component
                if (window.Livewire) {
                    Livewire.dispatch('progressReached', { progress: Math.round(maxProgress) });
                    // Update sidebar immediately when lesson is completed
                    Livewire.dispatch('reloadProgress');
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

    {{-- AI Assistant: admin and teacher only --}}

    @include('components.ai-assistant')


    <script>
        window.__PTY_BASE__  = "{{ config('services.pty.url') }}";
        window.__PTY_TOKEN__ = "{{ config('services.pty.secret') }}";
    </script>

    <script>
        (function () {
            if (window.__pvInit) return;
            window.__pvInit = true;
            window.__pv = window.__pv || {};

            function pvGetWrap(bid) { return document.querySelector('.pv-code-wrap[data-block-id="' + bid + '"]'); }

            function pvGetCode(bid) {
                const w = pvGetWrap(bid);
                if (!w) return '';
                // dataset.code comes from an HTML-encoded attribute; decode entities
                const raw = w.dataset.code ?? '';
                const txt = document.createElement('textarea');
                txt.innerHTML = raw;
                return txt.value;
            }
            function pvGetLang(bid) { const w = pvGetWrap(bid); return w ? (w.dataset.lang ?? 'python') : 'python'; }

            function pvAppend(bid, text, cls) {
                const out = document.getElementById('pv-out-' + bid);
                if (!out) return;
                out.querySelector('.pv-term-welcome')?.remove();
                const span = document.createElement('span');
                span.className = 'pv-out-' + cls;
                span.textContent = text;
                out.appendChild(span);
                out.scrollTop = out.scrollHeight;
            }

            function pvSetDone(bid, exitCode) {
                const state = window.__pv[bid];
                if (!state) return;
                state.running = false; state.ws = null;
                document.getElementById('pv-run-' + bid).style.display  = 'inline-flex';
                document.getElementById('pv-kill-' + bid).style.display = 'none';
                const stdinRow = document.getElementById('pv-stdin-' + bid);
                if (stdinRow) stdinRow.style.display = 'none';
                const elapsed = state.startTime ? ((Date.now() - state.startTime) / 1000).toFixed(2) : null;
                const exitBadge = document.getElementById('pv-exit-' + bid);
                if (exitBadge && exitCode !== -1) {
                    exitBadge.textContent   = exitCode === 0 ? 'exit 0' : `exit ${exitCode}`;
                    exitBadge.className     = 'pv-exit-badge ' + (exitCode === 0 ? 'ok' : 'fail');
                    exitBadge.style.display = 'inline-block';
                }
                const runTime = document.getElementById('pv-time-' + bid);
                if (runTime && elapsed) { runTime.textContent = elapsed + 's'; runTime.style.display = 'inline'; }
            }

            window.pvRun = function (bid) {
                if (window.__pv[bid]?.running) return;
                const code = pvGetCode(bid), lang = pvGetLang(bid);
                window.__pv[bid] = window.__pv[bid] || {};
                const state = window.__pv[bid];
                const termPanel = document.getElementById('pv-term-' + bid);
                if (termPanel) termPanel.style.display = 'flex';
                pvClearTerm(bid, false);
                pvAppend(bid, `▶ Running ${lang}…\n`, 'system');
                document.getElementById('pv-run-' + bid).style.display  = 'none';
                document.getElementById('pv-kill-' + bid).style.display = 'inline-flex';
                const exitBadge = document.getElementById('pv-exit-' + bid);
                const runTime   = document.getElementById('pv-time-' + bid);
                if (exitBadge) exitBadge.style.display = 'none';
                if (runTime)   runTime.style.display   = 'none';
                state.running = true; state.startTime = Date.now();
                const base  = (window.__PTY_BASE__  || 'ws://127.0.0.1:4000').replace(/\/$/, '');
                const token = window.__PTY_TOKEN__ || '';
                const ws = new WebSocket(`${base}?token=${encodeURIComponent(token)}`);
                state.ws = ws;
                ws.onopen = () => {
                    ws.send(JSON.stringify({ type: 'run', language: lang, code }));
                    const stdinRow = document.getElementById('pv-stdin-' + bid);
                    if (stdinRow) stdinRow.style.display = 'flex';
                };
                ws.onmessage = (evt) => {
                    let msg; try { msg = JSON.parse(evt.data); } catch (_) { pvAppend(bid, evt.data, 'stdout'); return; }
                    switch (msg.type) {
                        case 'stdout': pvAppend(bid, msg.data, 'stdout'); break;
                        case 'stderr': pvAppend(bid, msg.data, 'stderr'); break;
                        case 'exit': case 'done': pvSetDone(bid, msg.code ?? msg.exit_code ?? 0); break;
                        case 'error': pvAppend(bid, `\nError: ${msg.message}\n`, 'stderr'); pvSetDone(bid, 1); break;
                    }
                };
                ws.onerror = () => { pvAppend(bid, '\n⚠ Could not connect to PTY server.\n', 'stderr'); pvSetDone(bid, 1); };
                ws.onclose = () => { if (window.__pv[bid]?.running) pvSetDone(bid, 0); };
            };

            window.pvKill = function (bid) {
                const state = window.__pv[bid]; if (!state) return;
                state.ws?.send(JSON.stringify({ type: 'kill' })); state.ws?.close();
                pvAppend(bid, '\n⚡ Killed.\n', 'system'); pvSetDone(bid, -1);
            };

            window.pvCopy = function (bid) {
                navigator.clipboard.writeText(pvGetCode(bid)).then(() => {
                    const btn = pvGetWrap(bid)?.querySelector('.pv-copy-btn');
                    if (!btn) return;
                    btn.textContent = 'Copied!';
                    setTimeout(() => { btn.innerHTML = `<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg> Copy`; }, 2000);
                });
            };

            window.pvClearTerm = function (bid, showWelcome = true) {
                const out = document.getElementById('pv-out-' + bid); if (!out) return;
                out.innerHTML = '';
                if (showWelcome) out.innerHTML = '<div class="pv-term-welcome">Press <strong>Run</strong> to execute this code.</div>';
                const badge = document.getElementById('pv-exit-' + bid); const time = document.getElementById('pv-time-' + bid);
                if (badge) badge.style.display = 'none'; if (time) time.style.display = 'none';
            };

            window.pvToggleTerm = function (bid) {
                const out = document.getElementById('pv-out-' + bid); if (!out) return;
                const stdinRow = document.getElementById('pv-stdin-' + bid);
                const collapseBtn = document.getElementById('pv-collapse-' + bid);
                const hidden = out.style.display === 'none';
                out.style.display = hidden ? '' : 'none';
                if (stdinRow) stdinRow.style.display = hidden ? (window.__pv[bid]?.running ? 'flex' : 'none') : 'none';
                if (collapseBtn) collapseBtn.textContent = hidden ? '▾ Hide' : '▸ Show';
            };

            window.pvSendInput = function (bid) {
                const input = document.getElementById('pv-stdin-input-' + bid); const state = window.__pv[bid];
                if (!input || !state?.ws) return;
                const val = input.value;
                state.ws.send(JSON.stringify({ type: 'stdin', data: val + '\n' }));
                pvAppend(bid, val + '\n', 'stdin-echo'); input.value = '';
            };

            window.pvTryItYourself = function (bid) {
                const wrap = pvGetWrap(bid);
                const editorUrl = wrap?.dataset.editorUrl ?? '/user/editor';
                try { localStorage.setItem('bcb_prefill', JSON.stringify({ code: pvGetCode(bid), language: pvGetLang(bid) })); } catch (_) {}
                window.open(editorUrl, '_blank');
            };
        })();
    </script>

@endsection
