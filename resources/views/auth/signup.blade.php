<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
session_start();
?>

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
    <form method="post" action="{{route('verify_user_signup')}}">
        @csrf
        <div class="credentials">
            <div class="name">
                <label for="name">name</label>
                <input type="text" name="name" id="name">

                <label for="lastname">lastname</label>
                <input type="text" name="lastname" id="lastname">

            </div>
            <div class="sensitive-info">
                <label for="birthdate">birth-date</label>
                <input type="date" name="birthdate" id="birthdate">

                <label for="email">email</label>
                <input type="email" name="email" id="email">

                <label for="password">password</label>
                <input type="password" name="password" id="password">

                <label for="confirm-password">confirm-password</label>
                <input type="password" name="confirm-password" id="confirm-password">
            </div>
            <div class="submit">
                <input type="submit" value="sign-up" name="signup" id="signup">
            </div>
            <div class="login">
                <p>already have an account?</p>
                <button><a href="{{route('login_page')}}">login</a></button>
            </div>
        </div>
    </form>
</div>
<script>
    let form = document.querySelector("form");
    let submit = document.getElementById("signupcontroller");
    let inputs = document.querySelectorAll('input');
    let confirmpass = document.getElementById('confirm-password');
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

        if (confirmpass.value != pass.value) {
            confirmpass.value = '';
            confirmpass.placeholder = 'pls renter the password correctly';
            complete = false;
        }

        if (!complete) {
            e.preventDefault();

        }

    });
</script>
</body>

</html>
