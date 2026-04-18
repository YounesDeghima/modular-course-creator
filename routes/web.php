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
use App\Http\Controllers\quizcontroller;
use App\Http\Controllers\signupcontroller;
use App\Http\Controllers\user\usercontroller;
use App\Http\Controllers\lessonprogresscontroller;
use App\Http\Controllers\userprofilecontroller;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\eventcontroller;
use App\Http\Middleware\updateLastSeen;
use App\Http\Controllers\AIController;


Route::get('/', function () {
    return redirect()->route('login_page');
});

Route::get('/login',[logincontroller::class,'show'])->name('login_page');
Route::get('/signup',[signupcontroller::class,'show'])->name('signup_page');


Route::post('/login',[LoginController::class,'verify'])->name('verify_user_login');
Route::post('/signup',[signupController::class,'verify'])->name('verify_user_signup');



Route::middleware(['auth'])->group(function () {
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
});


Route::middleware(['auth', updateLastSeen::class])->group(function () {
    Route::prefix('admin')
        ->name('admin.')
        ->group(function () {

            // Control panel page
            Route::get('ai', [AIController::class, 'panel'])->name('ai.panel');

            // Connection tests
            Route::post('ai/test', [AIController::class, 'test'])->name('ai.test');
            Route::post('ai/test-mineru', [AIController::class, 'testMinerU'])->name('ai.test-mineru');

            // Upload & Job management
            Route::post('ai/jsonify', [AIController::class, 'jsonify'])->name('ai.jsonify');
            Route::get('ai/jobs', [AIController::class, 'jobsList'])->name('ai.jobs.list');
            Route::get('ai/status/{id}', [AIController::class, 'status'])->name('ai.status');
            Route::get('ai/logs/{id}', [AIController::class, 'logs'])->name('ai.logs');

            Route::get('ai/stats', [AIController::class, 'stats'])->name('ai.stats');

// Job detail & management
            Route::get('ai/jobs/{id}', [AIController::class, 'jobDetail'])->name('ai.jobs.detail');
            Route::patch('ai/jobs/{id}', [AIController::class, 'updateJob'])->name('ai.jobs.update');
            Route::post('ai/jobs/{id}/retry', [AIController::class, 'retry'])->name('ai.jobs.retry');
            Route::post('ai/jobs/{id}/cancel', [AIController::class, 'cancel'])->name('ai.jobs.cancel');
            Route::delete('ai/jobs/{id}', [AIController::class, 'deleteJob'])->name('ai.jobs.delete');
            Route::delete('ai/jobs/{id}/logs', [AIController::class, 'clearLogs'])->name('ai.jobs.clear-logs');

// Bulk actions
            Route::post('ai/bulk', [AIController::class, 'bulkAction'])->name('ai.bulk');

            // Save result to course
            Route::post('ai/store', [AIController::class, 'store'])->name('ai.store');

            // Block converter
            Route::post('ai/convert-block', [AIController::class, 'convertBlock'])->name('ai.convert-block');
// Debug
            Route::get('ai/debug-python',             function () {
                $python = base_path('scripts/.venv/Scripts/python.exe');
                $env = array_merge($_SERVER, $_ENV);
                $env['PYTHONHASHSEED'] = '0'; $env['PYTHONUNBUFFERED'] = '1'; $env['SystemRoot'] = 'C:\\Windows';
                foreach (array_keys($env) as $k) { if (str_starts_with($k, 'HTTP_')) unset($env[$k]); }
                $p = new \Symfony\Component\Process\Process([$python, '-c', 'import sys; print(sys.executable); import mineru; print("OK")'], null, $env, null, 30);
                $p->run();
                return response()->json(['python_exists' => file_exists($python), 'exit_code' => $p->getExitCode(), 'stdout' => $p->getOutput(), 'stderr' => $p->getErrorOutput()]);
            })->name('ai.debug');


            Route::post('/blocks/upload-media', [blockcontroller::class, 'uploadMedia'])
                ->name('blocks.upload-media');


            Route::get('/calendar', [EventController::class, 'adminIndex'])->name('calendar');
            Route::get('/dashboard', [admincontroller::class, 'dashboard'])->name('dashboard');
            Route::get('/userprofile/{userid}',[userprofilecontroller::class, 'userprofile'])->name('userProfile');
            Route::get('/main', [admincontroller::class, 'main'])->name('main');

            Route::post('/users',          [admincontroller::class, 'storeUser'])->name('users.store');
            Route::put('/users/{user}',    [admincontroller::class, 'updateUser'])->name('users.update');
            Route::delete('/users/{user}', [admincontroller::class, 'destroyUser'])->name('users.destroy');

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

                Route::resource('courses.quiz', quizcontroller::class);





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

            route::get('preview/courses/{course}/quiz',[previewcontroller::class , 'loadquiz'])->name('preview.courses.quiz');


        });});

Route::middleware(['auth', updateLastSeen::class])->group(function () {
    Route::prefix('user')
        ->name('user.')
        ->group(function () {

            Route::get('/calendar', [EventController::class, 'userIndex'])->name('calendar');

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


        });});

