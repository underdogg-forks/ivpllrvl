<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Controllers\SettingsController;

// Route registrations for settings::SettingsController.

Route::get('settings', [SettingsController::class, 'index'])->name('settings');
