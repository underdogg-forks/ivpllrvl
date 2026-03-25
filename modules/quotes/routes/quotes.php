<?php

use Illuminate\Support\Facades\Route;
use Modules\Quotes\Controllers\QuotesController;

// Route registrations for quotes::QuotesController.

Route::prefix('quotes')
    ->name('quotes.')
    ->controller(QuotesController::class)
    ->group(function () {
        Route::get('index', 'index')->name('index');
        Route::get('view/{id}', 'view')->name('view.id')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete.id')->whereNumber('id');
        Route::post('cancel/{id}', 'cancel')->name('cancel.id')->whereNumber('id');
        Route::get('generate_pdf/{id}', 'generate_pdf')->name('generate_pdf.id')->whereNumber('id');

        Route::prefix('status')->name('status.')->group(function () {
            Route::get('all', 'status')->name('all');
            Route::get('approved', 'status')->name('approved');
            Route::get('canceled', 'status')->name('canceled');
            Route::get('draft', 'status')->name('draft');
            Route::get('rejected', 'status')->name('rejected');
            Route::get('sent', 'status')->name('sent');
            Route::get('viewed', 'status')->name('viewed');
        });
    });
