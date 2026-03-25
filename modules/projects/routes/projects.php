<?php

use Illuminate\Support\Facades\Route;
use Modules\Projects\Controllers\ProjectsController;

// Route registrations for projects::ProjectsController.

Route::prefix('projects')
    ->name('projects.')
    ->controller(ProjectsController::class)
    ->group(function () {
        Route::get('index', 'index')->name('index');
        Route::get('view/{id}', 'view')->name('view.id')->whereNumber('id');
        Route::get('form', 'form')->name('form');
        Route::get('form/{id}', 'form')->name('form.id')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete.id')->whereNumber('id');
    });
