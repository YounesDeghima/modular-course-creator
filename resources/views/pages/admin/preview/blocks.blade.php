@extends('layouts.admin-base')

@section('css')
    <link rel="stylesheet" href="{{asset('css/modular-site.css')}}">
    <link rel="stylesheet" href="{{asset('css/block-page.css')}}">
    <link rel="stylesheet" href="{{asset('css/preview.css')}}">
@endsection

@section('main')

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
                        <pre><code>{{$block->content}}</code></pre>
                        @break
                    @case('note')
                        <div class="note">{{$block->content}}</div>
                        @break
                    @case('exercise')
                        <div class="exercise">
                            <strong>Q: {{$block->content}}</strong>
                            <button class="toggle-solution" data-blockid="{{$block->id}}">show solution</button>
                            @foreach($block->solutions as $solution)
                                <div class="solution solution-{{$block->id}}">{{$solution->content}}</div>
                            @endforeach

                        </div>
                @endswitch
            @endforeach
        </div>

    </div>

@endsection


@section('js')
    <script>

        const adder = document.getElementById('block-popup');
        const openBtn = document.getElementById('block-adder');
        const closeBtn = document.getElementById('close-popup');

        openBtn.addEventListener('click', () => {
            adder.style.visibility = 'visible';
            adder.style.opacity = 1;
        });

        closeBtn.addEventListener('click', () => {
            adder.style.visibility = 'hidden';
            adder.style.opacity = 0;
        });

        let years = Array.from(document.getElementsByClassName('year-input'));
        let branchs = Array.from(document.getElementsByClassName('branch-input'));
        let branchlabels = Array.from(document.getElementsByClassName('branch-label'));

        function togglebranch(year, i) {
            console.log(i);
            if (parseInt(year.value) > 1) {

                branchs[i].style.display = 'block';
                branchlabels[i].style.display = 'block';
                branchs[i].value = 'mi';

            } else {
                branchs[i].style.display = 'none';
                branchlabels[i].style.display = 'none';

            }
        }

        years.forEach((year, i) => {
            year.addEventListener('change', () => togglebranch(year, i));

        });

        years.forEach((year, i) => togglebranch(year, i));

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

