@extends('layouts.user-base')
@section('css')

    <link rel="stylesheet" href="{{asset('css/modular-site.css')}}">
    <link rel="stylesheet" href="{{asset('css/preview.css')}}">
@endsection

@section('navigation')
    <div class="navigation">
        <a href="{{route('user.preview.years')}}">home</a>
        <a>--></a>
        <a href="{{route('user.preview.backcourses',['year'=>$year,'branch'=>$course->branch])}}">{{$year}}-{{$course->branch}}</a>
    </div>
@endsection
@section('main')

    <div class="blocks-container" id="blocks-container">


        <div class="chapters-container">
            <ol class="chapters">
                @foreach($chapters as $chapter)
                    <li>
                        <a href="{{route('user.preview.lessons',['year'=>$year,'course'=>$course,'chapter'=>$chapter])}}">{{$chapter->title}}</a>
                        <ol class="lessons">
                            @foreach($chapter->lessons as $lesson)
                                <li><a href="{{route('user.preview.blocks',['year'=>$year,'course'=>$course,'chapter'=>$chapter,'lesson'=>$lesson])}}">{{$lesson->title}}</a></li>
                            @endforeach
                        </ol>
                    </li>
                @endforeach
            </ol>
        </div>

    </div>

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

            })
            }
        )


    </script>
@endsection

