<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Controllers\SetupController;

// Route registrations for setup::SetupController.

Route::prefix('setup/setup')
    ->name('setup.setup.')
    ->controller(SetupController::class)
    ->group(function () {
        Route::get('calculation_info', 'calculation_info')->name('calculation_info');
        Route::get('complete', 'complete')->name('complete');
        Route::get('configure_database', 'configure_database')->name('configure_database');
        Route::post('create_user', 'create_user')->name('create_user');
        Route::get('index', 'index')->name('index');
        Route::post('install_tables', 'install_tables')->name('install_tables');
        Route::get('language', 'language')->name('language');
        Route::get('prerequisites', 'prerequisites')->name('prerequisites');
        Route::post('upgrade_tables', 'upgrade_tables')->name('upgrade_tables');
    });
