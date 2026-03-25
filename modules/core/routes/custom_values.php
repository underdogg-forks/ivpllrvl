<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomValues\Controllers\CustomValuesController;

// Route registrations for custom_values::CustomValuesController.

Route::get('custom_values', [CustomValuesController::class, 'index'])->name('custom_values');

Route::prefix('custom_values')
    ->name('custom_values.')
    ->controller(CustomValuesController::class)
    ->group(function () {
        Route::get('create', 'create')->name('create');
        Route::get('clone/{id}', 'create')->name('clone')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete.id')->whereNumber('id');
        Route::get('field', 'field')->name('field');
        Route::get('field/{id}', 'field')->name('field.id')->whereNumber('id');
    });
