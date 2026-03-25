<?php

use Illuminate\Support\Facades\Route;
use Modules\Projects\Controllers\ProjectsController;

// Route registrations for projects::ProjectsController.

Route::get( 'projects/index', [ProjectsController::class, 'index'])->name('projects.index');
Route::get( 'projects/view/{id}', [ProjectsController::class, 'view'])->name('projects.view.id');
Route::get( 'projects/form', [ProjectsController::class, 'form'])->name('projects.form');
Route::get( 'projects/form/{id}', [ProjectsController::class, 'form'])->name('projects.form.id');
Route::post( 'projects/delete/{id}', [ProjectsController::class, 'delete'])->name('projects.delete.id');
