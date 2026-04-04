<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Course;
use App\Models\lesson_progress;

class UserController extends Controller
{
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

        return view('pages.user.home', [
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
}
