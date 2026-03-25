<?php

use Illuminate\Support\Facades\Route;
use Modules\Tasks\Controllers\TasksController;

// Route registrations for tasks::TasksController.

Route::get( 'tasks/index', [TasksController::class, 'index'])->name('tasks.index');
Route::get( 'tasks/form', [TasksController::class, 'form'])->name('tasks.form');
Route::get( 'tasks/form/{id}', [TasksController::class, 'form'])->name('tasks.form.id');
Route::post( 'tasks/delete/{id}', [TasksController::class, 'delete'])->name('tasks.delete.id');
