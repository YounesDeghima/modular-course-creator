@extends('layouts.edditor')
@section('css')

    <link rel="stylesheet" href="{{asset('css/modular-site.css')}}">


    {{--    <link rel="stylesheet" href="{{asset('css/block-editor.css')}}">--}}
    <link rel="stylesheet" href="{{asset('css/admin-layout.css')}}">

@endsection

@section('back-button')
    <a class="back-button" href="{{route('admin.courses.index')}}">{{$course->title}}</a>
@endsection





@section('main')

    @fragment('main-content')
        <livewire:questions :questions="$questions"
                            :course="$course"/>
        <livewire:questioncreate :course="$course"/>



    @endfragment
@endsection

@section('sidebar-elements')









@endsection


@section('js')
    <script>






    </script>
@endsection

