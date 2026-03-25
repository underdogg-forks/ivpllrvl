<?php

use Illuminate\Support\Facades\Route;
use Modules\Tasks\Controllers\TasksController;

// Route registrations for tasks::TasksController.

Route::match(['GET'], 'tasks/index', [TasksController::class, 'index'])->name('tasks.index');
Route::match(['GET'], 'tasks/form', [TasksController::class, 'form'])->name('tasks.form');
Route::match(['GET'], 'tasks/form/{id}', [TasksController::class, 'form'])->name('tasks.form.id');
Route::match(['GET'], 'tasks/delete/{id}', [TasksController::class, 'delete'])->name('tasks.delete.id');
