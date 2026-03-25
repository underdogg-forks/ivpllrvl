<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Controllers\PaymentInformationController;

// Auto-generated routes for guest::PaymentInformationController actions.

Route::get( 'guest/paymentinformation/form', [PaymentInformationController::class, 'form'])->name('guest.paymentinformation.form');
Route::get( 'guest/paymentinformation/paypal', [PaymentInformationController::class, 'paypal'])->name('guest.paymentinformation.paypal');
Route::get( 'guest/paymentinformation/stripe', [PaymentInformationController::class, 'stripe'])->name('guest.paymentinformation.stripe');
