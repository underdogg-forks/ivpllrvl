<?php

use Illuminate\Support\Facades\Route;
use Modules\UserClients\Controllers\UserClientsController;

// Route registrations for user_clients::UserClientsController.

Route::prefix('user_clients')
    ->name('user_clients.')
    ->controller(UserClientsController::class)
    ->group(function () {
        Route::get('index', 'index')->name('index');
        Route::get('form', 'create')->name('form');
        Route::get('form/{id}', 'create')->name('form.id')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete.id')->whereNumber('id');
    });
