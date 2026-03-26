<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Controllers\UsersController;

// Route registrations for users::UsersController.

Route::get('users', [UsersController::class, 'index'])->name('users');

Route::prefix('users')
    ->name('users.')
    ->controller(UsersController::class)
    ->group(function () {
        Route::get('index', 'index')->name('index');
        Route::get('form', 'form')->name('form');
        Route::get('form/{id}', 'form')->name('form.id')->whereNumber('id');
        Route::get('change_password/{id}', 'change_password')->name('change_password.id')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete.id')->whereNumber('id');
    });
