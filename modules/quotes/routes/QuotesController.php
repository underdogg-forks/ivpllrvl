<?php

use Illuminate\Support\Facades\Route;
use Modules\Quotes\Controllers\QuotesController;

// Auto-generated routes for quotes::QuotesController actions.

Route::match(['POST'], 'quotes/quotes/delete', [QuotesController::class, 'delete'])->name('quotes.quotes.delete');
Route::match(['POST'], 'quotes/quotes/delete_quote_tax', [QuotesController::class, 'delete_quote_tax'])->name('quotes.quotes.delete_quote_tax');
Route::match(['GET'], 'quotes/quotes/generate_pdf', [QuotesController::class, 'generate_pdf'])->name('quotes.quotes.generate_pdf');
Route::match(['GET'], 'quotes/quotes/index', [QuotesController::class, 'index'])->name('quotes.quotes.index');
Route::match(['GET'], 'quotes/quotes/recalculate_all_quotes', [QuotesController::class, 'recalculate_all_quotes'])->name('quotes.quotes.recalculate_all_quotes');
Route::match(['GET'], 'quotes/quotes/status', [QuotesController::class, 'status'])->name('quotes.quotes.status');
Route::match(['GET'], 'quotes/quotes/view', [QuotesController::class, 'view'])->name('quotes.quotes.view');
