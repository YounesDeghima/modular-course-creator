@extends('layouts.base')
@section('css')
    {{asset('css/modular-site.css')}}
@endsection

@section('back-button')
    <a class="back-button" href="{{route('admin.courses.chapters.lessons.index',['course'=>$course->id,'chapter'=>$chapter->id])}}">{{$course->name}}->{{$chapter->name}}->{{$lesson->name}}</a>
@endsection
@section('main')

    <div class="blocks-container" id="blocks-container">
        <div class="route">{{$course->name}}->{{$chapter->name}}->{{$lesson->name}}</div>
        <div class="block-adder">
            <button id="block-adder">+</button>
            <div class="popup" id="block-popup">

                <form id="new-block-form" method="POST" action="{{route('admin.courses.chapters.lessons.blocks.store',['course'=>$course->id,'chapter'=>$chapter->id,'lesson'=>$lesson->id])}}">
                    @csrf
                    <label>Name:</label>
                    <input class="value-input" type="text" name="name" required>

                    <label>block-number:</label>
                    <input class="value-input" type="number" name="block_number" min="1" max="99" required>

                    <label>type:</label>
                    <select name="type">
                        <option value="title">title</option>
                        <option value="description">description</option>
                        <option value="note">note</option>
                        <option value="exercise">exercise</option>
                        <option value="code">code</option>
                    </select>

                    <label>content:</label>
                    <textarea class="value-input" name="content" required></textarea>

                    <div style="text-align:right; margin-top:10px;">
                        <button type="submit">create block</button>
                        <button type="button" id="close-popup">Cancel</button>
                    </div>
                </form>
            </div>

        </div>
        <div class="blocks">
            @foreach($blocks as $block)
                <div class="block">

                    <div class="block-top">

                        <form action="{{route('admin.courses.chapters.lessons.blocks.update',['course'=>$course->id,'chapter'=>$chapter->id,'lesson'=>$lesson->id,'block'=>$block->id])}}" method="post">
                            @csrf
                            @method('PUT')

                            <div class="info-row">
                                <label  for="name">name</label>
                                <input class="value-input" type="text"  name="name" value="{{$block->name}}">
                            </div>
                            <div class="info-row">
                                <label  for="name">number</label>
                                <input class="value-input" type="text"  name="block_number" value="{{$block->block_number}}">
                            </div>
                            <div class="info-row">
                                <select name="type">
                                    <option value="title" {{ $block->type == 'title' ? 'selected' : '' }}>title</option>
                                    <option value="description" {{ $block->type == 'description' ? 'selected' : '' }}>description</option>
                                    <option value="note" {{ $block->type == 'note' ? 'selected' : '' }}>note</option>
                                    <option value="exercise" {{ $block->type == 'exercise' ? 'selected' : '' }}>exercise</option>
                                    <option value="code" {{$block->type == 'code' ? 'selected' : ''}}>code</option>
                                </select>
                            </div>




                            <div class="info-row">
                                <label for="content">content</label>
                                <textarea name="content" class="value-input" >{{$block->content}}</textarea>
                            </div>

                            <input class="value-input update-button" type="submit" name="update" value="update" >

                        </form>


                        <form action="{{route('admin.courses.chapters.lessons.blocks.destroy',[$course,$chapter,$lesson,$block])}}" method="post">
                            @csrf
                            @method('DELETE')
                            <input type="submit" name="block-delete" class="block-delete delete-button" value="delete">
                        </form>



                    </div>


                </div>
            @endforeach
        </div>

    </div>

@endsection




@section('main')




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

