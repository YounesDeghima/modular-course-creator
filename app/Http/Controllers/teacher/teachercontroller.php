<?php
// app/Http/Controllers/Teacher/TeacherController.php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\user;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherController extends Controller
{
    public function section(Request $request, Section $section)
    {
        $user = Auth::user();

        // Make sure this teacher is actually assigned to this section
        abort_unless(
            $user->assignedSections()->where('sections.id', $section->id)->exists(),
            403
        );

        $otherSections = $user->assignedSections()
            ->where('sections.id', '!=', $section->id)
            ->get();

        $user = Auth::user();
        $users = User::orderBy('created_at', 'desc')->get();

        return view('pages.shared.editor.sectionDashboard', [
            'user'=>$user,

            'name'       => $user->name,
            'email'      => $user->email,
            'id'         => $user->id,
            'section' => $section,
            'otherSections' => $otherSections,
        ]);

    }
}
