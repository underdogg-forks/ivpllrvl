<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Controllers\InvoicesController;

// Route registrations for invoices::InvoicesController.

Route::prefix('invoices')
    ->name('invoices.')
    ->controller(InvoicesController::class)
    ->group(function () {
        Route::get('index', 'index')->name('index');
        Route::get('archive', 'archive')->name('archive');
        Route::get('view/{id}', 'view')->name('view.id')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete.id')->whereNumber('id');
        Route::get('generate_pdf/{id}', 'generate_pdf')->name('generate_pdf.id')->whereNumber('id');

        Route::prefix('status')->name('status.')->group(function () {
            Route::get('all', 'status')->name('all');
            Route::get('draft', 'status')->name('draft');
            Route::get('overdue', 'status')->name('overdue');
            Route::get('paid', 'status')->name('paid');
            Route::get('sent', 'status')->name('sent');
            Route::get('viewed', 'status')->name('viewed');
        });
    });
