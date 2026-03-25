<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Controllers\UsersController;

// Route registrations for users::UsersController.

Route::get( 'users', [UsersController::class, 'index'])->name('users');
Route::get( 'users/index', [UsersController::class, 'index'])->name('users.index');
Route::get( 'users/form', [UsersController::class, 'form'])->name('users.form');
Route::get( 'users/form/{id}', [UsersController::class, 'form'])->name('users.form.id');
Route::get( 'users/change_password/{id}', [UsersController::class, 'change_password'])->name('users.change_password.id');
Route::post( 'users/delete/{id}', [UsersController::class, 'delete'])->name('users.delete.id');
