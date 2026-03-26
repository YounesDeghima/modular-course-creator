<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\user;

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
            $user = user::where('email','=',$request->email)->first();
            if($user->role == 'admin')
            {
                return(redirect()->route('admin.main'));
            }
            else if($user->role == 'user'){
                return(redirect()->route('user.main'));
            }


        }
        return back()->withErrors(['email'=>'invalid email','password'=>'invalid password']);
    }
}
