@extends('layouts.admin-base')
@section('css')

    <link rel="stylesheet" href="{{asset('css/modular-site.css')}}">
    <link rel="stylesheet" href="{{asset('css/preview.css')}}">
@endsection
@section('main')

    <div class="blocks-container" id="blocks-container">


        <div class="blocks">
            @foreach($lessons as $lesson)
                <div class="block">

                    <div class="block-top">
                        <div class="info-row">
                            <label for="name">Title</label>
                            <input class="value-input" type="text" name="title" value="{{$lesson->title}}" readonly>
                        </div>

                        <div class="info-row">
                            <label for="description">Description</label>
                            <textarea name="description" class="value-input description" style="height: 200px" readonly>{{$chapter->description}}</textarea>
                        </div>

                        <a href={{route('admin.preview.blocks',['year','course'=>$course,'chapter'=>$chapter,'lesson'=>$lesson])}}>start lesson</a>

                    </div>

                </div>
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

