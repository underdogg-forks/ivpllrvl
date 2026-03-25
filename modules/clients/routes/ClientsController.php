<?php

use Illuminate\Support\Facades\Route;
use Modules\Clients\Controllers\ClientsController;

// Route registrations for clients::ClientsController.

Route::match(['GET'], 'clients', [ClientsController::class, 'index'])->name('clients');
Route::match(['GET'], 'clients/index', [ClientsController::class, 'index'])->name('clients.index');
Route::match(['GET'], 'clients/status/{status}', [ClientsController::class, 'status'])->name('clients.status.status');
Route::match(['GET'], 'clients/status/all', [ClientsController::class, 'status'])->name('clients.status.all');
Route::match(['GET'], 'clients/status/active', [ClientsController::class, 'status'])->name('clients.status.active');
Route::match(['GET'], 'clients/status/inactive', [ClientsController::class, 'status'])->name('clients.status.inactive');
Route::match(['GET'], 'clients/view/{id}', [ClientsController::class, 'view'])->name('clients.view.id');
Route::match(['GET'], 'clients/view/{id}/invoices', [ClientsController::class, 'view'])->name('clients.view.id.invoices');
Route::match(['GET'], 'clients/form', [ClientsController::class, 'form'])->name('clients.form');
Route::match(['GET'], 'clients/form/{id}', [ClientsController::class, 'form'])->name('clients.form.id');
Route::match(['GET'], 'clients/delete/{id}', [ClientsController::class, 'delete'])->name('clients.delete.id');
Route::match(['GET'], 'clients/remove/{id}', [ClientsController::class, 'delete'])->name('clients.remove.id');
