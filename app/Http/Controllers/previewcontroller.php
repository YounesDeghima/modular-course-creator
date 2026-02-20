<?php

namespace App\Http\Controllers;

use App\Models\chapter;
use App\Models\course;
use App\Models\lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class previewcontroller extends Controller
{


    public function loadyears()
    {
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;
        return view('pages.admin.preview.years',compact('name','email','id'));
    }

    public function loadcourses(Request $request,$year)
    {
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;

        $branch = $request['branch'];

       $courses=course::where('status','=','published')
                                ->where('year','=',$year)
                                ->where('branch','=',$branch)
                                ->get();


        return view('pages.admin.preview.courses',compact('courses','year','branch','name','email','id'));
    }

    public function loadchapters($year,course $course)
    {
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;



        $chapters = chapter::where('status','=','published')
            ->where('course_id','=',$course->id)
            ->get();



        return view('pages.admin.preview.chapters',compact('course','chapters','year','name','email','id'));
    }

    public function loadlessons($year,course $course,chapter $chapter)
    {
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;

        $lessons = lesson::where('status','=','published')
            ->where('chapter_id','=',$chapter->id)
            ->get();

        return view('pages.admin.preview.lessons',compact('course','chapter','lessons','year','name','email','id'));

    }



}
