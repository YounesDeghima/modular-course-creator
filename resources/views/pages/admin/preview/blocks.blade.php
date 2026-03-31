@extends('layouts.admin-base')

@section('css')
    <link rel="stylesheet" href="{{asset('css/modular-site-preview.css')}}">
    <link rel="stylesheet" href="{{asset('css/block-page.css')}}">
    <link rel="stylesheet" href="{{asset('css/preview.css')}}">
@endsection

@section('navigation')


    <div class="navigation">

        <a href="{{route('admin.preview.years')}}">home</a>
        <a>--></a>
        <a href="{{route('admin.preview.backcourses',['year'=>$year,'branch'=>$course->branch])}}">{{$year}}-{{$course->branch}}</a>
        <a>---></a>
        <a href="{{route('admin.preview.chapters',['year'=>$year,'course'=>$course])}}">{{$chapter->title}}</a>
        <a>---></a>
        <a href="{{route('admin.preview.lessons',['year'=>$year,'course'=>$course,'chapter'=>$chapter])}}">{{$lesson->title}}</a>
    </div>
    <div class="lesson-complete">
        <label>

            <input class="completed_checkbox" type="checkbox" disabled
                   @if($lesson_progress && $lesson_progress->progress >= 90)
                       checked
                @endif
            >
            Lesson Completed
        </label>
    </div>

@endsection

@section('main')
    <div id="scroll-progress"></div>
    <div class="lesson-wrapper">

        @if($prevlesson)
            <div class="nav-button"><a href={{route('admin.preview.blocks',['year'=>$year,'course'=>$course,'chapter'=>$chapter,'lesson'=>$prevlesson])}}><</a></div>
        @endif
        <div class="blocks-container" id="blocks-container">


            <div class="preview" id="preview">
                @foreach($blocks as $block)
                    @switch($block->type)
                        @case('header')
                            <h1>{{$block->content}}</h1>
                            @break
                        @case('description')
                            <p>{{$block->content}}</p>
                            @break
                        @case('code')
                            <pre><code >{{$block->content}}</code></pre>
                            @break
                        @case('note')
                            <div class="note">{{$block->content}}</div>
                            @break
                        @case('exercise')
                            <div class="exercise">
                                <strong>Q: {{$block->content}}</strong>
                                <button class="toggle-solution" data-blockid="{{$block->id}}">show solution</button>

                                @if(count($block->solutions)==0)
                                    <div class="solution solution-{{$block->id}}" >there is nothing here yet</div>
                                @else
                                    @foreach($block->solutions as $solution)
                                        <div class="solution solution-{{$block->id}}">{{$solution->content}}</div>
                                    @endforeach
                                @endif

                            </div>
                    @endswitch
                @endforeach
            </div>

        </div>
        @if($nextlesson)
            <div class="nav-button"><a href={{route('admin.preview.blocks',['year'=>$year,'course'=>$course,'chapter'=>$chapter,'lesson'=>$nextlesson])}}>></a></div>
        @endif
    </div>
    <form id="progress-form" method="POST" action="{{ route('user.lesson.progress.store',['lesson'=>$lesson])}}" style="display: none;">
        @csrf

        <input type="hidden" name="lesson_id" value="{{ $lesson->id ?? '' }}">

        <input type="hidden" name="progress" id="progress-input" value="{{$lesson_progress ? $lesson_progress->progress : 0}}">
        <button type="submit">Send</button>
    </form>

@endsection


@section('js')
    <script>


        let solutionbuttons = document.querySelectorAll('.toggle-solution');
        solutionbuttons.forEach(button => {
            let blockid = button.dataset.blockid;
            let solutions = document.querySelectorAll(`.solution-${blockid}`);

            solutions.forEach(solution => {
                    solution.style.display = 'none';
                }
            )

            button.addEventListener('click', () => {
                let first_solution = solutions[0];
                let ishidden = first_solution.style.display === 'none';
                let display = ishidden ? 'block' : 'none';

                console.log(solutions);

                solutions.forEach(solution => {
                    solution.style.display = display;
                });
                button.textContent = ishidden ? 'hide solution' : 'show solution';
            });

        });

        let maxProgress = 0;
        let completedcheckbox = document.querySelector('.completed_checkbox');
        let sent = completedcheckbox.checked;


        window.addEventListener('scroll', () => {
            const scrollTop = window.scrollY+document.getElementById('progress-input').value;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;

            const progress = (scrollTop / docHeight) * 100;

            // update visual bar (optional)
            if (progress > maxProgress) {
                maxProgress = progress;
                document.getElementById('scroll-progress').style.width = maxProgress + '%';
            }

            // ✅ trigger when > 90%
            if (maxProgress >= 90 && !sent) {
                sent = true;

                document.getElementById('progress-input').value = Math.round(maxProgress);

                document.getElementById('progress-form').submit();
            }
        });



    </script>
@endsection

