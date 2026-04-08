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
        return view('pages.admin.userprofile',compact('user','name','email','id'));
    }
}
