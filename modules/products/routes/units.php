<?php

use Illuminate\Support\Facades\Route;
use Modules\Products\Controllers\UnitsController;

// Route registrations for units::UnitsController.

Route::prefix('units')
    ->name('units.')
    ->controller(UnitsController::class)
    ->group(function () {
        Route::get('index', 'index')->name('index');
        Route::get('form', 'form')->name('form');
        Route::get('form/{id}', 'form')->name('form.id')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete.id')->whereNumber('id');
    });
