<?php

use Illuminate\Support\Facades\Route;
use Modules\Projects\Controllers\ProjectsController;

// Auto-generated routes for projects::ProjectsController actions.

Route::match(['POST'], 'projects/projects/delete', [ProjectsController::class, 'delete'])->name('projects.projects.delete');
Route::match(['GET'], 'projects/projects/form', [ProjectsController::class, 'form'])->name('projects.projects.form');
Route::match(['GET'], 'projects/projects/index', [ProjectsController::class, 'index'])->name('projects.projects.index');
Route::match(['GET'], 'projects/projects/view', [ProjectsController::class, 'view'])->name('projects.projects.view');
