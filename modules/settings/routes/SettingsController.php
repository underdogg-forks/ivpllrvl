<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Controllers\SettingsController;

// Auto-generated routes for settings::SettingsController actions.

Route::match(['GET'], 'settings/settings/index', [SettingsController::class, 'index'])->name('settings.settings.index');
Route::match(['POST'], 'settings/settings/remove_logo', [SettingsController::class, 'remove_logo'])->name('settings.settings.remove_logo');
