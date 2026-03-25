<?php

use Illuminate\Support\Facades\Route;
use Modules\Sessions\Controllers\SessionsController;

// Auto-generated routes for sessions::SessionsController actions.

Route::match(['GET'], 'sessions/sessions/authenticate', [SessionsController::class, 'authenticate'])->name('sessions.sessions.authenticate');
Route::match(['GET'], 'sessions/sessions/index', [SessionsController::class, 'index'])->name('sessions.sessions.index');
Route::match(['GET'], 'sessions/sessions/login', [SessionsController::class, 'login'])->name('sessions.sessions.login');
Route::match(['GET'], 'sessions/sessions/logout', [SessionsController::class, 'logout'])->name('sessions.sessions.logout');
Route::match(['GET'], 'sessions/sessions/passwordreset', [SessionsController::class, 'passwordreset'])->name('sessions.sessions.passwordreset');
