<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Controllers\CustomFieldsController;

// Route registrations for custom_fields::CustomFieldsController.

Route::get('custom_fields', [CustomFieldsController::class, 'index'])->name('custom_fields');

Route::prefix('custom_fields')
    ->name('custom_fields.')
    ->controller(CustomFieldsController::class)
    ->group(function () {
        Route::get('index', 'index')->name('index');
        Route::get('form', 'form')->name('form');
        Route::get('form/{id}', 'form')->name('form.id')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete.id')->whereNumber('id');

        Route::prefix('table')->name('table.')->group(function () {
            Route::get('all', 'table')->name('all');
            Route::get('client', 'table')->name('client');
            Route::get('invoice', 'table')->name('invoice');
            Route::get('payment', 'table')->name('payment');
            Route::get('quote', 'table')->name('quote');
            Route::get('user', 'table')->name('user');
        });
    });
