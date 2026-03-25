<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Controllers\InvoicesController;

// Auto-generated routes for guest::InvoicesController actions.

Route::match(['GET'], 'guest/invoices/generate_pdf', [InvoicesController::class, 'generate_pdf'])->name('guest.invoices.generate_pdf');
Route::match(['GET'], 'guest/invoices/generate_sumex_pdf', [InvoicesController::class, 'generate_sumex_pdf'])->name('guest.invoices.generate_sumex_pdf');
Route::match(['GET'], 'guest/invoices/index', [InvoicesController::class, 'index'])->name('guest.invoices.index');
Route::match(['GET'], 'guest/invoices/status', [InvoicesController::class, 'status'])->name('guest.invoices.status');
Route::match(['GET'], 'guest/invoices/view', [InvoicesController::class, 'view'])->name('guest.invoices.view');
