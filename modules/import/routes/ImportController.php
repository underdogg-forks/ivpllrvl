<?php

use Illuminate\Support\Facades\Route;
use Modules\Import\Controllers\ImportController;

// Route registrations for import::ImportController.

Route::match(['GET'], 'import', [ImportController::class, 'index'])->name('import');
Route::match(['GET'], 'import/form', [ImportController::class, 'form'])->name('import.form');
