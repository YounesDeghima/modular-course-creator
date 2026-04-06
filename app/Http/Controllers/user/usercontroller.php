<?php

namespace App\Http\Controllers\user;

use App\Models\user;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\course;
use App\Models\event;
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

        $now = now();

        $currentEvents = event::whereDate('start_date', '<=', $now)
            ->whereDate('end_date', '>=', $now)
            ->orderBy('start_date', 'asc')
            ->get(); //

        // 2. Fetch the next 5 events starting AFTER today
        $upcomingEvents = event::where('start_date', '>', $now->endOfDay())
            ->orderBy('start_date', 'asc')
            ->take(5)
            ->get(); //

//        return view('homepage', compact('currentEvents', 'upcomingEvents'));

        return view('pages.user.homepage', [
            'name'           => $user->name,
            'last_name'      => $user->last_name,
            'email'          => $user->email,
            'id'             => $user->id,
            'courses'        => $courses,
            'total'          => $courses->count(),
            'completed'      => $completed,
            'inProgress'     => $inProgress,
            'currentEvents'  => $currentEvents,
            'upcomingEvents' => $upcomingEvents,
        ]);
    }

}
