<?php

use App\Http\Controllers\blockcontroller;
use App\Http\Controllers\chaptercontroller;
use App\Http\Controllers\lessoncontroller;
use App\Http\Controllers\coursecontroller;
use App\Http\Controllers\admincontroller;



use App\Http\Controllers\logincontroller;
use App\Http\Controllers\previewcontroller;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login_page');
});

Route::get('/login',[logincontroller::class,'show'])->name('login_page');


Route::post('/login',[LoginController::class,'verify'])->name('verify_user_login');


Route::prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [admincontroller::class, 'dashboard'])->name('dashboard');
        Route::get('/main', [admincontroller::class, 'main'])->name('main');

        Route::resource('courses', coursecontroller::class);

        Route::scopeBindings()->group(function () {
            Route::resource('courses.chapters',chaptercontroller::class);
            Route::resource('courses.chapters.lessons', lessoncontroller::class);
            Route::resource('courses.chapters.lessons.blocks', blockcontroller::class);
        });
        Route::get('preview',function(){return(redirect()->route('admin.preview.years'));});
        Route::scopeBindings()->group(function () {
            Route::get('preview/years', [previewcontroller::class,'loadyears'])->name('preview.years');
            Route::get('preview/years/{year}/courses', [previewcontroller::class,'loadcourses'])->name('preview.courses');
            Route::get('preview/years/{year}/courses/{course}/chapters', [previewcontroller::class,'loadchapters'])->name('preview.chapters');
            Route::get('preview/years/{year}/courses/{course}/chapters/{chapter}/lessons', [previewcontroller::class,'loadlessons'])->name('preview.lessons');
            Route::get('preview/years/{year}/courses/{course}/chapters/{chapter}/lessons/{lesson}/blocks', [previewcontroller::class,'loadblocks'])->name('preview.blocks');

        });
        route::get('preview/years/{year}/courses/{course}/chapters/{chapter}/lessons/{lesson}/lastlesson',[previewcontroller::class,'lastlesson'])->name('preview.lastlesson');
        route::get('preview/years/{year}/courses/{course}/chapters/{chapter}/lessons/{lesson}/nextlesson',[previewcontroller::class,'nextlesson'])->name('preview.nextlesson');
    });
