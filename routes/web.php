<?php

use App\Http\Controllers\blockcontroller;
use App\Http\Controllers\chaptercontroller;
use App\Http\Controllers\coursecontroller;
use App\Http\Controllers\admincontroller;
use App\Http\Controllers\lessoncontroller;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login',[LoginController::class,'show'])->name('login_page');
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
            Route::resource('courses.chapters', blockcontroller::class);
        });

    });
