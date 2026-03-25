<?php

use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Controllers\DashboardController;

// Auto-generated routes for dashboard::DashboardController actions.

Route::match(['GET'], 'dashboard/dashboard/index', [DashboardController::class, 'index'])->name('dashboard.dashboard.index');
