<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Controllers\InvoicesController;

// Route registrations for invoices::InvoicesController.

Route::match(['GET'], 'invoices/index', [InvoicesController::class, 'index'])->name('invoices.index');
Route::match(['GET'], 'invoices/archive', [InvoicesController::class, 'archive'])->name('invoices.archive');
Route::match(['GET'], 'invoices/status/all', [InvoicesController::class, 'status'])->name('invoices.status.all');
Route::match(['GET'], 'invoices/status/draft', [InvoicesController::class, 'status'])->name('invoices.status.draft');
Route::match(['GET'], 'invoices/status/overdue', [InvoicesController::class, 'status'])->name('invoices.status.overdue');
Route::match(['GET'], 'invoices/status/paid', [InvoicesController::class, 'status'])->name('invoices.status.paid');
Route::match(['GET'], 'invoices/status/sent', [InvoicesController::class, 'status'])->name('invoices.status.sent');
Route::match(['GET'], 'invoices/status/viewed', [InvoicesController::class, 'status'])->name('invoices.status.viewed');
Route::match(['GET'], 'invoices/view/{id}', [InvoicesController::class, 'view'])->name('invoices.view.id');
Route::match(['GET'], 'invoices/delete/{id}', [InvoicesController::class, 'delete'])->name('invoices.delete.id');
Route::match(['GET'], 'invoices/generate_pdf/{id}', [InvoicesController::class, 'generate_pdf'])->name('invoices.generate_pdf.id');
