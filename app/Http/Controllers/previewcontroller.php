<?php

namespace App\Http\Controllers;

use App\Models\block;
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

    public function loadblocks($year,course $course,chapter $chapter,lesson $lesson)
    {
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;

        $blocks = block::where('lesson_id','=',$lesson->id)->get();

        return view('pages.admin.preview.blocks',compact('course','chapter','lesson','blocks','year','name','email','id'));


    }


    public function lastlesson($year,course $course,chapter $chapter,lesson $lesson)
    {
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;



        $lastlesson = lesson::where('chapter_id','=',$chapter->id)
            ->where('lesson_number','<',$lesson->lesson_number)
            ->orderBy('lesson_number','desc')
            ->first();

        if(!$lastlesson){
            return redirect()->back();
        }
        else
        {
            $lesson=$lastlesson;

            $blocks = block::where('lesson_id','=',$lesson->id)->get();


            return view('pages.admin.preview.blocks',compact('course','chapter','lesson','blocks','year','name','email','id'));
        }


    }

    public function nextlesson($year,course $course,chapter $chapter,lesson $lesson)
    {
        $admin = Auth::user();
        $id = $admin->id;
        $name = $admin->name;
        $email = $admin->email;

        $nextlesson = lesson::where('chapter_id','=',$chapter->id)
            ->where('lesson_number','>',$lesson->lesson_number)
            ->orderBy('lesson_number','asc')
            ->first();



        if(!$nextlesson){
            return redirect()->back();
        }
        else{
            $lesson=$nextlesson;
            $blocks = block::where('lesson_id','=',$lesson->id)->get();

            if($blocks->isEmpty())
            {
                return redirect()->back();
            }
            return view('pages.admin.preview.blocks',compact('course','chapter','lesson','blocks','year','name','email','id'));
        }


    }




}
