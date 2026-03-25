<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomFields\Controllers\CustomFieldsController;

// Auto-generated routes for custom_fields::CustomFieldsController actions.

Route::match(['POST'], 'custom_fields/customfields/delete', [CustomFieldsController::class, 'delete'])->name('custom_fields.customfields.delete');
Route::match(['GET'], 'custom_fields/customfields/form', [CustomFieldsController::class, 'form'])->name('custom_fields.customfields.form');
Route::match(['GET'], 'custom_fields/customfields/index', [CustomFieldsController::class, 'index'])->name('custom_fields.customfields.index');
Route::match(['GET'], 'custom_fields/customfields/table', [CustomFieldsController::class, 'table'])->name('custom_fields.customfields.table');
