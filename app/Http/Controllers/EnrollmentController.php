<?php

namespace App\Http\Controllers;

use App\Models\course;
use App\Models\CourseEnrollment;
use App\Models\user;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    // ── Student: self-enroll ──────────────────────────────────────────────────

    public function store(course $course)
    {
        $userId = Auth::id();

        // Already enrolled — idempotent
        if (CourseEnrollment::where('user_id', $userId)->where('course_id', $course->id)->exists()) {
            return response()->json(['ok' => true, 'already' => true]);
        }

        CourseEnrollment::create([
            'user_id'   => $userId,
            'course_id' => $course->id,
            'forced'    => false,
        ]);

        return response()->json(['ok' => true]);
    }

    // ── Student: unenroll (only if not forced) ────────────────────────────────

    public function destroy(course $course)
    {
        $userId = Auth::id();

        $enrollment = CourseEnrollment::where('user_id', $userId)
            ->where('course_id', $course->id)
            ->firstOrFail();

        if ($enrollment->forced) {
            return response()->json(['ok' => false, 'message' => 'This course was assigned by an admin and cannot be removed.'], 403);
        }

        $enrollment->delete();

        return response()->json(['ok' => true]);
    }

    // ── Admin: force-enroll a user ────────────────────────────────────────────

    public function adminStore(user $user, course $course)
    {


        $existing = CourseEnrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing) {
            // Upgrade to forced if not already
            $existing->update(['forced' => true]);
            return response()->json(['ok' => true, 'already' => true]);
        }

        CourseEnrollment::create([
            'user_id'   => $user->id,
            'course_id' => $course->id,
            'forced'    => true,
        ]);

        return response()->json(['ok' => true]);
    }

    // ── Admin: remove enrollment from a user ─────────────────────────────────

    public function adminDestroy(user $user, course $course)
    {

        CourseEnrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->delete();

        return response()->json(['ok' => true]);
    }

    // ── Admin: course browser for a specific user (returns view) ─────────────

    public function adminBrowser(user $user)
    {


        $authUser = Auth::user();
        $courses  = course::where('status', 'published')->get();

        $enrolledIds = $user->enrolledCourses()->pluck('course_id')->toArray();

        return view('pages.admin.users.enrollment-browser', [
            'targetUser'  => $user,
            'courses'     => $courses,
            'enrolledIds' => $enrolledIds,
            'name'        => $authUser->name,
            'email'       => $authUser->email,
            'id'          => $authUser->id,
        ]);
    }

    // ── Student: course browser ───────────────────────────────────────────────

    public function browser()
    {
        $user    = Auth::user();
        $courses = course::where('status', 'published')->get();

        $enrolledMap = $user->enrolledCourses()
            ->withPivot('forced')
            ->get()
            ->keyBy('id');

        return view('pages.student.course-browser', [
            'courses'     => $courses,
            'enrolledMap' => $enrolledMap,
            'name'        => $user->name,
            'email'       => $user->email,
            'id'          => $user->id,
        ]);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function requireAdmin(): void
    {
        abort_if(Auth::user()->role !== 'admin', 403, 'Admins only.');
    }
}
