@extends('layouts.edditor')
@section('css')

    <link rel="stylesheet" href="{{asset('css/modular-site-preview.css')}}">
    <link rel="stylesheet" href="{{asset('css/preview.css')}}">
@endsection
@section('navigation')
    <div class="navigation">

    </div>
@endsection
@section('main')
    <div class="search">
        <form class="filter-form" method="GET" action="{{ route('admin.preview.courses') }}">

            <select name="year" class="year-input">
                <option value="">All Years</option>
                <option value="1" {{ request('year')=='1' ? 'selected' : '' }}>Year 1</option>
                <option value="2" {{ request('year')=='2' ? 'selected' : '' }}>Year 2</option>
                <option value="3" {{ request('year')=='3' ? 'selected' : '' }}>Year 3</option>
            </select>

            <select name="branch" class="branch-input">
                <option value="">All Branches</option>
                <option value="st" {{ request('branch')=='st' ? 'selected' : '' }}>st</option>
                <option value="mi" {{ request('branch')=='mi' ? 'selected' : '' }}>mi</option>

            </select>

            <button type="submit">Filter</button>
        </form>
    </div>

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

                        <a href="{{route('admin.preview.chapters',['course'=>$course->id])}}">view chapters</a>

                    </div>

                </div>
            @endforeach
        </div>

    </div>

@endsection


@section('js')
    <script>
        document.querySelectorAll('.filter-form').forEach(form => {
            const year = form.querySelector('.year-input');
            const branch = form.querySelector('.branch-input');


            function toggleBranch() {
                if (parseInt(year.value) > 1 || year.value == '') {

                    branch.style.visibility = 'visible';

                } else {
                    branch.value='';
                    branch.style.visibility = 'hidden';

                }
            }

            year.addEventListener('change', toggleBranch);

            // run once on load
            toggleBranch();
        });




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

