<?php

namespace App\Http\Controllers\user;

use App\Models\user;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Course;
use App\Models\lesson_progress;


use App\Http\Controllers\Controller;


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

            return redirect()->route('user.home');
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


    public function home()
    {
        $user   = Auth::user();
        $courses = Course::where('status', 'published')->get();

        // Count courses with any progress
        $inProgress = 0;
        $completed  = 0;

        foreach ($courses as $course) {
            $progress = $course->progressForUser($user->id);
            if ($progress == 100) $completed++;
            elseif ($progress > 0) $inProgress++;
        }

        return view('pages.admin.preview.homepage', [
            'name'       => $user->name,
            'last_name'  => $user->last_name,
            'email'      => $user->email,
            'id'         => $user->id,
            'courses'    => $courses,
            'total'      => $courses->count(),
            'completed'  => $completed,
            'inProgress' => $inProgress,
        ]);
    }
    public function storeUser(Request $request)
    {
        $request->validate([
            'name'      => 'required|string',
            'last_name' => 'required|string',
            'email'     => 'required|email|unique:users',
            'password'  => 'required|min:6',
            'role'      => 'required|in:admin,user',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'last_name' => $request->last_name,
            'email'     => $request->email,
            'password'  => bcrypt($request->password),
            'role'      => $request->role,
        ]);

        return response()->json($user);
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name'      => 'required|string',
            'last_name' => 'required|string',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'role'      => 'required|in:admin,user',
        ]);

        $user->update($request->only('name', 'last_name', 'email', 'role'));
        return response()->json($user);
    }

    public function destroyUser(User $user)
    {
        $user->delete();
        return response()->json(['ok' => true]);
    }
}
