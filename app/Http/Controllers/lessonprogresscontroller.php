<?php

namespace App\Http\Controllers;

use App\Models\lesson_progress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class lessonprogresscontroller extends Controller
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
    public function store(Request $request,$lesson)
    {
        $userId = Auth::id();


        $validated = $request->validate([
            'progress' => 'required|string|max:255',
        ]);
        $validated['user_id'] = $userId;
        $validated['lesson_id'] = $lesson;

        $lesson_progress = lesson_progress::create($validated);


        return redirect()->back();


    }

    /**
     * Display the specified resource.
     */
    public function show(lesson_progress $lesson_progress)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(lesson_progress $lesson_progress)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, lesson_progress $lesson_progress)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(lesson_progress $lesson_progress)
    {
        //
    }
}
