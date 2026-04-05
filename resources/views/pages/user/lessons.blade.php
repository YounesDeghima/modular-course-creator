@extends('layouts.edditor')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/modular-site-preview.css') }}">
    <link rel="stylesheet" href="{{ asset('css/block-page.css') }}">
@endsection

@section('sidebar-elements')
    <div class="sb-course-head">
        <div class="sb-course-label">Course</div>
        <div class="sb-chapter-name">{{ $course->title }}</div>
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
        @foreach($lessons as $i => $lesson_item)
            @php
                $lp     = $lesson_item->progressForUser($id);
                $isDone = $lp && $lp->progress >= 90;
            @endphp
            <a class="lesson-nav-item"
               href="{{ route('user.preview.blocks', ['course'=>$course,'chapter'=>$chapter,'lesson'=>$lesson_item]) }}">
                <span class="lesson-nav-num">{{ $chapter->chapter_number }}.{{ $i+1 }}</span>
                <span class="lesson-nav-title">{{ $lesson_item->title }}</span>
                <span class="lesson-nav-check {{ $isDone ? 'lnc-done' : 'lnc-none' }}">
            {{ $isDone ? '✓' : '' }}
        </span>
            </a>
        @endforeach
    </nav>

    <div class="sb-lesson-nav">
        <a class="sb-nav-btn"
           href="{{ route('user.preview.chapters', ['course'=>$course]) }}">
            ‹ Chapters
        </a>
    </div>
@endsection

@section('navigation')
    <div class="navigation">
        <a href="{{ route('user.preview.courses') }}">{{ $course->year }}-{{ $course->branch }}</a>
        <span>›</span>
        <a href="{{ route('user.preview.chapters', ['course'=>$course]) }}">{{ $course->title }}</a>
        <span>›</span>
        <span style="color:var(--text);font-weight:500;">{{ $chapter->title }}</span>
    </div>
@endsection

@section('main')
    <div style="max-width:740px;margin:0 auto;padding:28px 28px 60px;">

        {{-- Chapter header --}}
        <div style="margin-bottom:28px;padding-bottom:20px;border-bottom:1px solid var(--border);">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <span style="font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.06em;color:var(--text-faint);">
                Chapter {{ $chapter->chapter_number }}
            </span>
                <span style="font-size:11px;color:var(--border);">·</span>
                <span style="font-size:11px;color:var(--text-faint);">
                {{ $lessons->count() }} {{ Str::plural('lesson', $lessons->count()) }}
            </span>
            </div>
            <h1 style="font-size:26px;font-weight:500;color:var(--text);line-height:1.3;margin-bottom:10px;">
                {{ $chapter->title }}
            </h1>
            @if($chapter->description)
                <p style="font-size:15px;color:var(--text-muted);line-height:1.8;">
                    {{ $chapter->description }}
                </p>
            @endif

            {{-- Chapter progress bar --}}
            <div style="margin-top:16px;">
                <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-faint);margin-bottom:6px;">
                    <span>Chapter progress</span>
                    <span>{{ $chapter->progressForUser($id) }}%</span>
                </div>
                <div style="height:5px;background:var(--border);border-radius:999px;overflow:hidden;">
                    <div style="height:100%;border-radius:999px;background:var(--accent);width:{{ $chapter->progressForUser($id) }}%;transition:width .6s ease;"></div>
                </div>
            </div>
        </div>

        {{-- Lessons list --}}
        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach($lessons as $i => $lesson)
                @php
                    $lp     = $lesson->progressForUser($id);
                    $isDone = $lp && $lp->progress >= 90;
                    $pct    = $lp ? $lp->progress : 0;
                @endphp
                <a href="{{ route('user.preview.blocks', ['course'=>$course,'chapter'=>$chapter,'lesson'=>$lesson]) }}"
                   style="display:flex;align-items:center;gap:14px;padding:16px 18px;
                  border:1px solid var(--border);border-radius:10px;
                  text-decoration:none;transition:border-color .15s,background .15s;
                  border-left:3px solid {{ $isDone ? '#639922' : ($pct > 0 ? 'var(--accent)' : 'var(--border)') }};
                  border-radius:0 10px 10px 0;"
                   onmouseover="this.style.background='var(--bg-subtle)'"
                   onmouseout="this.style.background=''">

                    {{-- Number circle --}}
                    <div style="width:36px;height:36px;border-radius:50%;flex-shrink:0;
                        display:flex;align-items:center;justify-content:center;
                        font-size:13px;font-weight:500;
                        background:{{ $isDone ? '#d1fae5' : 'var(--bg-subtle)' }};
                        color:{{ $isDone ? '#065f46' : 'var(--text-muted)' }};">
                        @if($isDone)
                            ✓
                        @else
                            {{ $chapter->chapter_number }}.{{ $i+1 }}
                        @endif
                    </div>

                    {{-- Title + description --}}
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:14px;font-weight:500;color:var(--text);margin-bottom:3px;
                            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $lesson->title }}
                        </div>
                        @if($lesson->description)
                            <div style="font-size:12px;color:var(--text-muted);line-height:1.5;
                            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                {{ $lesson->description }}
                            </div>
                        @endif
                        @if($pct > 0 && !$isDone)
                            <div style="margin-top:6px;height:3px;background:var(--border);border-radius:999px;overflow:hidden;max-width:140px;">
                                <div style="height:100%;border-radius:999px;background:var(--accent);width:{{ $pct }}%;"></div>
                            </div>
                        @endif
                    </div>

                    {{-- Status tag --}}
                    <div style="flex-shrink:0;">
                        @if($isDone)
                            <span style="font-size:11px;padding:3px 9px;border-radius:999px;font-weight:500;background:#d1fae5;color:#065f46;">Done</span>
                        @elseif($pct > 0)
                            <span style="font-size:11px;padding:3px 9px;border-radius:999px;font-weight:500;background:#E6F1FB;color:#0C447C;">{{ $pct }}%</span>
                        @else
                            <span style="font-size:11px;padding:3px 9px;border-radius:999px;font-weight:500;background:var(--bg-subtle);color:var(--text-faint);border:1px solid var(--border);">Start</span>
                        @endif
                    </div>

                    <span style="font-size:16px;color:var(--text-faint);flex-shrink:0;">›</span>
                </a>
            @endforeach
        </div>

        {{-- Bottom nav --}}
        <div style="display:flex;justify-content:space-between;margin-top:32px;padding-top:20px;border-top:1px solid var(--border);">
            <a href="{{ route('user.preview.chapters', ['course'=>$course]) }}"
               style="font-size:13px;color:var(--text-muted);text-decoration:none;
                  padding:8px 14px;border:1px solid var(--border);border-radius:7px;
                  transition:background .13s;"
               onmouseover="this.style.background='var(--bg-hover)'"
               onmouseout="this.style.background=''">
                ‹ All chapters
            </a>
            @if($lessons->first())
                <a href="{{ route('user.preview.blocks', ['course'=>$course,'chapter'=>$chapter,'lesson'=>$lessons->first()]) }}"
                   style="font-size:13px;font-weight:500;color:#fff;text-decoration:none;
                  padding:8px 18px;border-radius:7px;background:var(--accent);
                  transition:background .13s;"
                   onmouseover="this.style.background='var(--accent-hover)'"
                   onmouseout="this.style.background='var(--accent)'">
                    @php $anyStarted = $lessons->first(fn($l) => $l->progressForUser($id) && $l->progressForUser($id)->progress > 0); @endphp
                    {{ $anyStarted ? 'Continue chapter ›' : 'Start chapter ›' }}
                </a>
            @endif
        </div>

    </div>
@endsection
