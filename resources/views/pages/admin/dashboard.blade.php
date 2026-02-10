

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="{{asset('css/admin-page.css')}}">
</head>

<body>
<header>
    <nav>
        <div class="left-side">
            <img src="#" class="logo">


            <a href="logout.php">logout</a>
        </div>
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

                <form action="../main-page/main-page.php" method="post">
                    <input type="submit" value="delete-user" name="delete-user" id="delete-user">
                </form>

            </div>
        </div>
    </nav>
</header>
<main>
    <div class="users">

        <table id="users_table">

            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
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
</main>
<script>
    let rightside = document.getElementById('right-side');
    let input = document.getElementById('popup');



    let open = false;

    rightside.addEventListener("click", () => {
        if (open == false) {
            input.style.visibility = 'visible';
            open = true;
        }
        else {
            input.style.visibility = 'hidden';
            open = false;
        }


    });



</script>
</body>

</html>
