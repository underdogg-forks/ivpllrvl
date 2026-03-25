<?php

use Illuminate\Support\Facades\Route;
use Modules\Clients\Controllers\PaymentInformationController;

// Route registrations for clients::PaymentInformationController.

Route::get('guest/paymentinformation/form', [PaymentInformationController::class, 'form'])->name('guest.paymentinformation.form');
Route::get('guest/paymentinformation/paypal', [PaymentInformationController::class, 'paypal'])->name('guest.paymentinformation.paypal');
Route::get('guest/paymentinformation/stripe', [PaymentInformationController::class, 'stripe'])->name('guest.paymentinformation.stripe');
