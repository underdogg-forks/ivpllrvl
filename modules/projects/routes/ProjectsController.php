<?php

use Illuminate\Support\Facades\Route;
use Modules\Projects\Controllers\ProjectsController;

// Route registrations for projects::ProjectsController.

Route::match(['GET'], 'projects/index', [ProjectsController::class, 'index'])->name('projects.index');
Route::match(['GET'], 'projects/view/{id}', [ProjectsController::class, 'view'])->name('projects.view.id');
Route::match(['GET'], 'projects/form', [ProjectsController::class, 'form'])->name('projects.form');
Route::match(['GET'], 'projects/form/{id}', [ProjectsController::class, 'form'])->name('projects.form.id');
Route::match(['GET'], 'projects/delete/{id}', [ProjectsController::class, 'delete'])->name('projects.delete.id');
