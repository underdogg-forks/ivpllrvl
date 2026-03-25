<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomFields\Controllers\CustomFieldsController;

// Route registrations for custom_fields::CustomFieldsController.

Route::get( 'custom_fields', [CustomFieldsController::class, 'index'])->name('custom_fields');
Route::get( 'custom_fields/index', [CustomFieldsController::class, 'index'])->name('custom_fields.index');
Route::get( 'custom_fields/form', [CustomFieldsController::class, 'form'])->name('custom_fields.form');
Route::get( 'custom_fields/form/{id}', [CustomFieldsController::class, 'form'])->name('custom_fields.form.id');
Route::post( 'custom_fields/delete/{id}', [CustomFieldsController::class, 'delete'])->name('custom_fields.delete.id');
Route::get( 'custom_fields/table/all', [CustomFieldsController::class, 'table'])->name('custom_fields.table.all');
Route::get( 'custom_fields/table/client', [CustomFieldsController::class, 'table'])->name('custom_fields.table.client');
Route::get( 'custom_fields/table/invoice', [CustomFieldsController::class, 'table'])->name('custom_fields.table.invoice');
Route::get( 'custom_fields/table/payment', [CustomFieldsController::class, 'table'])->name('custom_fields.table.payment');
Route::get( 'custom_fields/table/quote', [CustomFieldsController::class, 'table'])->name('custom_fields.table.quote');
Route::get( 'custom_fields/table/user', [CustomFieldsController::class, 'table'])->name('custom_fields.table.user');
