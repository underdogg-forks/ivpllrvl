<?php

use Illuminate\Support\Facades\Route;
use Modules\UserClients\Controllers\UserClientsController;

// Route registrations for user_clients::UserClientsController.

Route::get( 'user_clients/index', [UserClientsController::class, 'index'])->name('user_clients.index');
Route::get( 'user_clients/form', [UserClientsController::class, 'create'])->name('user_clients.form');
Route::get( 'user_clients/form/{id}', [UserClientsController::class, 'create'])->name('user_clients.form.id');
Route::post( 'user_clients/delete/{id}', [UserClientsController::class, 'delete'])->name('user_clients.delete.id');
