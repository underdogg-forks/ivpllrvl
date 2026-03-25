<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Controllers\PaymentsAjaxController;

// Route registrations for payments::PaymentsAjaxController.

Route::prefix('payments/paymentsajax')
    ->name('payments.paymentsajax.')
    ->controller(PaymentsAjaxController::class)
    ->group(function () {
        Route::post('add', 'add')->name('add');
        Route::post('modal_add_payment', 'modal_add_payment')->name('modal_add_payment');
    });
