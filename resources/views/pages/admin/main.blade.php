@extends('layouts.base')

@section('css')
    {{asset('css/admin-page.css')}}
@endsection

@section('menuItems')
    <li><a href='{{route('admin.main')}}' data-item='Home'>Home</a></li>
    <li><a href='{{route('admin.dashboard')}}' data-item='About'>Users</a></li>
    <li><a href='#' data-item='Projects'>modular site</a></li>

@endsection
