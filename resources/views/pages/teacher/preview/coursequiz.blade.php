@extends('layouts.edditor')
@section('css')

    <link rel="stylesheet" href="{{asset('css/modular-site.css')}}">


    {{--    <link rel="stylesheet" href="{{asset('css/block-editor.css')}}">--}}
    <link rel="stylesheet" href="{{asset('css/admin-layout.css')}}">
    <style>
        /* Container */
        .blocks-wrapper {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .route-header {
            margin-bottom: 20px;
        }

        .course-name {
            font-size: 24px;
            font-weight: 600;
        }

        /* Question Card */
        .block-row {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: box-shadow 0.2s ease;
        }

        .block-row:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        /* Question textarea */
        .title-style {
            width: 100%;
            font-size: 18px;
            font-weight: 500;
            border: none;
            outline: none;
            resize: none;
            margin-bottom: 15px;
        }

        /* Choices container */
        .choices {
            margin-top: 10px;
            padding-left: 10px;
            border-left: 3px solid #f1f5f9;
        }

        /* Each choice row */
        .choices > div {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        /* Choice textarea */
        .content-style {
            flex: 1;
            border: none;
            outline: none;
            resize: none;
            font-size: 14px;
        }

        /* Select styling */
        select {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            cursor: pointer;
        }

        /* Delete button */
        .block-row button {
            margin-top: 10px;
            background: #ef4444;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }

        .block-row button:hover {
            background: #dc2626;
        }

        /* Save button */
        .save-container {
            text-align: right;
            margin-top: 20px;
        }

        .btn-save-all {
            background: #10b981;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
        }

        .btn-save-all:hover {
            background: #059669;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            color: #6b7280;
            padding: 30px;
        }
    </style>
@endsection



@section('back-button')
    <a class="back-button" href="{{route('admin.courses.index')}}">{{$course->title}}</a>
@endsection

@section('main')

    @fragment('main-content')
        <livewire:quiz.coursequizpreview :course="$course" :questions="$questions"/>

    @endfragment
@endsection

@section('sidebar-elements')

@endsection


@section('js')
    <script>
    </script>
@endsection

