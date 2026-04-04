<?php

use App\Http\Controllers\admincontroller;
use App\Http\Controllers\blockcontroller;
use App\Http\Controllers\chaptercontroller;
use App\Http\Controllers\chapterprogresscontroller;
use App\Http\Controllers\coursecontroller;
use App\Http\Controllers\courseprogresscontroller;
use App\Http\Controllers\lessoncontroller;
use App\Http\Controllers\logincontroller;
use App\Http\Controllers\previewcontroller;
use App\Http\Controllers\signupcontroller;
use App\Http\Controllers\user\usercontroller;
use App\Http\Controllers\lessonprogresscontroller;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return redirect()->route('login_page');
});

Route::get('/login',[logincontroller::class,'show'])->name('login_page');
Route::get('/signup',[signupcontroller::class,'show'])->name('signup_page');


Route::post('/login',[LoginController::class,'verify'])->name('verify_user_login');
Route::post('/signup',[signupController::class,'verify'])->name('verify_user_signup');






Route::prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [admincontroller::class, 'dashboard'])->name('dashboard');
        Route::get('/main', [admincontroller::class, 'main'])->name('main');

        Route::put('courses/toggle-everything', [coursecontroller::class, 'toggleEverything'])
            ->name('courses.toggle-everything');

        Route::resource('courses', coursecontroller::class);

        Route::scopeBindings()->group(function () {

            Route::put('courses/{course}/chapters/publish-all', [chaptercontroller::class, 'publishAll'])
                ->name('courses.chapters.publish-all');
            Route::put('courses/{course}/chapters/{chapter}/publish-all-lessons', [lessoncontroller::class, 'publishAll'])
                ->name('courses.chapters.lessons.publish-all');
            Route::put('courses/{course}/chapters/{chapter}/lessons/toggle-all', [lessoncontroller::class, 'toggleAll'])
                ->name('courses.chapters.lessons.toggle-all');

            Route::resource('courses.chapters',chaptercontroller::class);
            Route::resource('courses.chapters.lessons', lessoncontroller::class);

            Route::put('courses/{course}/chapters/{chapter}/lessons/{lesson}/blocks/update-all', [blockcontroller::class, 'updateAll'])
                ->name('courses.chapters.lessons.blocks.update-all');

            Route::resource('courses.chapters.lessons.blocks', blockcontroller::class);
        });

        Route::get('preview', function () {
            return (redirect()->route('admin.preview.courses'));
        });
        Route::scopeBindings()->group(function () {

            Route::get('preview/courses', [previewcontroller::class, 'loadcourses'])->name('preview.courses');
            Route::get('preview/branch/{branch}/courses', [previewcontroller::class, 'loadbackcourses'])->name('preview.backcourses');
            Route::get('preview/courses/{course}/chapters', [previewcontroller::class, 'loadchapters'])->name('preview.chapters');
            Route::get('preview/courses/{course}/chapters/{chapter}/lessons', [previewcontroller::class, 'loadlessons'])->name('preview.lessons');
            Route::get('preview/courses/{course}/chapters/{chapter}/lessons/{lesson}/blocks', [previewcontroller::class, 'loadblocks'])->name('preview.blocks');

        });

        route::get('preview/courses/{course}/chapters/{chapter}/lessons/{lesson}/lastlesson',[previewcontroller::class,'lastlesson'])->name('preview.lastlesson');
        route::get('preview/courses/{course}/chapters/{chapter}/lessons/{lesson}/nextlesson',[previewcontroller::class,'nextlesson'])->name('preview.nextlesson');
    });


Route::prefix('user')
    ->name('user.')
    ->group(function () {

        Route::get('/home', [usercontroller::class, 'home'])->name('home');

        Route::get('/main',[usercontroller::class,'main'])->name('main');

        Route::get('preview', function () {
            return (redirect()->route('user.preview.courses'));
        });
        Route::scopeBindings()->group(function () {
            Route::get('preview', [previewcontroller::class, 'user_loadyears'])->name('preview.years');
            Route::get('preview/courses', [previewcontroller::class, 'user_loadcourses'])->name('preview.courses');
            Route::get('preview/branch/{branch}/courses', [previewcontroller::class, 'user_loadbackcourses'])->name('preview.backcourses');
            Route::get('preview/courses/{course}/chapters', [previewcontroller::class, 'user_loadchapters'])->name('preview.chapters');
            Route::get('preview/courses/{course}/chapters/{chapter}/lessons', [previewcontroller::class, 'user_loadlessons'])->name('preview.lessons');
            Route::get('preview/courses/{course}/chapters/{chapter}/lessons/{lesson}/blocks', [previewcontroller::class, 'user_loadblocks'])->name('preview.blocks');

        });

        Route:: Resource('lesson.progress', lessonprogresscontroller::class);
        Route:: Resource('chapter.progress', chapterprogresscontroller::class);
        Route:: Resource('course.progress', courseprogresscontroller::class);


    });

