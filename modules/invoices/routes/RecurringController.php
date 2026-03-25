<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Controllers\RecurringController;

// Auto-generated routes for invoices::RecurringController actions.

Route::match(['POST'], 'invoices/recurring/delete', [RecurringController::class, 'delete'])->name('invoices.recurring.delete');
Route::match(['GET'], 'invoices/recurring/index', [RecurringController::class, 'index'])->name('invoices.recurring.index');
Route::match(['GET'], 'invoices/recurring/stop', [RecurringController::class, 'stop'])->name('invoices.recurring.stop');
