<?php

namespace App\Http\Controllers;

use App\Models\chapter;
use App\Models\course;
use App\Models\lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class chaptercontroller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(course $course)
    {

        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;
        $chapters=chapter::where('course_id',$course->id)->get();
        return view('pages.admin.chapters',compact('chapters','course','id','name','email'));

    }



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request,course $course)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'chapter_number'=>'required|integer',
            'description' => 'required|string',
        ]);

        $validated['course_id'] = $course->id;

        chapter::create($validated);


        return redirect()->back();
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
    public function update(Request $request, course $course, chapter $chapter)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'chapter_number'=>'required|integer',
            'description' => 'required|string',
        ]);


        $chapter->update($validated);

        return redirect()->back()->with('success', 'lesson updated');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(course $course ,chapter $chapter)
    {
        $chapter = chapter::findOrFail($chapter->id);
        $chapter->delete();

        return redirect()->back()->with('success', 'chapter deleted');
    }
}
