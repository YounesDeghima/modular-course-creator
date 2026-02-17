<?php

namespace App\Http\Controllers;

use App\Models\block;
use App\Models\chapter;
use App\Models\course;
use App\Models\lesson;
use App\Models\exercisesolution;
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
        $blocks=block::where('lesson_id',$lesson->id)
                                    ->with('solutions')
                                    ->orderBy('block_number','asc')
                                    ->get();
        $block_count = $lesson->blocks->count();


        return view('pages.admin.newblocks',compact('blocks','block_count','course','chapter','lesson','id','name','email'));

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
            'title' => 'required|string|max:255',
            'type' => 'required|in:header,description,note,exercise,code',
            'content' => 'required|string',
            'block_number'=>'required|integer',
        ]);



        $validated['lesson_id'] = $lesson->id;

        $block=block::create($validated);

        if($block->type == 'exercise'){
            $block->solutions()->create([
                'solution_number'=>1,
                'block_id'=>$block->id,
            ]);

        }


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


        $direction = $request->input('update');

        if($direction === 'up'){
            $previous = block::where('lesson_id',$lesson->id)
                ->where('block_number','<',$block->block_number)
                ->orderBy('block_number','desc')
                ->first();

            if($previous){
                $temp = $block->block_number;
                $block->block_number=$previous->block_number;
                $previous->block_number = $temp;

                $block->save();
                $previous->save();

            }


            return back();

        }
        if($direction === 'down'){
            $previous = block::where('lesson_id',$lesson->id)
                ->where('block_number','>',$block->block_number)
                ->orderBy('block_number','asc')
                ->first();

            if($previous){
                $temp = $block->block_number;
                $block->block_number=$previous->block_number;
                $previous->block_number = $temp;

                $block->save();
                $previous->save();

            }


            return back();

        }



        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:header,description,note,exercise,code',
            'block_number'=>'required|integer',
            'content' => 'required|string',
        ]);


        if($block->type=='exercise'){
            foreach ($block->solutions as $solution) {
                $solution->content = $request->input('solution');

                $solution->save();

            }
        }

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
