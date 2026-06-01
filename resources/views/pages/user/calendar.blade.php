@extends('layouts.calendar-user')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/calendar.css') }}">

    <style>
        .modal{
            display: flex;
        }

    </style>
@endsection

{{--@section('sidebar-elements')
    <livewire:calendar.eventcreate/>
@endsection--}}

@section('main')
    <livewire:calendar.calendar/>
@endsection





@section('js')
    {{--<script>
        window.CAL_IS_ADMIN = true;

        window.CAL_IS_ADMIN = true;

        document.addEventListener('livewire:updated', () => {
            console.log('livewire updated, CAL_EVENTS:', window.CAL_EVENTS);
            if (typeof render === 'function') render();
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="{{ asset('js/calendar.js') }}"></script>--}}
@endsection




