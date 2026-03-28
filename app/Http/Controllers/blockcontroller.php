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
    public function index(Course $course, Chapter $chapter, Lesson $lesson)
    {
        // 1. Get all the data first
        $blocks = $lesson->blocks()
            ->with('solutions')
            ->orderBy('block_number', 'asc')
            ->get();

        $block_count = $blocks->count();
        $chapters = $course->chapters()->with('lessons')->get();
        $chapter_count = $chapters->count();

        // 2. Get the user data for the layout (Crucial!)
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;

        // 3. The AJAX Check
        if (request()->ajax()) {
            if (request()->ajax()) {
                return view('pages.admin.chapters', compact(
                    'blocks',
                    'course',
                    'chapter',
                    'lesson',
                    'block_count',
                    'chapters',       // <--- Add this
                    'chapter_count', // <--- Add this
                    'id',
                    'name',
                    'email'
                ))->fragment('main-content');
            }
        }

        return view('pages.admin.chapters', compact(
            'blocks', 'block_count', 'course', 'chapter', 'lesson',
            'chapters', 'chapter_count', 'admin', 'id', 'name', 'email'
        ));
    }



    public function updateAll(Request $request, $courseId, $chapterId, $lessonId)
    {
        // 1. HANDLE REORDERING (If an arrow was clicked)
        // The 'move' value comes in as "blockId:direction" (e.g., "12:up")
        if ($request->has('move')) {
            $parts = explode(':', $request->move);
            $currentBlockId = $parts[0];
            $direction = $parts[1];

            $currentBlock = \App\Models\Block::findOrFail($currentBlockId);
            $currentOrder = $currentBlock->block_number;

            if ($direction === 'up') {
                // Find the block immediately above it
                $previousBlock = \App\Models\Block::where('lesson_id', $lessonId)
                    ->where('block_number', '<', $currentOrder)
                    ->orderBy('block_number', 'desc')
                    ->first();

                if ($previousBlock) {
                    // Swap their order numbers
                    $currentBlock->update(['block_number' => $previousBlock->block_number]);
                    $previousBlock->update(['block_number' => $currentOrder]);
                }
            } else {
                // Find the block immediately below it
                $nextBlock = \App\Models\Block::where('lesson_id', $lessonId)
                    ->where('block_number', '>', $currentOrder)
                    ->orderBy('block_number', 'asc')
                    ->first();

                if ($nextBlock) {
                    // Swap their order numbers
                    $currentBlock->update(['block_number' => $nextBlock->block_number]);
                    $nextBlock->update(['block_number' => $currentOrder]);
                }
            }

            // After moving, we usually want to save any text changes made too
        }

        // 2. BULK UPDATE CONTENT & TYPES
        // This loops through the 'blocks' array from the form
        if ($request->has('blocks')) {
            foreach ($request->blocks as $id => $data) {
                \App\Models\Block::where('id', $id)->update([
                    'content' => $data['content'],
                    'type'    => $data['type']
                ]);
            }
        }

        return back()->with('success', 'Lesson layout updated successfully!');
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
