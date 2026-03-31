@extends('layouts.admin-base')
@section('css')

    <link rel="stylesheet" href="{{asset('css/modular-site-preview.css')}}">
    <link rel="stylesheet" href="{{asset('css/preview.css')}}">
@endsection
@section('navigation')
    <div class="navigation">
        <a href="{{route('admin.preview.years')}}">home</a>
    </div>
@endsection
@section('main')

    <div class="blocks-container" id="blocks-container">
        <div class="blocks">

            @foreach($courses as $course)

                <div class="block">


                    <form action="{{ route('user.course.progress.destroy',['course'=>$course,'progress'=>$course->progressForUser($id)]) }}" method="POST" onsubmit="return confirmReset()" style="display:inline;">
                        @csrf
                        @method('DELETE')

                        <button type="submit">Reset course progress</button>
                    </form>

                    <div class="course-progress-bar">
                        <div class="course-progress-fill"
                             data-progress="{{ $course->progressForUser($id) }}">
                        </div>
                    </div>

                    <div class="block-top">
                        <div class="info-row">
                            <label for="name">Title</label>

                            <input class="value-input" type="text" name="title" value="{{$course->title}}" readonly>
                        </div>

                        <div class="info-row">
                            <label for="description">Description</label>
                            <textarea name="description" class="value-input description" style="height: 200px" readonly>{{$course->description}}</textarea>
                        </div>

                        <a href="{{route('admin.preview.chapters',['year'=>$year,'course'=>$course->id])}}">view chapters</a>

                    </div>

                </div>
            @endforeach
        </div>

    </div>

@endsection


@section('js')
    <script>





        let courses = document.querySelectorAll('.blocks>.block');
        courses.forEach((course,i)=>{
            let progressFill = course.querySelector('.course-progress-fill');
            let progress = progressFill.dataset.progress;

            setTimeout(() => {
                progressFill.style.width = progress + '%';
            }, 50);
        })

        function confirmReset() {
            return confirm("Are you sure you want to reset this chapter's progress? This action cannot be undone.");
        }




    </script>
@endsection

