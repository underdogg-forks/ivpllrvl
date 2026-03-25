<?php

use Illuminate\Support\Facades\Route;
use Modules\Import\Controllers\ImportController;

// Auto-generated routes for import::ImportController actions.

Route::match(['POST'], 'import/import/delete', [ImportController::class, 'delete'])->name('import.import.delete');
Route::match(['GET'], 'import/import/form', [ImportController::class, 'form'])->name('import.import.form');
Route::match(['GET'], 'import/import/index', [ImportController::class, 'index'])->name('import.import.index');
