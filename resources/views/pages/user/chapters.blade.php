@extends('layouts.user-base')
@section('css')

    <link rel="stylesheet" href="{{asset('css/modular-site-preview.css')}}">
    <link rel="stylesheet" href="{{asset('css/preview.css')}}">

    <style>
        .sb-progress-bar {
            width: 100%;
            height: 6px;
            background: #eee;
        }

        .sb-progress-fill {
            height: 100%;
            width: 0;
            background: #4caf50;
            transition: width 0.4s ease;
        }
    </style>
@endsection

@section('navigation')
    <div class="navigation">

        <a href="{{route('user.preview.courses')}}">{{$course->year}}-{{$course->branch}}</a>
    </div>
@endsection
@section('main')
    <div class="breadcrumb">
        <a href="{{ route('user.preview.courses') }}">Courses</a>
        <span class="sep">›</span>
        <span class="current">{{ $course->year }}-{{ $course->branch }}</span>
    </div>

    @foreach($chapters as $i => $chapter)
        @php
            $chProgress = $chapter->progressForUser($id);
        @endphp

        <div style="margin-bottom: 32px;">
            <div class="ch-main-header">
                <h2 class="ch-main-title">Chapter {{ $i+1 }} — {{ $chapter->title }}</h2>
                <span class="ch-progress-badge" id="ch-badge-{{ $chapter->id }}">
                        {{ $chProgress }}% complete
                </span>
            </div>

            <div class="lessons-grid">
                @foreach($chapter->lessons as $j => $lesson)
                    @continue($lesson->status !== 'published')

                    @php
                        // Cache result per lesson to avoid repeated calls
                        static $progressCache = [];

                        if (!isset($progressCache[$lesson->id])) {
                            $progressCache[$lesson->id] = $lesson->progressForUser($id);
                        }

                        $progress = $progressCache[$lesson->id];
                        $done = $progress?->progress >= 90;
                    @endphp

                    <a class="lesson-card {{ $done ? 'done' : '' }}"
                       href="{{ route('user.preview.blocks', [
                            'course' => $course->id,
                            'chapter' => $chapter->id,
                            'lesson' => $lesson->id
                       ]) }}"
                       aria-label="Lesson {{ $lesson->title }}">

                        <span class="lc-num">{{ $i + 1 }}.{{ $j + 1 }}</span>
                        <span class="lc-title">{{ $lesson->title }}</span>

                        <span class="lc-check {{ $done ? 'check-done' : 'check-none' }}">
                            {{ $done ? '✓' : '' }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
@endsection

@section('sidebar-elements')
    <div class="sb-course-head">
        <div class="sb-course-label">Course</div>
        <div class="sb-course-name">{{ $course->title }}</div>
        <div class="sb-overall-progress">
            <div class="sb-progress-label">
                <span>Overall progress</span>
                <span id="overall-pct">{{$course->progressForUser($id)}}%</span>
            </div>
            <div class="sb-progress-bar">
                <div class="sb-progress-fill" id="overall-fill"></div>
            </div>
        </div>
    </div>

    <nav class="chapters-nav">



        @foreach($chapters as $i => $chapter)
            @php $chProgress = $chapter->progressForUser($id); @endphp
            <div class="ch-nav-item">
                <a class="ch-nav-row {{ request()->route('chapter') == $chapter->id ? 'active' : '' }}"
                   href="{{ route('user.preview.lessons', ['course'=>$course,'chapter'=>$chapter]) }}">
                    <span class="ch-nav-num">{{ str_pad($i+1, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="ch-nav-title">{{ $chapter->title }}</span>
                    <span class="ch-nav-dot
                {{$chProgress == 100 ? 'dot-done' :
                  ($chProgress  > 0 ? 'dot-partial' : 'dot-none') }}">
            </span>
                </a>
                <div class="ls-nav-list">
                    @foreach($chapter->lessons as $j => $lesson)
                        @php
                            $progress = $lesson->progressForUser($id);
                            $done = $progress && $progress->progress > 90;
                        @endphp
                        @if($lesson->status == 'published')
                            <a class="ls-nav-item {{ $done ? 'done' : '' }}"
                               href="{{ route('user.preview.blocks',['course'=>$course,'chapter'=>$chapter,'lesson'=>$lesson]) }}">
                                {{ ($i+1) }}.{{ ($j+1) }} {{ $lesson->title }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>
@endsection


@section('js')
    <script>



        document.addEventListener('DOMContentLoaded', function () {
            let pct = document.getElementById('overall-pct').innerText.replace('%','').trim();
            document.getElementById('overall-fill').style.width = pct + '%';
        });









    </script>
@endsection

