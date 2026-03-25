<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Controllers\PaymentsController;

// Route registrations for payments::PaymentsController.

Route::get( 'payments/index', [PaymentsController::class, 'index'])->name('payments.index');
Route::get( 'payments/form', [PaymentsController::class, 'form'])->name('payments.form');
Route::get( 'payments/form/{id}', [PaymentsController::class, 'form'])->name('payments.form.id');
Route::post( 'payments/delete/{id}', [PaymentsController::class, 'delete'])->name('payments.delete.id');
Route::get( 'payments/online_logs', [PaymentsController::class, 'online_logs'])->name('payments.online_logs');
