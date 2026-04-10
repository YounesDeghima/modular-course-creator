<?php

namespace App\Http\Controllers;

use App\Models\course;
use App\Models\coursequestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class quizcontroller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(course $course)
    {
        $admin = Auth::user();
        if($admin->role=='admin'){
            $id = $admin->id;
            $name = $admin->name;
            $email = $admin->email;



        $questions = coursequestion::where('course_id','=', $course->id)->get();





            return view('pages.admin.coursequiz',compact('admin','id','name','email','course','questions' ));
        }
        return redirect()->back();

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(coursequestion $coursequestion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(coursequestion $coursequestion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, coursequestion $coursequestion)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(coursequestion $coursequestion)
    {
        //
    }
}
