<?php

namespace App\Http\Controllers;

use App\Models\block;
use App\Models\chapter;
use App\Models\course;
use App\Models\coursequestion;
use App\Models\lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * FIX #9: All methods call Auth::user() assuming middleware guarantees auth.
 * Make sure routes are wrapped in middleware(['auth']) in web.php.
 * Each method now has a guard so a missing user fails gracefully.
 *
 * Route example (web.php):
 *   Route::middleware(['auth'])->group(function () {
 *       Route::get('/preview/courses', [previewcontroller::class, 'loadcourses'])->name('admin.preview.courses');
 *       ...
 *   });
 */
class previewcontroller extends Controller
{
    // ─── Shared helper ────────────────────────────────────────────────────────

    /**
     * FIX #9: centralised auth extraction — aborts 401 if user is somehow null
     * (guards against missing middleware on a route).
     */
    private function authUser(): array
    {
        $user = Auth::user();
        abort_if(is_null($user), 401, 'Unauthenticated.');
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ];
    }

    // ─── Admin preview routes ─────────────────────────────────────────────────

    public function loadcourses(Request $request)
    {
        ['id' => $id, 'name' => $name, 'email' => $email] = $this->authUser();

        $courses = course::where('status', 'published')
            ->when($request->branch, fn($q) => $q->where('branch', $request->branch))
            ->when($request->year,   fn($q) => $q->where('year',   $request->year))
            ->get();

        $branch = $request->branch;
        $year   = $request->year;

        return view('pages.admin.preview.courses', compact('courses', 'branch', 'name', 'email', 'id'));
    }

    public function loadchapters(course $course)
    {
        ['id' => $id, 'name' => $name, 'email' => $email] = $this->authUser();

        $chapters = chapter::where('status', 'published')
            ->where('course_id', $course->id)
            ->get();

        // FIX: use null-coalesce — avg() can return null on empty collection
        $overallProgress = round(
            $course->chapters->avg(fn($ch) => $ch->progressForUser($id)) ?? 0
        );

        return view('pages.admin.preview.chapters',
            compact('course', 'chapters', 'name', 'email', 'id', 'overallProgress'));
    }

    public function loadlessons(course $course, chapter $chapter)
    {
        ['id' => $id, 'name' => $name, 'email' => $email] = $this->authUser();

        $lessons = lesson::where('status', 'published')
            ->where('chapter_id', $chapter->id)
            ->orderBy('lesson_number', 'asc')
            ->get();

        return view('pages.admin.preview.lessons',
            compact('course', 'chapter', 'lessons', 'name', 'email', 'id'));
    }

    public function loadblocks(course $course, chapter $chapter, lesson $lesson)
    {
        ['id' => $id, 'name' => $name, 'email' => $email] = $this->authUser();

        // FIX: verify lesson belongs to chapter (IDOR guard)
        abort_if($lesson->chapter_id !== $chapter->id, 404);
        // FIX: verify chapter belongs to course (IDOR guard)
        abort_if($chapter->course_id !== $course->id, 404);

        $blocks = block::where('lesson_id', $lesson->id)
            ->with('solutions')
            ->orderBy('block_number', 'asc')
            ->get();

        $prevlesson = lesson::where('chapter_id', $chapter->id)
            ->where('lesson_number', '<', $lesson->lesson_number)
            ->where('status', 'published')
            ->orderByDesc('lesson_number')
            ->first();

        $nextlesson = lesson::where('chapter_id', $chapter->id)
            ->where('lesson_number', '>', $lesson->lesson_number)
            ->where('status', 'published')
            ->orderBy('lesson_number', 'asc')
            ->first();

        $lesson_progress = $lesson->progressForUser($id);

        $prevchapter = $chapter->course->chapters()
            ->where('chapter_number', '<', $chapter->chapter_number)
            ->where('status', 'published')
            ->orderByDesc('chapter_number')
            ->first();

        $nextchapter = $chapter->course->chapters()
            ->where('chapter_number', '>', $chapter->chapter_number)
            ->where('status', 'published')
            ->orderBy('chapter_number', 'asc')
            ->first();

        return view('pages.admin.preview.blocks', compact(
            'course', 'chapter', 'lesson',
            'prevlesson', 'nextlesson', 'prevchapter', 'nextchapter',
            'blocks', 'name', 'email', 'id', 'lesson_progress'
        ));
    }

    public function loadquiz(course $course)
    {
        ['id' => $id, 'name' => $name, 'email' => $email] = $this->authUser();

        $questions = coursequestion::where('course_id', $course->id)->get();

        return view('pages.admin.preview.coursequiz',
            compact('course', 'questions', 'name', 'email', 'id'));
    }

    // ─── Student (user) routes ────────────────────────────────────────────────

    public function user_loadcourses(Request $request)
    {
        ['id' => $id, 'name' => $name, 'email' => $email] = $this->authUser();

        $courses = course::where('status', 'published')
            ->when($request->branch, fn($q) => $q->where('branch', $request->branch))
            ->when($request->year,   fn($q) => $q->where('year',   $request->year))
            ->get();

        $branch = $request->branch;
        $year   = $request->year;

        return view('pages.user.courses', compact('courses', 'branch', 'name', 'email', 'id'));
    }

    public function user_loadchapters(course $course)
    {
        ['id' => $id, 'name' => $name, 'email' => $email] = $this->authUser();

        $chapters = chapter::where('status', 'published')
            ->where('course_id', $course->id)
            ->get();

        return view('pages.user.chapters',
            compact('course', 'chapters', 'name', 'email', 'id'));
    }

    public function user_loadlessons(course $course, chapter $chapter)
    {
        ['id' => $id, 'name' => $name, 'email' => $email] = $this->authUser();

        // FIX: IDOR guard — chapter must belong to course
        abort_if($chapter->course_id !== $course->id, 404);

        $lessons = lesson::where('status', 'published')
            ->where('chapter_id', $chapter->id)
            ->orderBy('lesson_number', 'asc')
            ->get();

        return view('pages.user.lessons',
            compact('course', 'chapter', 'lessons', 'name', 'email', 'id'));
    }

    public function user_loadblocks(course $course, chapter $chapter, lesson $lesson)
    {
        ['id' => $id, 'name' => $name, 'email' => $email] = $this->authUser();

        // FIX: IDOR guards — prevent accessing arbitrary lessons/chapters via URL manipulation
        abort_if($chapter->course_id  !== $course->id,  404);
        abort_if($lesson->chapter_id  !== $chapter->id, 404);

        $blocks = block::where('lesson_id', $lesson->id)
            ->with('solutions')
            ->orderBy('block_number', 'asc')
            ->get();

        $prevlesson = lesson::where('chapter_id', $chapter->id)
            ->where('lesson_number', '<', $lesson->lesson_number)
            ->where('status', 'published')
            ->orderByDesc('lesson_number')
            ->first();

        $nextlesson = lesson::where('chapter_id', $chapter->id)
            ->where('lesson_number', '>', $lesson->lesson_number)
            ->where('status', 'published')
            ->orderBy('lesson_number', 'asc')
            ->first();

        $lesson_progress = $lesson->progressForUser($id);

        $prevchapter = $chapter->course->chapters()
            ->where('chapter_number', '<', $chapter->chapter_number)
            ->where('status', 'published')
            ->orderByDesc('chapter_number')
            ->first();

        $nextchapter = $chapter->course->chapters()
            ->where('chapter_number', '>', $chapter->chapter_number)
            ->where('status', 'published')
            ->orderBy('chapter_number', 'asc')
            ->first();

        return view('pages.user.blocks', compact(
            'course', 'chapter', 'lesson', 'prevlesson',
            'nextlesson', 'prevchapter', 'nextchapter', 'blocks',
            'name', 'email', 'id', 'lesson_progress'
        ));
    }
}
