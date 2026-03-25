<?php

use Illuminate\Support\Facades\Route;
use Modules\Quotes\Controllers\QuotesAjaxController;

// Auto-generated routes for quotes::QuotesAjaxController actions.

Route::post( 'quotes/quotesajax/change_client', [QuotesAjaxController::class, 'change_client'])->name('quotes.quotesajax.change_client');
Route::post( 'quotes/quotesajax/change_user', [QuotesAjaxController::class, 'change_user'])->name('quotes.quotesajax.change_user');
Route::post( 'quotes/quotesajax/copy_quote', [QuotesAjaxController::class, 'copy_quote'])->name('quotes.quotesajax.copy_quote');
Route::post( 'quotes/quotesajax/create', [QuotesAjaxController::class, 'create'])->name('quotes.quotesajax.create');
Route::post( 'quotes/quotesajax/delete_item', [QuotesAjaxController::class, 'delete_item'])->name('quotes.quotesajax.delete_item');
Route::post( 'quotes/quotesajax/get_item', [QuotesAjaxController::class, 'get_item'])->name('quotes.quotesajax.get_item');
Route::post( 'quotes/quotesajax/modal_change_client', [QuotesAjaxController::class, 'modal_change_client'])->name('quotes.quotesajax.modal_change_client');
Route::post( 'quotes/quotesajax/modal_change_user', [QuotesAjaxController::class, 'modal_change_user'])->name('quotes.quotesajax.modal_change_user');
Route::post( 'quotes/quotesajax/modal_copy_quote', [QuotesAjaxController::class, 'modal_copy_quote'])->name('quotes.quotesajax.modal_copy_quote');
Route::post( 'quotes/quotesajax/modal_create_quote', [QuotesAjaxController::class, 'modal_create_quote'])->name('quotes.quotesajax.modal_create_quote');
Route::post( 'quotes/quotesajax/modal_quote_to_invoice', [QuotesAjaxController::class, 'modal_quote_to_invoice'])->name('quotes.quotesajax.modal_quote_to_invoice');
Route::post( 'quotes/quotesajax/quote_to_invoice', [QuotesAjaxController::class, 'quote_to_invoice'])->name('quotes.quotesajax.quote_to_invoice');
Route::post( 'quotes/quotesajax/save', [QuotesAjaxController::class, 'save'])->name('quotes.quotesajax.save');
Route::post( 'quotes/quotesajax/save_quote_tax_rate', [QuotesAjaxController::class, 'save_quote_tax_rate'])->name('quotes.quotesajax.save_quote_tax_rate');
