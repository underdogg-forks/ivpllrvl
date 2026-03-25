<?php

use Illuminate\Support\Facades\Route;
use Modules\Reports\Controllers\ReportsController;

// Route registrations for reports::ReportsController.

Route::prefix('reports')
    ->name('reports.')
    ->controller(ReportsController::class)
    ->group(function () {
        Route::get('invoice_aging', 'invoice_aging')->name('invoice_aging');
        Route::get('invoices_per_client', 'invoices_per_client')->name('invoices_per_client');
        Route::get('payment_history', 'payment_history')->name('payment_history');
        Route::get('sales_by_client', 'sales_by_client')->name('sales_by_client');
        Route::get('sales_by_year', 'sales_by_year')->name('sales_by_year');
    });
