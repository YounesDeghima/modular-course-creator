<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class usercontroller extends Controller
{
    public function main()
    {
        dd('brih');
        $user = Auth::user();

        if($user->role=='user')
        {
            $id = $user->id;
            $name = $user->name;
            $email = $user->email;

            return view('pages.user.main' ,compact( 'name', 'email','id'));
        }

        else{
            return redirect()->back();
        }


    }
}
