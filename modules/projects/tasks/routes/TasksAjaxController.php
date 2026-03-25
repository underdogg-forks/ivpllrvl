<?php

use Illuminate\Support\Facades\Route;
use Modules\Tasks\Controllers\TasksAjaxController;

// Auto-generated routes for tasks::TasksAjaxController actions.

Route::match(['POST'], 'tasks/tasksajax/modal_task_lookups', [TasksAjaxController::class, 'modal_task_lookups'])->name('tasks.tasksajax.modal_task_lookups');
Route::match(['POST'], 'tasks/tasksajax/process_task_selections', [TasksAjaxController::class, 'process_task_selections'])->name('tasks.tasksajax.process_task_selections');
