<?php

use Illuminate\Support\Facades\Route;
use Modules\Clients\Controllers\ClientsController;

// Auto-generated routes for clients::ClientsController actions.

Route::match(['POST'], 'clients/clients/delete', [ClientsController::class, 'delete'])->name('clients.clients.delete');
Route::match(['GET'], 'clients/clients/form', [ClientsController::class, 'form'])->name('clients.clients.form');
Route::match(['GET'], 'clients/clients/index', [ClientsController::class, 'index'])->name('clients.clients.index');
Route::match(['GET'], 'clients/clients/status', [ClientsController::class, 'status'])->name('clients.clients.status');
Route::match(['GET'], 'clients/clients/view', [ClientsController::class, 'view'])->name('clients.clients.view');
