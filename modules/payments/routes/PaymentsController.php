<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Controllers\PaymentsController;

// Auto-generated routes for payments::PaymentsController actions.

Route::match(['POST'], 'payments/payments/delete', [PaymentsController::class, 'delete'])->name('payments.payments.delete');
Route::match(['GET'], 'payments/payments/form', [PaymentsController::class, 'form'])->name('payments.payments.form');
Route::match(['GET'], 'payments/payments/index', [PaymentsController::class, 'index'])->name('payments.payments.index');
Route::match(['GET'], 'payments/payments/online_logs', [PaymentsController::class, 'online_logs'])->name('payments.payments.online_logs');
