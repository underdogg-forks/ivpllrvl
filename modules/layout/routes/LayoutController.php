<?php

use Illuminate\Support\Facades\Route;
use Modules\Layout\Controllers\LayoutController;

// Route registrations for layout::LayoutController.

Route::match(['GET'], 'layout/header', [LayoutController::class, 'load_view'])->name('layout.header');
Route::match(['GET'], 'layout/footer', [LayoutController::class, 'load_view'])->name('layout.footer');
Route::match(['GET'], 'layout/sidebar', [LayoutController::class, 'load_view'])->name('layout.sidebar');
