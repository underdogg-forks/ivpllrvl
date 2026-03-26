<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Controllers\InvoiceGroupsController;

// Route registrations for invoice_groups::InvoiceGroupsController.

Route::prefix('invoice_groups')
    ->name('invoice_groups.')
    ->controller(InvoiceGroupsController::class)
    ->group(function () {
        Route::get('index', 'index')->name('index');
        Route::get('form', 'form')->name('form');
        Route::get('form/{id}', 'form')->name('form.id')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete.id')->whereNumber('id');
    });
