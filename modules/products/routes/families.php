<?php

use Illuminate\Support\Facades\Route;
use Modules\Products\Controllers\FamiliesController;

// Route registrations for families::FamiliesController.

Route::prefix('families')
    ->name('families.')
    ->controller(FamiliesController::class)
    ->group(function () {
        Route::get('index', 'index')->name('index');
        Route::get('form', 'form')->name('form');
        Route::get('form/{id}', 'form')->name('form.id')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete.id')->whereNumber('id');
    });
