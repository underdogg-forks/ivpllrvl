<?php

use Illuminate\Support\Facades\Route;
use Modules\Layout\Controllers\LayoutController;

// Route registrations for layout::LayoutController.

Route::get( 'layout/header', [LayoutController::class, 'load_view'])->name('layout.header');
Route::get( 'layout/footer', [LayoutController::class, 'load_view'])->name('layout.footer');
Route::get( 'layout/sidebar', [LayoutController::class, 'load_view'])->name('layout.sidebar');
