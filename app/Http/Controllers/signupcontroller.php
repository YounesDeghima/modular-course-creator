<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\user;


class signupcontroller extends Controller
{
    function show()
    {
        return view('auth/signup');
    }


    public function verify(Request $request)
    {

        $exists = User::where('email','=',$request->email)->exists();

        if (!$exists) {

            $user=User::create([
                'name' => $request->name,
                'last_name' => $request->lastname,
                'birthdate'=>$request->birthdate,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            Auth::login($user);
            return(redirect()->route('user.main'));
        }



        return back()->withErrors(['email' => 'Email already exists']);
    }
}


