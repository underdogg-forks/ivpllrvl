<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Controllers\InvoicesAjaxController;

// Route registrations for invoices::InvoicesAjaxController.

Route::prefix('invoices/invoicesajax')
    ->name('invoices.invoicesajax.')
    ->controller(InvoicesAjaxController::class)
    ->group(function () {
        Route::post('change_client', 'change_client')->name('change_client');
        Route::post('change_user', 'change_user')->name('change_user');
        Route::post('copy_invoice', 'copy_invoice')->name('copy_invoice');
        Route::post('create', 'create')->name('create');
        Route::post('create_credit', 'create_credit')->name('create_credit');
        Route::post('create_recurring', 'create_recurring')->name('create_recurring');
        Route::post('delete_item', 'delete_item')->name('delete_item');
        Route::post('get_item', 'get_item')->name('get_item');
        Route::post('get_recur_start_date', 'get_recur_start_date')->name('get_recur_start_date');
        Route::post('modal_change_client', 'modal_change_client')->name('modal_change_client');
        Route::post('modal_change_user', 'modal_change_user')->name('modal_change_user');
        Route::post('modal_copy_invoice', 'modal_copy_invoice')->name('modal_copy_invoice');
        Route::post('modal_create_credit', 'modal_create_credit')->name('modal_create_credit');
        Route::post('modal_create_invoice', 'modal_create_invoice')->name('modal_create_invoice');
        Route::post('modal_create_recurring', 'modal_create_recurring')->name('modal_create_recurring');
        Route::post('save', 'save')->name('save');
        Route::post('save_invoice_tax_rate', 'save_invoice_tax_rate')->name('save_invoice_tax_rate');
    });
