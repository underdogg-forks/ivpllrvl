<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Controllers\RecurringController;

// Route registrations for invoices::RecurringController.

Route::get('invoices/recurring', [RecurringController::class, 'index'])->name('invoices.recurring');

Route::prefix('invoices/recurring')
    ->name('invoices.recurring.')
    ->controller(RecurringController::class)
    ->group(function () {
        Route::get('index', 'index')->name('index');
        Route::post('stop/{id}', 'stop')->name('stop.id')->whereNumber('id');
    });
