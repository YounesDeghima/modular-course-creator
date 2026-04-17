<?php

namespace App\Http\Controllers;

use App\Models\block;
use App\Models\chapter;
use App\Models\course;
use App\Models\coursequestion;
use App\Models\lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class previewcontroller extends Controller
{


//    public function loadyears()
//    {
//        $admin = Auth::user();
//        $id = $admin->id;
//        $name = $admin->name;
//        $email = $admin->email;
//        return view('pages.admin.preview.years',compact('name','email','id'));
//    }

    public function loadcourses(Request $request)
    {
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;



        $courses = course::where('status', 'published')
            ->when($request->branch, fn($q) =>
            $q->where('branch', $request->branch)
            )
            ->when($request->year, fn($q) =>
            $q->where('year', $request->year)
            )
            ->get();
        $branch = $request->branch;
        $year = $request->year;


        return view('pages.admin.preview.courses',compact('courses','branch','name','email','id'));
    }


    public function loadchapters(course $course)
    {
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;



        $chapters = chapter::where('status','=','published')
            ->where('course_id','=',$course->id)
            ->get();

        $overallProgress = $course->chapters->avg(fn($ch) => $ch->progressForUser($id)) ?? 0;
        $overallProgress = round($overallProgress);



        return view('pages.admin.preview.chapters',compact('course','chapters','name','email','id','overallProgress'));
    }

    public function loadlessons(course $course,chapter $chapter)
    {
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;

        $lessons = lesson::where('status','=','published')
            ->where('chapter_id','=',$chapter->id)
            ->orderBy('lesson_number','asc')
            ->get();

        return view('pages.admin.preview.lessons',compact('course','chapter','lessons','name','email','id'));

    }

    public function loadblocks(course $course,chapter $chapter,lesson $lesson)
    {
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;

        $blocks = block::where('lesson_id', $lesson->id)
            ->with('solutions')
            ->orderBy('block_number', 'asc')
            ->get();
        $prevlesson = lesson::where('chapter_id','=',$chapter->id)
            ->where('lesson_number','<',$lesson->lesson_number)
            ->where('status','=','published')
            ->orderBy('lesson_number','desc')
            ->first();


        $nextlesson = lesson::where('chapter_id','=',$chapter->id)
            ->where('status','=','published')
            ->orderBy('lesson_number','asc')->where('lesson_number','>',$lesson->lesson_number)
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


        return view('pages.admin.preview.blocks',compact(
            'course','chapter','lesson',
            'prevlesson','nextlesson','prevchapter','nextchapter',
            'blocks','name','email','id','lesson_progress'
        ));

    }

    public function loadquiz(course $course){
        $admin = Auth::user();

        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;

        $questions = coursequestion::where('course_id','=',$course->id)->get();
        return view('pages.admin.preview.coursequiz',compact('course','questions','name','email','id'));

    }


    public function user_loadcourses(Request $request)
    {
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;



        $courses = course::where('status', 'published')
            ->when($request->branch, fn($q) =>
            $q->where('branch', $request->branch)
            )
            ->when($request->year, fn($q) =>
            $q->where('year', $request->year)
            )
            ->get();
        $branch = $request->branch;
        $year = $request->year;


        return view('pages.user.courses',compact('courses','branch','name','email','id'));
    }


    public function user_loadchapters(course $course)
    {
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;



        $chapters = chapter::where('status','=','published')
            ->where('course_id','=',$course->id)
            ->get();




        return view('pages.user.chapters',compact('course','chapters','name','email','id'));
    }

    public function user_loadlessons(course $course,chapter $chapter)
    {
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;

        $lessons = lesson::where('status','=','published')
            ->where('chapter_id','=',$chapter->id)
            ->orderBy('lesson_number','asc')
            ->get();

        return view('pages.user.lessons',compact('course','chapter','lessons','name','email','id'));

    }

    public function user_loadblocks(course $course,chapter $chapter,lesson $lesson)
    {
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;

        $blocks = block::where('lesson_id', $lesson->id)
            ->with('solutions')
            ->orderBy('block_number', 'asc')
            ->get();
        $prevlesson = lesson::where('chapter_id','=',$chapter->id)
            ->where('lesson_number','<',$lesson->lesson_number)
            ->where('status','=','published')
            ->orderBy('lesson_number','desc')
            ->first();


        $nextlesson = lesson::where('chapter_id','=',$chapter->id)
            ->where('lesson_number','>',$lesson->lesson_number)
            ->where('status','=','published')
            ->orderBy('lesson_number','asc')
            ->first();

        $prevchapter = $chapter->course->chapters()
            ->where('chapter_number', '<', $chapter->chapter_number)
            ->where('status', 'published')
            ->orderByDesc('chapter_number')
            ->first();

        $lesson_progress = $lesson->progressForUser($id);
        $nextchapter = $chapter->course->chapters()
            ->where('chapter_number', '>', $chapter->chapter_number)
            ->where('status', 'published')
            ->orderBy('chapter_number', 'asc')
            ->first();


        return view('pages.user.blocks',compact(
            'course','chapter','lesson','prevlesson',
            'nextlesson', 'prevchapter','nextchapter','blocks',
            'name','email','id','lesson_progress'
        ));

    }



}
