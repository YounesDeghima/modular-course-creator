@extends('layouts.admin-base')
@section('css')

    <link rel="stylesheet" href="{{asset('css/modular-site.css')}}">
@endsection

@section('back-button')
    <a class="back-button" href="{{route('admin.courses.index')}}">{{$course->title}}</a>
@endsection

@section('main')

    <div class="blocks-container" id="blocks-container">
        <div class="route">{{$course->title}}</div>
        <div class="block-adder">
            <button id="block-adder">+</button>
            <div class="popup" id="block-popup">

                <form id="new-block-form" method="POST" action="{{route('admin.courses.chapters.store',$course)}}">
                    @csrf
                    <label>Title:</label>
                    <input class="value-input" type="text" name="title" required>

                    <label>chapter-number:</label>
                    <input class="value-input" type="number" name="chapter_number" value="{{$chapter_count+1}}"
                           readonly>

                    <label>Description:</label>
                    <textarea class="value-input" name="description" required></textarea>

                    <div style="text-align:right; margin-top:10px;">
                        <button type="submit">Create Chapter</button>
                        <button type="button" id="close-popup">Cancel</button>
                    </div>
                </form>
            </div>

        </div>
        <div class="blocks">
            @foreach($chapters as $chapter)
                <div class="block">

                    <div class="block-top">

                        <form action="{{route('admin.courses.chapters.update',['course'=>$course->id,'chapter'=>$chapter->id])}}"
                              method="post">
                            @csrf
                            @method('PUT')

                            <div class="info-row">
                                <label for="name">Title</label>
                                <input class="value-input" type="text" name="title" value="{{$chapter->title}}">
                            </div>
                            <div class="info-row">
                                <label for="chapter_number">chapter number</label>
                                <input class="value-input" type="text" name="chapter_number"
                                       value="{{$chapter->chapter_number}}" readonly>
                            </div>

                            <div class="info-row">
                                <label for="description">description</label>
                                <textarea name="description" class="value-input" style="height: 200px">{{$chapter->description}}</textarea>
                            </div>

                            <input class="value-input update-button" type="submit" name="update" value="update">

                        </form>


                        <form action="{{route('admin.courses.chapters.destroy',[$course,$chapter])}}" method="post">
                            @csrf
                            @method('DELETE')
                            <input type="submit" name="chapter-delete" class="block-delete delete-button"
                                   value="delete">
                        </form>

                        <a href="{{route('admin.courses.chapters.lessons.index',['course'=>$course->id,'chapter'=>$chapter->id])}}">manage lessons</a>

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


    </script>
@endsection

