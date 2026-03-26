<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Controllers\DashboardController;

// Route registrations for dashboard::DashboardController.

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
