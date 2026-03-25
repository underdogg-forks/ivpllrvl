<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Controllers\UsersController;

// Route registrations for users::UsersController.

Route::match(['GET'], 'users', [UsersController::class, 'index'])->name('users');
Route::match(['GET'], 'users/index', [UsersController::class, 'index'])->name('users.index');
Route::match(['GET'], 'users/form', [UsersController::class, 'form'])->name('users.form');
Route::match(['GET'], 'users/form/{id}', [UsersController::class, 'form'])->name('users.form.id');
Route::match(['GET'], 'users/change_password/{id}', [UsersController::class, 'change_password'])->name('users.change_password.id');
Route::match(['GET'], 'users/delete/{id}', [UsersController::class, 'delete'])->name('users.delete.id');
