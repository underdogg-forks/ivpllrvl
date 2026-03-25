<?php

use Illuminate\Support\Facades\Route;
use Modules\Reports\Controllers\ReportsController;

// Auto-generated routes for reports::ReportsController actions.

Route::match(['GET'], 'reports/reports/invoice_aging', [ReportsController::class, 'invoice_aging'])->name('reports.reports.invoice_aging');
Route::match(['GET'], 'reports/reports/invoices_per_client', [ReportsController::class, 'invoices_per_client'])->name('reports.reports.invoices_per_client');
Route::match(['GET'], 'reports/reports/payment_history', [ReportsController::class, 'payment_history'])->name('reports.reports.payment_history');
Route::match(['GET'], 'reports/reports/sales_by_client', [ReportsController::class, 'sales_by_client'])->name('reports.reports.sales_by_client');
Route::match(['GET'], 'reports/reports/sales_by_year', [ReportsController::class, 'sales_by_year'])->name('reports.reports.sales_by_year');
