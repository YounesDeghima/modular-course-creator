<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class usercontroller extends Controller
{
    public function main()
    {

        $user = Auth::user();

        if($user->role=='user')
        {
            $id = $user->id;
            $name = $user->name;
            $email = $user->email;

            return view('pages.user.main' ,compact( 'name', 'email','id'));
        }else{
            if($user->role=='admin'){
            $id = $user->id;
            $name = $user->name;
            $email = $user->email;

            return view('pages.admin.main',compact( 'name', 'email','id'));}
            else{
                return redirect()->back();
            }
        }



    }
}
