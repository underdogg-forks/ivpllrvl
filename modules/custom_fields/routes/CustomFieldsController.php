<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomFields\Controllers\CustomFieldsController;

// Route registrations for custom_fields::CustomFieldsController.

Route::match(['GET'], 'custom_fields', [CustomFieldsController::class, 'index'])->name('custom_fields');
Route::match(['GET'], 'custom_fields/index', [CustomFieldsController::class, 'index'])->name('custom_fields.index');
Route::match(['GET'], 'custom_fields/form', [CustomFieldsController::class, 'form'])->name('custom_fields.form');
Route::match(['GET'], 'custom_fields/form/{id}', [CustomFieldsController::class, 'form'])->name('custom_fields.form.id');
Route::match(['GET'], 'custom_fields/delete/{id}', [CustomFieldsController::class, 'delete'])->name('custom_fields.delete.id');
Route::match(['GET'], 'custom_fields/table/all', [CustomFieldsController::class, 'table'])->name('custom_fields.table.all');
Route::match(['GET'], 'custom_fields/table/client', [CustomFieldsController::class, 'table'])->name('custom_fields.table.client');
Route::match(['GET'], 'custom_fields/table/invoice', [CustomFieldsController::class, 'table'])->name('custom_fields.table.invoice');
Route::match(['GET'], 'custom_fields/table/payment', [CustomFieldsController::class, 'table'])->name('custom_fields.table.payment');
Route::match(['GET'], 'custom_fields/table/quote', [CustomFieldsController::class, 'table'])->name('custom_fields.table.quote');
Route::match(['GET'], 'custom_fields/table/user', [CustomFieldsController::class, 'table'])->name('custom_fields.table.user');
