<?php

namespace App\Http\Controllers;

use App\Models\chapter;
use App\Models\chapter_progress;
use App\Models\lesson;
use App\Models\lesson_progress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class courseprogresscontroller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(chapter_progress $chapter_progress,$course)
    {



        $user = Auth::user();
        $chapter_ids = chapter::where('status', 'published')
            ->where('course_id', $course)
            ->pluck('id');
        $lesson_ids = lesson::wherein('chapter_id',$chapter_ids)
            ->where('status', 'published')->pluck('id');


        $lesson_progress = lesson_progress::wherein('lesson_id',$lesson_ids)
            ->where('user_id',$user->id)
            ->delete();
        return redirect()->back();
    }
}
