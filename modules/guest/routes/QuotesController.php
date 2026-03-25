<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Controllers\QuotesController;

// Auto-generated routes for guest::QuotesController actions.

Route::match(['GET'], 'guest/quotes/approve', [QuotesController::class, 'approve'])->name('guest.quotes.approve');
Route::match(['GET'], 'guest/quotes/generate_pdf', [QuotesController::class, 'generate_pdf'])->name('guest.quotes.generate_pdf');
Route::match(['GET'], 'guest/quotes/index', [QuotesController::class, 'index'])->name('guest.quotes.index');
Route::match(['GET'], 'guest/quotes/reject', [QuotesController::class, 'reject'])->name('guest.quotes.reject');
Route::match(['GET'], 'guest/quotes/status', [QuotesController::class, 'status'])->name('guest.quotes.status');
Route::match(['GET'], 'guest/quotes/view', [QuotesController::class, 'view'])->name('guest.quotes.view');
