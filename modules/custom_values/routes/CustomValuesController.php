<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomValues\Controllers\CustomValuesController;

// Auto-generated routes for custom_values::CustomValuesController actions.

Route::match(['POST'], 'custom_values/customvalues/create', [CustomValuesController::class, 'create'])->name('custom_values.customvalues.create');
Route::match(['POST'], 'custom_values/customvalues/delete', [CustomValuesController::class, 'delete'])->name('custom_values.customvalues.delete');
Route::match(['GET'], 'custom_values/customvalues/edit', [CustomValuesController::class, 'edit'])->name('custom_values.customvalues.edit');
Route::match(['GET'], 'custom_values/customvalues/field', [CustomValuesController::class, 'field'])->name('custom_values.customvalues.field');
Route::match(['GET'], 'custom_values/customvalues/index', [CustomValuesController::class, 'index'])->name('custom_values.customvalues.index');
