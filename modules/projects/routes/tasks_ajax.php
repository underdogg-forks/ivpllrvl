<?php

use Illuminate\Support\Facades\Route;
use Modules\Tasks\Controllers\TasksAjaxController;

// Auto-generated routes for tasks::TasksAjaxController actions.

Route::post( 'tasks/tasksajax/modal_task_lookups', [TasksAjaxController::class, 'modal_task_lookups'])->name('tasks.tasksajax.modal_task_lookups');
Route::post( 'tasks/tasksajax/process_task_selections', [TasksAjaxController::class, 'process_task_selections'])->name('tasks.tasksajax.process_task_selections');
