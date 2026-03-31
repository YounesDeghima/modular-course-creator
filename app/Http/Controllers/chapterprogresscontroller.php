<?php

namespace App\Http\Controllers;

use App\Models\chapter_progress;
use App\Models\lesson;
use App\Models\lesson_progress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class chapterprogresscontroller extends Controller
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
    public function show(chapter_progress $chapter_progress)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(chapter_progress $chapter_progress)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, chapter_progress $chapter_progress)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(chapter_progress $chapter_progress,$chapter)
    {
        $user = Auth::user();

        $lesson_ids = lesson::where('chapter_id',$chapter)->pluck('id');
        $lesson_progress = lesson_progress::wherein('lesson_id',$lesson_ids)
            ->where('user_id',$user->id)
            ->delete();
        return redirect()->back();
    }
}
