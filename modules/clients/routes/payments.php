<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Controllers\PaymentsController;

// Auto-generated routes for guest::PaymentsController actions.

Route::get( 'guest/payments/index', [PaymentsController::class, 'index'])->name('guest.payments.index');
