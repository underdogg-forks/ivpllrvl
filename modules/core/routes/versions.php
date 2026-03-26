<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Controllers\VersionsController;

// Route registrations for settings::VersionsController actions.

Route::get('settings/versions/index', [VersionsController::class, 'index'])->name('settings.versions.index');
