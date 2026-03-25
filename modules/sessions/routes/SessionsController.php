<?php

use Illuminate\Support\Facades\Route;
use Modules\Sessions\Controllers\SessionsController;

// Route registrations for sessions::SessionsController.

Route::match(['GET'], 'sessions/index', [SessionsController::class, 'index'])->name('sessions.index');
Route::match(['GET'], 'sessions/login', [SessionsController::class, 'login'])->name('sessions.login');
Route::match(['GET'], 'sessions/logout', [SessionsController::class, 'logout'])->name('sessions.logout');
