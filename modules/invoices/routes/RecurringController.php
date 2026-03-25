<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Controllers\RecurringController;

// Route registrations for invoices::RecurringController.

Route::match(['GET'], 'invoices/recurring', [RecurringController::class, 'index'])->name('invoices.recurring');
Route::match(['GET'], 'invoices/recurring/index', [RecurringController::class, 'index'])->name('invoices.recurring.index');
Route::match(['GET'], 'invoices/recurring/stop/{id}', [RecurringController::class, 'stop'])->name('invoices.recurring.stop.id');
