@extends('layouts.admin-base')

@section('css')
    <link rel="stylesheet" href="{{asset('css/modular-site.css')}}">
    <link rel="stylesheet" href="{{asset('css/block-page.css')}}">
    <link rel="stylesheet" href="{{asset('css/preview.css')}}">
@endsection

@section('main')

    <div class="nav-button"><a href={{route('admin.preview.lastlesson',['year','course'=>$course,'chapter'=>$chapter,'lesson'=>$lesson])}}><</a></div>

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

    <div class="nav-button"><a href={{route('admin.preview.nextlesson',['year','course'=>$course,'chapter'=>$chapter,'lesson'=>$lesson])}}>></a></div>

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



    </script>
@endsection

