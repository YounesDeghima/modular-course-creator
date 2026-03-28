<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="{{asset('css/admin-page.css')}}">
    @yield('css')


</head>

<body>
<header>
    <nav>
        <div class="left-side">
            <img src="#" class="logo">


            <a href="{{route('login_page')}}">logout</a>
        </div>




        <a href='{{route('admin.main')}}' data-item='Home'>Home</a>
        <a href='{{route('admin.dashboard')}}' data-item='About'>Users</a>
        <a href='{{route('admin.courses.index')}}' data-item='Projects'>modular site</a>
        <a href="{{route('admin.preview.years')}}" data-item='preview'>preview</a>


        <div class="right-side" id="right-side">
            <p>user :
                {{$id}}
            </p>
            <div class="popup" id="popup">

                <p>name :
                    {{$name}}

                </p>

                <p>email :
                    {{$email}}
                </p>

                <form action="" method="post">
                    <input type="submit" value="delete-user" name="delete-user" id="delete-user">
                </form>

            </div>
        </div>
    </nav>
</header>
<div class="middle">
    <div class="side-bar">

        @yield('sidebar-elements')



    </div>
    <main>
        @yield('navigation')
        @yield('main')
    </main>
</div>



</body>
@yield('js')

</html>
