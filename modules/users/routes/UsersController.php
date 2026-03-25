<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Controllers\UsersController;

// Auto-generated routes for users::UsersController actions.

Route::match(['GET'], 'users/users/change_password', [UsersController::class, 'change_password'])->name('users.users.change_password');
Route::match(['POST'], 'users/users/delete', [UsersController::class, 'delete'])->name('users.users.delete');
Route::match(['POST'], 'users/users/delete_user_client', [UsersController::class, 'delete_user_client'])->name('users.users.delete_user_client');
Route::match(['GET'], 'users/users/form', [UsersController::class, 'form'])->name('users.users.form');
Route::match(['GET'], 'users/users/index', [UsersController::class, 'index'])->name('users.users.index');
