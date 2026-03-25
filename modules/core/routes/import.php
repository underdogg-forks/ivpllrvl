<?php

use Illuminate\Support\Facades\Route;
use Modules\Import\Controllers\ImportController;

// Route registrations for import::ImportController.

Route::get('import', [ImportController::class, 'index'])->name('import');
Route::get('import/form', [ImportController::class, 'form'])->name('import.form');
