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

        // 2. Logic to prevent the "Undefined Variable" crash:
        // We grab the first chapter and its first lesson so the main part has something to show.
        $chapter = $chapters->first();
        $lesson = $chapter ? $chapter->lessons()->orderBy('lesson_number', 'asc')->first() : null;

        // 3. Get the blocks for that default lesson (if they exist)
        $blocks = $lesson ? $lesson->blocks()->orderBy('block_number', 'asc')->get() : collect();

        // 4. Other data you need
        $chapter_count = $chapters->count();
        $id = $admin->id;
        $name = $admin->name;
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


//    public function index(course $course)
//    {
//        $chapters = $course->chapters()->with('lessons')->get();
//
//        // Grab the first chapter and first lesson so the page has something to show
//        $chapter = $chapters->first();
//        $lesson = $chapter ? $chapter->lessons->first() : null;
//        $blocks = $lesson ? $lesson->blocks : collect();
//
//        return view('pages.admin.chapters', compact('course', 'chapters', 'chapter', 'lesson', 'blocks'));
//    }

    // Inside chaptercontroller.php

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
        chapter::create($validated);

        return redirect()->back()->with('success', 'Chapter created successfully');
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
        ]);

        $chapter->update($validated);

        return redirect()->back()->with('success', 'Chapter updated');
    }
    public function publishAll(course $course)
    {
        // Count how many are NOT published
        $draftCount = $course->chapters()->where('status', 'draft')->count();

        if ($draftCount > 0) {
            // If there is at least one draft, make EVERYTHING published
            $course->chapters()->update(['status' => 'published']);
            $message = 'All chapters are now Live!';
        } else {
            // If everything was already published, move EVERYTHING to draft
            $course->chapters()->update(['status' => 'draft']);
            $message = 'All chapters moved to Draft.';
        }

        return redirect()->back()->with('success', $message);
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
