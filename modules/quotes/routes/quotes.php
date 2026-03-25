<?php

use Illuminate\Support\Facades\Route;
use Modules\Quotes\Controllers\QuotesController;

// Route registrations for quotes::QuotesController.

Route::get( 'quotes/index', [QuotesController::class, 'index'])->name('quotes.index');
Route::get( 'quotes/status/all', [QuotesController::class, 'status'])->name('quotes.status.all');
Route::get( 'quotes/status/approved', [QuotesController::class, 'status'])->name('quotes.status.approved');
Route::get( 'quotes/status/canceled', [QuotesController::class, 'status'])->name('quotes.status.canceled');
Route::get( 'quotes/status/draft', [QuotesController::class, 'status'])->name('quotes.status.draft');
Route::get( 'quotes/status/rejected', [QuotesController::class, 'status'])->name('quotes.status.rejected');
Route::get( 'quotes/status/sent', [QuotesController::class, 'status'])->name('quotes.status.sent');
Route::get( 'quotes/status/viewed', [QuotesController::class, 'status'])->name('quotes.status.viewed');
Route::get( 'quotes/view/{id}', [QuotesController::class, 'view'])->name('quotes.view.id');
Route::post( 'quotes/delete/{id}', [QuotesController::class, 'delete'])->name('quotes.delete.id');
Route::get( 'quotes/cancel/{id}', [QuotesController::class, 'delete'])->name('quotes.cancel.id');
Route::get( 'quotes/generate_pdf/{id}', [QuotesController::class, 'generate_pdf'])->name('quotes.generate_pdf.id');
