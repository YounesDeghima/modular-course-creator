<?php

namespace App\Http\Controllers;

use App\Models\block;
use App\Models\chapter;
use App\Models\course;
use App\Models\lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class blockcontroller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(course $course,chapter $chapter,lesson $lesson)
    {

        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;
        $blocks=block::where('lesson_id',$lesson->id)->get();
        return view('pages.admin.blocks',compact('blocks','course','chapter','lesson','id','name','email'));

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
    public function store(Request $request,course $course,chapter $chapter,lesson $lesson)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:title,description,note,exercise,code',
            'content' => 'required|string',
            'block_number'=>'required|integer',
        ]);



        $validated['lesson_id'] = $lesson->id;


        block::create($validated);


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
    public function update(Request $request, course $course, chapter $chapter,lesson $lesson,block $block)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:title,description,note,exercise,code',
            'block_number'=>'required|integer',
            'content' => 'required|string',
        ]);


        $block->update($validated);

        return redirect()->back()->with('success', 'block updated');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(course $course ,chapter $chapter,lesson $lesson,block $block)
    {
        $block = block::findOrFail($block->id);
        $block->delete();

        return redirect()->back()->with('success', 'block deleted');
    }
}
