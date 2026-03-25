<?php

use Illuminate\Support\Facades\Route;
use Modules\Quotes\Controllers\QuotesController;

// Route registrations for quotes::QuotesController.

Route::match(['GET'], 'quotes/index', [QuotesController::class, 'index'])->name('quotes.index');
Route::match(['GET'], 'quotes/status/all', [QuotesController::class, 'status'])->name('quotes.status.all');
Route::match(['GET'], 'quotes/status/approved', [QuotesController::class, 'status'])->name('quotes.status.approved');
Route::match(['GET'], 'quotes/status/canceled', [QuotesController::class, 'status'])->name('quotes.status.canceled');
Route::match(['GET'], 'quotes/status/draft', [QuotesController::class, 'status'])->name('quotes.status.draft');
Route::match(['GET'], 'quotes/status/rejected', [QuotesController::class, 'status'])->name('quotes.status.rejected');
Route::match(['GET'], 'quotes/status/sent', [QuotesController::class, 'status'])->name('quotes.status.sent');
Route::match(['GET'], 'quotes/status/viewed', [QuotesController::class, 'status'])->name('quotes.status.viewed');
Route::match(['GET'], 'quotes/view/{id}', [QuotesController::class, 'view'])->name('quotes.view.id');
Route::match(['GET'], 'quotes/delete/{id}', [QuotesController::class, 'delete'])->name('quotes.delete.id');
Route::match(['GET'], 'quotes/cancel/{id}', [QuotesController::class, 'delete'])->name('quotes.cancel.id');
Route::match(['GET'], 'quotes/generate_pdf/{id}', [QuotesController::class, 'generate_pdf'])->name('quotes.generate_pdf.id');
