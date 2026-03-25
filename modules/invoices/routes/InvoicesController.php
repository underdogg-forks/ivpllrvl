<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Controllers\InvoicesController;

// Auto-generated routes for invoices::InvoicesController actions.

Route::match(['GET'], 'invoices/invoices/archive', [InvoicesController::class, 'archive'])->name('invoices.invoices.archive');
Route::match(['POST'], 'invoices/invoices/delete', [InvoicesController::class, 'delete'])->name('invoices.invoices.delete');
Route::match(['POST'], 'invoices/invoices/delete_invoice_tax', [InvoicesController::class, 'delete_invoice_tax'])->name('invoices.invoices.delete_invoice_tax');
Route::match(['GET'], 'invoices/invoices/download', [InvoicesController::class, 'download'])->name('invoices.invoices.download');
Route::match(['GET'], 'invoices/invoices/generate_pdf', [InvoicesController::class, 'generate_pdf'])->name('invoices.invoices.generate_pdf');
Route::match(['GET'], 'invoices/invoices/generate_sumex_copy', [InvoicesController::class, 'generate_sumex_copy'])->name('invoices.invoices.generate_sumex_copy');
Route::match(['GET'], 'invoices/invoices/generate_sumex_pdf', [InvoicesController::class, 'generate_sumex_pdf'])->name('invoices.invoices.generate_sumex_pdf');
Route::match(['GET'], 'invoices/invoices/generate_xml', [InvoicesController::class, 'generate_xml'])->name('invoices.invoices.generate_xml');
Route::match(['GET'], 'invoices/invoices/index', [InvoicesController::class, 'index'])->name('invoices.invoices.index');
Route::match(['GET'], 'invoices/invoices/recalculate_all_invoices', [InvoicesController::class, 'recalculate_all_invoices'])->name('invoices.invoices.recalculate_all_invoices');
Route::match(['GET'], 'invoices/invoices/status', [InvoicesController::class, 'status'])->name('invoices.invoices.status');
Route::match(['GET'], 'invoices/invoices/view', [InvoicesController::class, 'view'])->name('invoices.invoices.view');
