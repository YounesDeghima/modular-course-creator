@extends('layouts.base')
@section('css')

    {{asset('css/modular-site.css')}}
@endsection

@section('back-button')
    <a class="back-button" href="{{route('admin.courses.index')}}">{{$course->name}}</a>
@endsection

@section('main')

    <div class="blocks-container" id="blocks-container">
        <div class="route">{{$course->name}}</div>
        <div class="block-adder">
            <button id="block-adder">+</button>
            <div class="popup" id="block-popup">

                <form id="new-block-form" method="POST" action="{{route('admin.courses.chapters.store',$course)}}">
                    @csrf
                    <label>Name:</label>
                    <input class="value-input" type="text" name="name" required>

                    <label>chapter-number:</label>
                    <input class="value-input" type="number" name="chapter_number" min="1" max="99" required>

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

                        <form action="{{route('admin.courses.chapters.update',['course'=>$course->id,'chapter'=>$chapter->id])}}" method="post">
                            @csrf
                            @method('PUT')

                                <div class="info-row">
                                    <label  for="name">name</label>
                                    <input class="value-input" type="text"  name="name" value="{{$chapter->name}}">
                                </div>
                                <div class="info-row">
                                    <label  for="name">number</label>
                                    <input class="value-input" type="text"  name="chapter_number" value="{{$chapter->chapter_number}}">
                                </div>



                            <div class="info-row">
                                <label for="description">description</label>
                                <textarea name="description" class="value-input" >{{$chapter->description}}</textarea>
                            </div>

                            <input class="value-input update-button" type="submit" name="update" value="update" >

                        </form>


                        <form action="{{route('admin.courses.chapters.destroy',[$course,$chapter])}}" method="post">
                            @csrf
                            @method('DELETE')
                            <input type="submit" name="chapter-delete" class="block-delete delete-button" value="delete">
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
            adder.style.visibility= 'visible';
            adder.style.opacity= 1;
        });

        closeBtn.addEventListener('click', () => {
            adder.style.visibility= 'hidden';
            adder.style.opacity= 0;
        });



    </script>
@endsection

