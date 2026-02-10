<?php

use App\Http\Controllers\dashboardcontroller;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login',[LoginController::class,'show'])->name('login_page');
Route::post('/login',[LoginController::class,'verify'])->name('verify_user_login');
Route::get('/dashboard', [dashboardcontroller::class, 'showusers'])->name('dashboard_page');
