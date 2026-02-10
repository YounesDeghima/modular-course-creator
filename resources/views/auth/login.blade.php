




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="{{asset('css/signnup-page.css')}}">
</head>

<body>
<div class="container">
    <form method="POST" action="{{ route('verify_user_login') }}">
        @csrf
        <div class="credentials">
            <div class="sensitive-info">
                <label for="email">email</label>
                <input type="email" name="email" id="email">

                <label for="password">password</label>
                <input type="password" name="password" id="password">
            </div>
            <div class="submit">
                <input type="submit" value="login" name="login" id="login">


            </div>
            <div class="sign-up">
                @error('email')
                {{ $message }}
                @enderror
                @error('password')
                {{$message}}
                @enderror
                <p>you don't have an account?</p>
                <button><a href="../signup-page/signup-page.php">sign-up</a></button>
            </div>

        </div>
    </form>
</div>
<script>
    let form = document.querySelector("form");
    let submit = document.getElementById("login");
    let inputs = document.querySelectorAll('input');

    let pass = document.getElementById("password");


    form.addEventListener("submit", (e) => {
        let complete = true;
        for (let input of inputs) {
            if (input != submit) {
                if (input.value.trim() === '') {
                    complete = false;
                    input.style.border = "1px solid red";
                }
                else {
                    input.style.border = 'none';
                }
            }
        }

        if (!complete) {
            e.preventDefault();
        }

    });
</script>
</body>

</html>
