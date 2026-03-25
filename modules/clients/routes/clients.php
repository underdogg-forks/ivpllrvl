<?php

use Illuminate\Support\Facades\Route;
use Modules\Clients\Controllers\ClientsController;

// Route registrations for clients::ClientsController.

Route::get( 'clients', [ClientsController::class, 'index'])->name('clients');
Route::get( 'clients/index', [ClientsController::class, 'index'])->name('clients.index');
Route::get( 'clients/status/{status}', [ClientsController::class, 'status'])->name('clients.status.status');
Route::get( 'clients/status/all', [ClientsController::class, 'status'])->name('clients.status.all');
Route::get( 'clients/status/active', [ClientsController::class, 'status'])->name('clients.status.active');
Route::get( 'clients/status/inactive', [ClientsController::class, 'status'])->name('clients.status.inactive');
Route::get( 'clients/view/{id}', [ClientsController::class, 'view'])->name('clients.view.id');
Route::get( 'clients/view/{id}/invoices', [ClientsController::class, 'view'])->name('clients.view.id.invoices');
Route::get( 'clients/form', [ClientsController::class, 'form'])->name('clients.form');
Route::get( 'clients/form/{id}', [ClientsController::class, 'form'])->name('clients.form.id');
Route::post( 'clients/delete/{id}', [ClientsController::class, 'delete'])->name('clients.delete.id');
Route::post( 'clients/remove/{id}', [ClientsController::class, 'delete'])->name('clients.remove.id');
