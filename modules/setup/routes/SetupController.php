<?php

use Illuminate\Support\Facades\Route;
use Modules\Setup\Controllers\SetupController;

// Auto-generated routes for setup::SetupController actions.

Route::match(['GET'], 'setup/setup/calculation_info', [SetupController::class, 'calculation_info'])->name('setup.setup.calculation_info');
Route::match(['GET'], 'setup/setup/complete', [SetupController::class, 'complete'])->name('setup.setup.complete');
Route::match(['GET'], 'setup/setup/configure_database', [SetupController::class, 'configure_database'])->name('setup.setup.configure_database');
Route::match(['POST'], 'setup/setup/create_user', [SetupController::class, 'create_user'])->name('setup.setup.create_user');
Route::match(['GET'], 'setup/setup/index', [SetupController::class, 'index'])->name('setup.setup.index');
Route::match(['GET'], 'setup/setup/install_tables', [SetupController::class, 'install_tables'])->name('setup.setup.install_tables');
Route::match(['GET'], 'setup/setup/language', [SetupController::class, 'language'])->name('setup.setup.language');
Route::match(['GET'], 'setup/setup/prerequisites', [SetupController::class, 'prerequisites'])->name('setup.setup.prerequisites');
Route::match(['GET'], 'setup/setup/upgrade_tables', [SetupController::class, 'upgrade_tables'])->name('setup.setup.upgrade_tables');
