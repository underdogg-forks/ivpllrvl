<?php

use Illuminate\Support\Facades\Route;
use Modules\Sessions\Controllers\SessionsController;

// Route registrations for sessions::SessionsController.

Route::get( 'sessions/index', [SessionsController::class, 'index'])->name('sessions.index');
Route::get( 'sessions/login', [SessionsController::class, 'login'])->name('sessions.login');
Route::get( 'sessions/logout', [SessionsController::class, 'logout'])->name('sessions.logout');
