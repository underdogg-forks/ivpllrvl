<?php

use Illuminate\Support\Facades\Route;
use Modules\Reports\Controllers\ReportsController;

// Route registrations for reports::ReportsController.

Route::match(['GET'], 'reports/invoice_aging', [ReportsController::class, 'invoice_aging'])->name('reports.invoice_aging');
Route::match(['GET'], 'reports/invoices_per_client', [ReportsController::class, 'invoices_per_client'])->name('reports.invoices_per_client');
Route::match(['GET'], 'reports/payment_history', [ReportsController::class, 'payment_history'])->name('reports.payment_history');
Route::match(['GET'], 'reports/sales_by_client', [ReportsController::class, 'sales_by_client'])->name('reports.sales_by_client');
Route::match(['GET'], 'reports/sales_by_year', [ReportsController::class, 'sales_by_year'])->name('reports.sales_by_year');
