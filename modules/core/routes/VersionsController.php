<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Controllers\VersionsController;

// Auto-generated routes for settings::VersionsController actions.

Route::match(['GET'], 'settings/versions/index', [VersionsController::class, 'index'])->name('settings.versions.index');
