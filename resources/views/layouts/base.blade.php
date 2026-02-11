<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="@yield('css')">
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

                <form action="" method="post">
                    <input type="submit" value="delete-user" name="delete-user" id="delete-user">
                </form>

            </div>
        </div>
    </nav>
</header>
<div class="side-bar">

    <div class="options">
        <ul class="menuItems">
            @yield('menuItems')
        </ul>
    </div>

</div>
<main>

</main>

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
</body>

</html>
