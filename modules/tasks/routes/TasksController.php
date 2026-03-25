<?php

use Illuminate\Support\Facades\Route;
use Modules\Tasks\Controllers\TasksController;

// Auto-generated routes for tasks::TasksController actions.

Route::match(['POST'], 'tasks/tasks/delete', [TasksController::class, 'delete'])->name('tasks.tasks.delete');
Route::match(['GET'], 'tasks/tasks/form', [TasksController::class, 'form'])->name('tasks.tasks.form');
Route::match(['GET'], 'tasks/tasks/index', [TasksController::class, 'index'])->name('tasks.tasks.index');
