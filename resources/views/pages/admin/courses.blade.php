@extends('layouts.base')
@section('css')

    {{asset('css/modular-site.css')}}
@endsection
@section('main')

    <div class="blocks-container" id="blocks-container">

        <div class="block-adder">
            <button id="block-adder">+</button>
            <div class="popup" id="block-popup">

                <form id="new-block-form" method="POST" action="{{route('admin.courses.store')}}">
                    @csrf
                    <label>Name:</label>
                    <input class="value-input" type="text" name="name" required>

                    <label>Year (1-3):</label>
                    <input class="value-input" type="number" name="year" min="1" max="3" required>

                    <label>Category:</label>
                    <input class="value-input" type="text" name="category" required>

                    <label>Description:</label>
                    <textarea class="value-input" name="description" required></textarea>

                    <div style="text-align:right; margin-top:10px;">
                        <button type="submit">Create Course</button>
                        <button type="button" id="close-popup">Cancel</button>
                    </div>
                </form>
            </div>

        </div>
        <div class="blocks">
            @foreach($courses as $course)
                <div class="block">

                    <div class="block-top">

                        <form action="{{route('admin.courses.update',$course->id)}}" method="post">
                            @csrf
                            @method('PUT')

                            <div>
                                <div class="info-row">
                                    <label  for="name">name</label>
                                    <input class="value-input" type="text"  name="name" value="{{$course->name}}">
                                </div>

                                <div class="info-row">
                                    <label for="year">year</label>
                                    <select name="year" >

                                        <option value="1" {{ $course->year == 1 ? 'selected' : '' }}>Year 1</option>
                                        <option value="2" {{ $course->year == 2 ? 'selected' : '' }}>Year 2</option>
                                        <option value="3" {{ $course->year == 3 ? 'selected' : '' }}>Year 3</option>
                                    </select>
                                </div>
                            </div>


                            <div class="info-row">
                                <label for="category">category</label>
                                <input class="value-input" type="text" name="category"  value="{{$course->category}}">
                            </div>

                            <div class="info-row">
                                <label for="description"></label>
                                <textarea name="description" class="value-input" >{{$course->description}}</textarea>
                            </div>

                            <input class="value-input" type="submit" name="update" value="update">

                        </form>


                        <form action="{{route('admin.courses.destroy',$course->id)}}" method="post">
                            @csrf
                            @method('DELETE')
                            <input type="submit" name="course-delete" class="block-delete" value="delete">
                        </form>

                        <a href="{{route('admin.courses.chapters.index',$course)}}">manage chapters</a>

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

