@extends('layouts.edditor')
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

        .btn-reset {
            font-size: 12px;
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: var(--bg-subtle);
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-reset:hover {
            border-color: #e53935;
            color: #e53935;
            background: rgba(229,57,53,0.08);
        }

        .blocks{
            display: block;
        }

    </style>
@endsection

@section('navigation')
    <div class="navigation">

        <a href="{{route('admin.preview.courses')}}">{{$course->year}}-{{$course->branch}}</a>
    </div>
@endsection
@section('main')
    <div class="breadcrumb">
        <a href="{{ route('admin.preview.courses') }}">Courses</a>
        <span class="sep">›</span>
        <span class="current">{{ $course->year }}-{{ $course->branch }}</span>
    </div>
    <livewire:preview.chapters :id="$id" :course="$course" :chapters="$chapters"/>

@endsection

@section('sidebar-elements')

    <livewire:preview.sidebar :id=$id :course="$course"/>

    <nav class="chapters-nav">

    @foreach($chapters as $i => $chapter)
        @php $chProgress = $chapter->progressForUser($id); @endphp
        <div class="ch-nav-item">
            <a class="ch-nav-row {{ request()->route('chapter') == $chapter->id ? 'active' : '' }}"
               href="{{ route('admin.preview.lessons', ['course'=>$course,'chapter'=>$chapter]) }}">
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
                        $done = $progress && $progress->progress > 80;


                    @endphp
                    @if($lesson->status == 'published')

                        <a class="ls-nav-item {{ $done ? 'done' : '' }}"
                           href="{{ route('admin.preview.blocks',['course'=>$course,'chapter'=>$chapter,'lesson'=>$lesson]) }}">
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








        function confirmReset() {
            return confirm("Are you sure you want to reset this chapter's progress? This action cannot be undone.");
        }





    </script>
@endsection

