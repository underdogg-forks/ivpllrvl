<?php

use Illuminate\Support\Facades\Route;
use Modules\Clients\Controllers\PaymentsController;

// Route registrations for guest::PaymentsController actions.

Route::get('guest/payments/index', [PaymentsController::class, 'index'])->name('guest.payments.index');
