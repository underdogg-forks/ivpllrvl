<?php

use Illuminate\Support\Facades\Route;
use Modules\PaymentMethods\Controllers\PaymentMethodsController;

// Auto-generated routes for payment_methods::PaymentMethodsController actions.

Route::match(['POST'], 'payment_methods/paymentmethods/delete', [PaymentMethodsController::class, 'delete'])->name('payment_methods.paymentmethods.delete');
Route::match(['GET'], 'payment_methods/paymentmethods/form', [PaymentMethodsController::class, 'form'])->name('payment_methods.paymentmethods.form');
Route::match(['GET'], 'payment_methods/paymentmethods/index', [PaymentMethodsController::class, 'index'])->name('payment_methods.paymentmethods.index');
