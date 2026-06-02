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

        // 1. Get all chapters for the sidebar
        $chapters = chapter::where('course_id', $course->id)
            ->orderBy('chapter_number', 'asc')
            ->get();

        // 2. Grab the first chapter and first lesson for the editor pane.
        $chapter = $chapters->first();
        $lesson  = null;

        if ($chapter) {
            $lesson = $chapter->lessons()
                ->orderBy('lesson_number', 'asc')
                ->first();
        }

        // 3. Load blocks for that lesson (empty collection when no lesson yet).
        $blocks = $lesson
            ? $lesson->blocks()->orderBy('block_number', 'asc')->get()
            : collect();

        // 4. Guard: Create placeholder chapter and lesson if none exist
        /*if (!$chapter) {
            $placeholder_chapter = chapter::create([
                'course_id' => $course->id,
                'title' => 'Chapter 1',
                'chapter_number' => 1,
                'description' => 'Add your chapter description here',
                'status' => 'draft'
            ]);

            $placeholder_chapter->lessons()->create([
                'title' => 'Lesson 1',
                'lesson_number' => 1,
                'content' => 'Add your lesson content here',
                'status' => 'draft'
            ]);

            $chapter = $placeholder_chapter;
            $lesson = $placeholder_chapter->lessons()->first();
            $blocks = $lesson->blocks()->orderBy('block_number', 'asc')->get();
        }*/

        // 5. Other data needed by the view
        $chapter_count = $chapters->count();
        $id    = $admin->id;
        $name  = $admin->name;
        $email = $admin->email;




        return view('pages.admin.chapters', compact(
            'chapters',
            'course',
            'chapter', // Fixed: Added singular $chapter
            'lesson',  // Fixed: Added singular $lesson
            'blocks',  // Fixed: Added $blocks
            'chapter_count',
            'id',
            'name',
            'email'
        ));
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
    public function store(Request $request, course $course)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'chapter_number' => 'required|integer',
            'description' => 'required|string',
            'status' => 'required|in:draft,published', // Add this
        ]);

        $validated['course_id'] = $course->id;
        $chapter = chapter::create($validated);

        // Auto-create a placeholder lesson for the new chapter
        $lesson = $chapter->lessons()->create([
            'title' => 'Lesson 1',
            'lesson_number' => 1,
            'content' => 'Add your lesson content here',
            'status' => $validated['status']
        ]);

        return response()->json([
            'chapter' => $chapter,
            'lesson' => $lesson
        ]);
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
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|in:draft,published',
            'chapter_number' => 'required|integer',
        ]);


        $chapter->update($validated);

        return redirect()->back()->with('success', 'Chapter updated');
    }
    public function publishAll(course $course)
    {
        // 1. Check if there is ANY draft anywhere (Chapter OR Lesson)
        $hasDrafts = $course->chapters()->where('status', 'draft')->exists() ||
            \App\Models\Lesson::whereIn('chapter_id', $course->chapters()->pluck('id'))
                ->where('status', 'draft')->exists();

        if ($hasDrafts) {
            // --- PUBLISH EVERYTHING ---
            $course->chapters()->update(['status' => 'published']);
            \App\Models\Lesson::whereIn('chapter_id', $course->chapters()->pluck('id'))
                ->update(['status' => 'published']);
            $msg = "Course is now 100% Live!";
        } else {
            // --- DRAFT EVERYTHING ---
            $course->chapters()->update(['status' => 'draft']);
            \App\Models\Lesson::whereIn('chapter_id', $course->chapters()->pluck('id'))
                ->update(['status' => 'draft']);
            $msg = "All content moved to Draft.";
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(course $course ,chapter $chapter)
    {
        $chapter->delete();

        return redirect()->back()->with('success', 'chapter deleted');
    }
}
