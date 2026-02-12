<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class admincontroller extends Controller
{
    public function dashboard()
    {
        $users = User::all();
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;

        return view('pages.admin.dashboard' ,compact('users', 'name', 'email','id'));
    }
    public function main()
    {

        $admin = Auth::user();
        if($admin->ROLE=='admin')
        {
            $id = $admin->id;
            $name = $admin->name;
            $email = $admin->email;
            return view('pages.admin.main' ,compact( 'name', 'email','id'));
        }
        else{
            return redirect()->back();
        }

    }
}
