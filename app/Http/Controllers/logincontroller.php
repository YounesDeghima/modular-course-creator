<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class logincontroller extends Controller
{

    function show()
    {
        return view('auth/login');
    }
    public function verify(Request $request)
    {


        $credentials =$request->only('email','password');

        if(Auth::attempt($credentials))
        {

            return(redirect('dashboard'));

        }
        return back()->withErrors(['email'=>'invalid email','password'=>'invalid password']);
    }
}
