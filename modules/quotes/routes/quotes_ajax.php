<?php

use Illuminate\Support\Facades\Route;
use Modules\Quotes\Controllers\QuotesAjaxController;

// Route registrations for quotes::QuotesAjaxController.

Route::prefix('quotes/quotesajax')
    ->name('quotes.quotesajax.')
    ->controller(QuotesAjaxController::class)
    ->group(function () {
        Route::post('change_client', 'change_client')->name('change_client');
        Route::post('change_user', 'change_user')->name('change_user');
        Route::post('copy_quote', 'copy_quote')->name('copy_quote');
        Route::post('create', 'create')->name('create');
        Route::post('delete_item', 'delete_item')->name('delete_item');
        Route::post('get_item', 'get_item')->name('get_item');
        Route::post('modal_change_client', 'modal_change_client')->name('modal_change_client');
        Route::post('modal_change_user', 'modal_change_user')->name('modal_change_user');
        Route::post('modal_copy_quote', 'modal_copy_quote')->name('modal_copy_quote');
        Route::post('modal_create_quote', 'modal_create_quote')->name('modal_create_quote');
        Route::post('modal_quote_to_invoice', 'modal_quote_to_invoice')->name('modal_quote_to_invoice');
        Route::post('quote_to_invoice', 'quote_to_invoice')->name('quote_to_invoice');
        Route::post('save', 'save')->name('save');
        Route::post('save_quote_tax_rate', 'save_quote_tax_rate')->name('save_quote_tax_rate');
    });
