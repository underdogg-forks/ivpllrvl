<?php

use Illuminate\Support\Facades\Route;
use Modules\Clients\Controllers\GetController;

// Route registrations for guest::GetController.

Route::prefix('guest/get')
    ->name('guest.get.')
    ->controller(GetController::class)
    ->group(function () {
        Route::get('attachment', 'attachment')->name('attachment');
        Route::get('get_file', 'get_file')->name('get_file');
        Route::get('show_files', 'show_files')->name('show_files');
    });
