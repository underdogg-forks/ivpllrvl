<?php

use Illuminate\Support\Facades\Route;
use Modules\PaymentMethods\Controllers\PaymentMethodsController;

// Route registrations for payment_methods::PaymentMethodsController.

Route::get( 'payment_methods/index', [PaymentMethodsController::class, 'index'])->name('payment_methods.index');
Route::get( 'payment_methods/form', [PaymentMethodsController::class, 'form'])->name('payment_methods.form');
Route::get( 'payment_methods/form/{id}', [PaymentMethodsController::class, 'form'])->name('payment_methods.form.id');
Route::post( 'payment_methods/delete/{id}', [PaymentMethodsController::class, 'delete'])->name('payment_methods.delete.id');
