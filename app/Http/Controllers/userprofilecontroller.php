<?php

namespace App\Http\Controllers;

use App\Models\user;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class userprofilecontroller extends Controller
{
    public function userprofile($userid){
        $actualuser = Auth::user();
        $name=$actualuser->name;
        $email=$actualuser->email;
        $id=$actualuser->id;

        $user = user::findOrFail($userid);
        $teacherId = 0;
        if($user->role == 'teacher'){
            $teacherId = $user->id;
        }


        return view('pages.admin.users.userprofile',compact('teacherId','actualuser','user','name','email','id'));
    }
}
