<?php

use Illuminate\Support\Facades\Route;
use Modules\UserClients\Controllers\UserClientsController;

// Auto-generated routes for user_clients::UserClientsController actions.

Route::match(['POST'], 'user_clients/userclients/create', [UserClientsController::class, 'create'])->name('user_clients.userclients.create');
Route::match(['POST'], 'user_clients/userclients/delete', [UserClientsController::class, 'delete'])->name('user_clients.userclients.delete');
Route::match(['GET'], 'user_clients/userclients/index', [UserClientsController::class, 'index'])->name('user_clients.userclients.index');
Route::match(['GET'], 'user_clients/userclients/user', [UserClientsController::class, 'user'])->name('user_clients.userclients.user');
