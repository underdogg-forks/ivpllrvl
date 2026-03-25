<?php

use Illuminate\Support\Facades\Route;
use Modules\Clients\Controllers\ClientsController;

// Route registrations for clients::ClientsController.

Route::get('clients', [ClientsController::class, 'index'])->name('clients');

Route::prefix('clients')
    ->name('clients.')
    ->controller(ClientsController::class)
    ->group(function () {
        Route::get('index', 'index')->name('index');

        Route::prefix('status')->name('status.')->group(function () {
            Route::get('all', 'status')->name('all');
            Route::get('active', 'status')->name('active');
            Route::get('inactive', 'status')->name('inactive');
            Route::get('{status}', 'status')->name('status');
        });

        Route::get('view/{id}', 'view')->name('view.id')->whereNumber('id');
        Route::get('view/{id}/invoices', 'view')->name('view.id.invoices')->whereNumber('id');
        Route::get('form', 'form')->name('form');
        Route::get('form/{id}', 'form')->name('form.id')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete.id')->whereNumber('id');
        Route::post('remove/{id}', 'delete')->name('remove.id')->whereNumber('id');
    });
