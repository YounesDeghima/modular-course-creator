@extends('layouts.base')

@section('css')
    {{asset('css/admin-page.css')}}
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

                <th>role</th>

            </tr>
            </thead>
            @foreach($users as $user)
                <tr>
                    <td>{{$user->id}}</td>
                    <td>{{$user->name}}</td>
                    <td>{{$user->lastname}}</td>
                    <td>{{$user->email}}</td>
                    <td>{{$user->ROLE}}</td>

                </tr>
            @endforeach

        </table>
    </div>

@endsection

@section('js')
    <script>
        let rightside = document.getElementById('right-side');

        let input = document.getElementById('popup');




        let open = false;

        rightside.addEventListener("click", () => {

            if (open == false) {
                input.style.visibility = 'visible';
                input.style.opacity=1;
                open = true;
            }
            else {
                input.style.visibility = 'hidden';
                input.style.opacity=0;
                open = false;
            }
        });



    </script>
@endsection
