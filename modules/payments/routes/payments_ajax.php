<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Controllers\PaymentsAjaxController;

// Auto-generated routes for payments::PaymentsAjaxController actions.

Route::post( 'payments/paymentsajax/add', [PaymentsAjaxController::class, 'add'])->name('payments.paymentsajax.add');
Route::post( 'payments/paymentsajax/modal_add_payment', [PaymentsAjaxController::class, 'modal_add_payment'])->name('payments.paymentsajax.modal_add_payment');
