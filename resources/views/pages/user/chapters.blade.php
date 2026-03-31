@extends('layouts.admin-base')
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

    <div class="blocks-container" id="blocks-container">


        <div class="chapters-container">
            <ol class="chapters">
                @foreach($chapters as $chapter)


                    <li>
                        <form action="{{ route('user.chapter.progress.destroy',['chapter'=>$chapter,'progress'=>$chapter->progressForUser($id)]) }}" method="POST" onsubmit="return confirmReset()" style="display:inline;">
                            @csrf
                            @method('DELETE')

                            <button type="submit">Reset chapter progress</button>
                        </form>
                        <a href="{{route('admin.preview.lessons',['course'=>$course,'chapter'=>$chapter])}}">{{$chapter->title}}</a>

                        <div class="chapter-progress-bar">
                            <div class="chapter-progress-fill"
                                 data-progress="{{ $chapter->progressForUser($id) }}">
                            </div>
                        </div>

                        <ol class="lessons">
                            @foreach($chapter->lessons as $lesson)
                                @if($lesson->status=='published')

                                    @if($lesson->progressForUser($id) && $lesson->progressForUser($id)->progress > 90)

                                        <li><a style="color: #2ecc71" href="{{route('admin.preview.blocks',['course'=>$course,'chapter'=>$chapter,'lesson'=>$lesson])}}">{{$lesson->title}}</a></li>
                                    @else
                                        <li><a href="{{route('admin.preview.blocks',['course'=>$course,'chapter'=>$chapter,'lesson'=>$lesson])}}">{{$lesson->title}}</a></li>
                                    @endif

                                @endif
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

