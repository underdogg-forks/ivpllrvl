<?php

use Illuminate\Support\Facades\Route;
use Modules\Setup\Controllers\SetupController;

// Auto-generated routes for setup::SetupController actions.

Route::get( 'setup/setup/calculation_info', [SetupController::class, 'calculation_info'])->name('setup.setup.calculation_info');
Route::get( 'setup/setup/complete', [SetupController::class, 'complete'])->name('setup.setup.complete');
Route::get( 'setup/setup/configure_database', [SetupController::class, 'configure_database'])->name('setup.setup.configure_database');
Route::post( 'setup/setup/create_user', [SetupController::class, 'create_user'])->name('setup.setup.create_user');
Route::get( 'setup/setup/index', [SetupController::class, 'index'])->name('setup.setup.index');
Route::get( 'setup/setup/install_tables', [SetupController::class, 'install_tables'])->name('setup.setup.install_tables');
Route::get( 'setup/setup/language', [SetupController::class, 'language'])->name('setup.setup.language');
Route::get( 'setup/setup/prerequisites', [SetupController::class, 'prerequisites'])->name('setup.setup.prerequisites');
Route::get( 'setup/setup/upgrade_tables', [SetupController::class, 'upgrade_tables'])->name('setup.setup.upgrade_tables');
