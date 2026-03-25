<?php

use Illuminate\Support\Facades\Route;
use Modules\Reports\Controllers\ReportsController;

// Route registrations for reports::ReportsController.

Route::get( 'reports/invoice_aging', [ReportsController::class, 'invoice_aging'])->name('reports.invoice_aging');
Route::get( 'reports/invoices_per_client', [ReportsController::class, 'invoices_per_client'])->name('reports.invoices_per_client');
Route::get( 'reports/payment_history', [ReportsController::class, 'payment_history'])->name('reports.payment_history');
Route::get( 'reports/sales_by_client', [ReportsController::class, 'sales_by_client'])->name('reports.sales_by_client');
Route::get( 'reports/sales_by_year', [ReportsController::class, 'sales_by_year'])->name('reports.sales_by_year');
