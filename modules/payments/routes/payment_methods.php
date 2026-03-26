<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Controllers\PaymentMethodsController;

// Route registrations for payment_methods::PaymentMethodsController.

Route::prefix('payment_methods')
    ->name('payment_methods.')
    ->controller(PaymentMethodsController::class)
    ->group(function () {
        Route::get('index', 'index')->name('index');
        Route::get('form', 'form')->name('form');
        Route::get('form/{id}', 'form')->name('form.id')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete.id')->whereNumber('id');
    });
