<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Controllers\InvoicesController;

// Route registrations for invoices::InvoicesController.

Route::get( 'invoices/index', [InvoicesController::class, 'index'])->name('invoices.index');
Route::get( 'invoices/archive', [InvoicesController::class, 'archive'])->name('invoices.archive');
Route::get( 'invoices/status/all', [InvoicesController::class, 'status'])->name('invoices.status.all');
Route::get( 'invoices/status/draft', [InvoicesController::class, 'status'])->name('invoices.status.draft');
Route::get( 'invoices/status/overdue', [InvoicesController::class, 'status'])->name('invoices.status.overdue');
Route::get( 'invoices/status/paid', [InvoicesController::class, 'status'])->name('invoices.status.paid');
Route::get( 'invoices/status/sent', [InvoicesController::class, 'status'])->name('invoices.status.sent');
Route::get( 'invoices/status/viewed', [InvoicesController::class, 'status'])->name('invoices.status.viewed');
Route::get( 'invoices/view/{id}', [InvoicesController::class, 'view'])->name('invoices.view.id');
Route::post( 'invoices/delete/{id}', [InvoicesController::class, 'delete'])->name('invoices.delete.id');
Route::get( 'invoices/generate_pdf/{id}', [InvoicesController::class, 'generate_pdf'])->name('invoices.generate_pdf.id');
