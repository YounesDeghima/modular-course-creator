@extends('layouts.edditor')
@section('css')

    <link rel="stylesheet" href="{{asset('css/modular-site-preview.css')}}">
    <link rel="stylesheet" href="{{asset('css/preview.css')}}">
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

    @foreach($chapters as $i => $chapter)
        <div style="margin-bottom: 32px;">
            <div class="ch-main-header">
                <h2 class="ch-main-title">Chapter {{ $i+1 }} — {{ $chapter->title }}</h2>
                <span class="ch-progress-badge" id="ch-badge-{{ $chapter->id }}">
            {{ $chapter->progressForUser($id) }}% complete
        </span>
            </div>

            <div class="lessons-grid">
                @foreach($chapter->lessons as $j => $lesson)
                    @if($lesson->status == 'published')
                        @php $done = $lesson->progressForUser($id) && $lesson->progressForUser($id)->progress > 90; @endphp
                        <a class="lesson-card {{ $done ? 'done' : '' }}"
                           href="{{ route('admin.preview.blocks',['course'=>$course,'chapter'=>$chapter,'lesson'=>$lesson]) }}">
                            <span class="lc-num">{{ ($i+1) }}.{{ ($j+1) }}</span>
                            <span class="lc-title">{{ $lesson->title }}</span>
                            <span class="lc-check {{ $done ? 'check-done' : 'check-none' }}">
                    {{ $done ? '✓' : '' }}
                </span>
                        </a>
                    @endif
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
                <span id="overall-pct">0%</span>
            </div>
            <div class="sb-progress-bar">
                <div class="sb-progress-fill" id="overall-fill"></div>
            </div>
        </div>
    </div>

    <nav class="chapters-nav">
        @foreach($chapters as $i => $chapter)
            <div class="ch-nav-item">
                <a class="ch-nav-row {{ request()->route('chapter') == $chapter->id ? 'active' : '' }}"
                   href="{{ route('admin.preview.lessons', ['course'=>$course,'chapter'=>$chapter]) }}">
                    <span class="ch-nav-num">{{ str_pad($i+1, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="ch-nav-title">{{ $chapter->title }}</span>
                    <span class="ch-nav-dot
                {{ $chapter->progressForUser($id) == 100 ? 'dot-done' :
                  ($chapter->progressForUser($id) > 0 ? 'dot-partial' : 'dot-none') }}">
            </span>
                </a>
                <div class="ls-nav-list">
                    @foreach($chapter->lessons as $j => $lesson)
                        @if($lesson->status == 'published')
                            <a class="ls-nav-item {{ $lesson->progressForUser($id) && $lesson->progressForUser($id)->progress > 90 ? 'done' : '' }}"
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




        let chapters = document.querySelectorAll('.chapters > li');

        chapters.forEach((chapter,i)=>{
            let chapter_number= i+1;
            let lessons = chapter.querySelectorAll(':scope> ol > li');
            lessons.forEach((lesson,j)=>{
                let lesson_number= lesson.querySelector(':scope >a');
                lesson.insertAdjacentText('afterbegin',`${chapter_number}.` +`${j+1}`+' ');

            });


            let progressFill = chapter.querySelector('.chapter-progress-fill');
            let progress = progressFill.dataset.progress;

            setTimeout(() => {
                progressFill.style.width = progress + '%';
            }, 50);
            });

        function confirmReset() {
            return confirm("Are you sure you want to reset this chapter's progress? This action cannot be undone.");
        }





    </script>
@endsection

