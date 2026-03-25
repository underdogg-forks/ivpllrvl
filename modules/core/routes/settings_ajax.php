<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Controllers\SettingsAjaxController;

// Auto-generated routes for settings::SettingsAjaxController actions.

Route::post( 'settings/settingsajax/get_cron_key', [SettingsAjaxController::class, 'get_cron_key'])->name('settings.settingsajax.get_cron_key');
