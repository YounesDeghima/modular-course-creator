@extends('layouts.base')

@section('css')
    {{asset('css/admin-page.css')}}
@endsection

@section('menuItems')
    <li><a href='{{route('admin.main')}}' data-item='Home'>Home</a></li>
    <li><a href='{{route('admin.dashboard')}}' data-item='About'>Users</a></li>
    <li><a href='#' data-item='Projects'>modular site</a></li>

@endsection


@section('main')

    <div class="users">

        <table id="users_table">

            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Lastname</th>
                <th>Email</th>
                <th>Birthdate</th>
                <th>role</th>

            </tr>
            </thead>
            @foreach($users as $user)
                <tr>
                    <td>{{$user->id}}</td>
                    <td>{{$user->name}}</td>
                    <td>{{$user->email}}</td>

                </tr>
            @endforeach

        </table>
    </div>

@endsection
