<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Controllers\PaymentsController;

// Auto-generated routes for guest::PaymentsController actions.

Route::match(['GET'], 'guest/payments/index', [PaymentsController::class, 'index'])->name('guest.payments.index');
