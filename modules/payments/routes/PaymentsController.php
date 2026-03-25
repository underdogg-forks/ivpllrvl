<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Controllers\PaymentsController;

// Route registrations for payments::PaymentsController.

Route::match(['GET'], 'payments/index', [PaymentsController::class, 'index'])->name('payments.index');
Route::match(['GET'], 'payments/form', [PaymentsController::class, 'form'])->name('payments.form');
Route::match(['GET'], 'payments/form/{id}', [PaymentsController::class, 'form'])->name('payments.form.id');
Route::match(['GET'], 'payments/delete/{id}', [PaymentsController::class, 'delete'])->name('payments.delete.id');
Route::match(['GET'], 'payments/online_logs', [PaymentsController::class, 'online_logs'])->name('payments.online_logs');
