<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Controllers\PaymentInformationController;

// Auto-generated routes for guest::PaymentInformationController actions.

Route::match(['GET'], 'guest/paymentinformation/form', [PaymentInformationController::class, 'form'])->name('guest.paymentinformation.form');
Route::match(['GET'], 'guest/paymentinformation/paypal', [PaymentInformationController::class, 'paypal'])->name('guest.paymentinformation.paypal');
Route::match(['GET'], 'guest/paymentinformation/stripe', [PaymentInformationController::class, 'stripe'])->name('guest.paymentinformation.stripe');
