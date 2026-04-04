<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;

class admincontroller extends Controller
{
//    public function dashboard()
//    {
//        $admin = Auth::user();
//        $users = User::all();
//
//        return view('pages.admin.dashboard', [
//            'users' => $users,
//            'name'  => $admin->name,
//            'email' => $admin->email,
//            'id'    => $admin->id,
//        ]);
//    }

    public function main()
    {
        $admin = Auth::user();
        if ($admin->role !== 'admin') return redirect()->back();

        $courses = Course::all();

        return view('pages.admin.main', [
            'name'         => $admin->name,
            'email'        => $admin->email,
            'id'           => $admin->id,
            'totalUsers'   => User::count(),
            'totalCourses' => $courses->count(),
            'pubCourses'   => $courses->where('status', 'published')->count(),
            'draftCourses' => $courses->where('status', 'draft')->count(),
            'pubLessons'   => Lesson::where('status', 'published')->count(),
            'draftLessons' => Lesson::where('status', 'draft')->count(),
            'recentUsers'  => User::latest()->take(5)->get(),
        ]);
    }
    public function dashboard()
    {
        $admin = Auth::user();
        $users = User::orderBy('created_at', 'desc')->get();

        return view('pages.admin.dashboard', [
            'users'      => $users,
            'name'       => $admin->name,
            'email'      => $admin->email,
            'id'         => $admin->id,
            'totalUsers' => $users->count(),
            'totalAdmins'=> $users->where('role', 'admin')->count(),
            'totalStudents' => $users->where('role', 'user')->count(),
        ]);
    }

}
