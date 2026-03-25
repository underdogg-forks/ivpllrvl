<?php

use Illuminate\Support\Facades\Route;
use Modules\Layout\Controllers\LayoutController;

// Route registrations for layout::LayoutController.

Route::prefix('layout')
    ->name('layout.')
    ->controller(LayoutController::class)
    ->group(function () {
        Route::get('header', 'load_view')->name('header');
        Route::get('footer', 'load_view')->name('footer');
        Route::get('sidebar', 'load_view')->name('sidebar');
    });
