<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Controllers\RecurringController;

// Route registrations for invoices::RecurringController.

Route::get( 'invoices/recurring', [RecurringController::class, 'index'])->name('invoices.recurring');
Route::get( 'invoices/recurring/index', [RecurringController::class, 'index'])->name('invoices.recurring.index');
Route::post('invoices/recurring/stop/{id}', [RecurringController::class, 'stop'])->name('invoices.recurring.stop.id');
