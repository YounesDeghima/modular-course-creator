@extends('layouts.edditor')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/calendar.css') }}">

    <style>
        .modal{
            display: flex;
        }

    </style>
@endsection

@section('sidebar-elements')
    <livewire:calendar.eventcreate/>
@endsection

@section('main')
<livewire:calendar.calendar/>
@endsection

@section('js')
    {{--<script>



        window.CAL_EVENTS   = @json($events);
        window.CAL_IS_ADMIN = true;

        // document.addEventListener('DOMContentLoaded', () => {
        //     // Set default CSRF header
        //     axios.defaults.headers.common['X-CSRF-TOKEN'] =
        //         document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        //
        //     // Initial render
        //     render();
        // });

    </script>
    <script src="{{ asset('js/axios.min.js') }}"></script>--}}
    {{--<script src="{{ asset('js/calendar.js') }}"></script>--}}
@endsection
