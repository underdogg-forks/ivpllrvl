<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Controllers\PaymentsController;

// Route registrations for payments::PaymentsController.

Route::prefix('payments')
    ->name('payments.')
    ->controller(PaymentsController::class)
    ->group(function () {
        Route::get('index', 'index')->name('index');
        Route::get('form', 'form')->name('form');
        Route::get('form/{id}', 'form')->name('form.id')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete.id')->whereNumber('id');
        Route::get('online_logs', 'online_logs')->name('online_logs');
    });
