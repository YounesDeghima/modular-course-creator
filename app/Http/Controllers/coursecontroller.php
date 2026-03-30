<?php

namespace App\Http\Controllers;

use App\Models\course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class coursecontroller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;

        $courses= Course::all();
        return view('pages.admin.courses',compact('courses','name','email','id'));
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

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'year' => 'required|in:1,2,3',
            'status' => 'required|in:draft,published',
            'description' => 'required|string',
        ]);



        if ($request->year == 1) {
            $validated['branch'] = 'none';
        }



        course::create($validated);

        return redirect()->back()->with('success', 'Course created successfully');
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
    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);



        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'year' => 'required|in:1,2,3',
            'branch' => 'required|in:mi,st,none',
            'description' => 'required|string',
            'status' => 'required|in:draft,published',
            ]);

        if ($request->year == 1) {
            $validated['branch'] = 'none';
        }


        $course->update($validated);


        return(redirect()->back()->with('success', 'Course updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $course->delete();

        return redirect()->back()->with('success', 'Course deleted');
    }

    public function toggleEverything()
    {
        $hasDrafts = course::where('status', 'draft')->exists();
        $newStatus = $hasDrafts ? 'published' : 'draft';

        // 1. Update all Courses
        course::query()->update(['status' => $newStatus]);

        // 2. Update all Chapters
        \App\Models\chapter::query()->update(['status' => $newStatus]);

        // 3. Update all Lessons
        \App\Models\lesson::query()->update(['status' => $newStatus]);

        return redirect()->back()->with('success', "Entire platform is now $newStatus");
    }
}
