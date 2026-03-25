<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Controllers\SettingsController;

// Route registrations for settings::SettingsController.

Route::get( 'settings', [SettingsController::class, 'index'])->name('settings');
