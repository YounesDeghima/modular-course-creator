@extends('layouts.admin-base')
@section('css')

    <link rel="stylesheet" href="{{asset('css/modular-site.css')}}">
    <link rel="stylesheet" href="{{asset('css/preview.css')}}">
@endsection
@section('navigation')
    <div class="navigation">
        <a href="{{route('admin.preview.years')}}">home</a>
        <a>--></a>
        <a href="{{route('admin.preview.backcourses',['year'=>$year,'branch'=>$course->branch])}}">{{$year}}-{{$course->branch}}</a>
        <a>---></a>
        <a href="{{route('admin.preview.chapters',['year'=>$year,'course'=>$course])}}">{{$chapter->title}}</a>
    </div>
@endsection
@section('main')

    <div class="prev-lessons-container" id="blocks-container">

        <h1>{{$chapter->title}}</h1>
        <div class="prev-lessons">
            @foreach($lessons as $lesson)

                <a href={{route('admin.preview.blocks',['year'=>$year,'course'=>$course,'chapter'=>$chapter,'lesson'=>$lesson])}}>{{$lesson->title}}</a>

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




    </script>
@endsection

