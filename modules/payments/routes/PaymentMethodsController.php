<?php

use Illuminate\Support\Facades\Route;
use Modules\PaymentMethods\Controllers\PaymentMethodsController;

// Route registrations for payment_methods::PaymentMethodsController.

Route::match(['GET'], 'payment_methods/index', [PaymentMethodsController::class, 'index'])->name('payment_methods.index');
Route::match(['GET'], 'payment_methods/form', [PaymentMethodsController::class, 'form'])->name('payment_methods.form');
Route::match(['GET'], 'payment_methods/form/{id}', [PaymentMethodsController::class, 'form'])->name('payment_methods.form.id');
Route::match(['GET'], 'payment_methods/delete/{id}', [PaymentMethodsController::class, 'delete'])->name('payment_methods.delete.id');
