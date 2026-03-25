<?php

use Illuminate\Support\Facades\Route;
use Modules\Sessions\Controllers\SessionsController;

// Route registrations for sessions::SessionsController.

Route::prefix('sessions')
    ->name('sessions.')
    ->controller(SessionsController::class)
    ->group(function () {
        Route::get('index', 'index')->name('index');
        Route::get('login', 'login')->name('login');
        Route::post('logout', 'logout')->name('logout');
    });
