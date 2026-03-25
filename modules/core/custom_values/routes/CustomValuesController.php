<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomValues\Controllers\CustomValuesController;

// Route registrations for custom_values::CustomValuesController.

Route::match(['GET'], 'custom_values', [CustomValuesController::class, 'index'])->name('custom_values');
Route::match(['GET'], 'custom_values/create', [CustomValuesController::class, 'create'])->name('custom_values.create');
Route::match(['GET'], 'custom_values/create/{id}', [CustomValuesController::class, 'create'])->name('custom_values.create.id');
Route::match(['GET'], 'custom_values/delete/{id}', [CustomValuesController::class, 'delete'])->name('custom_values.delete.id');
Route::match(['GET'], 'custom_values/field', [CustomValuesController::class, 'field'])->name('custom_values.field');
Route::match(['GET'], 'custom_values/field/{id}', [CustomValuesController::class, 'field'])->name('custom_values.field.id');
