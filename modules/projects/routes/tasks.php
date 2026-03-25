<?php

use Illuminate\Support\Facades\Route;
use Modules\Tasks\Controllers\TasksController;

// Route registrations for tasks::TasksController.

Route::prefix('tasks')
    ->name('tasks.')
    ->controller(TasksController::class)
    ->group(function () {
        Route::get('index', 'index')->name('index');
        Route::get('form', 'form')->name('form');
        Route::get('form/{id}', 'form')->name('form.id')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete.id')->whereNumber('id');
    });
