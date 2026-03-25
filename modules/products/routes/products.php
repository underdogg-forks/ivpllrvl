<?php

use Illuminate\Support\Facades\Route;
use Modules\Products\Controllers\ProductsController;

// Route registrations for products::ProductsController.

Route::prefix('products')
    ->name('products.')
    ->controller(ProductsController::class)
    ->group(function () {
        Route::get('index', 'index')->name('index');
        Route::get('form', 'form')->name('form');
        Route::get('form/{id}', 'form')->name('form.id')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete.id')->whereNumber('id');
    });
