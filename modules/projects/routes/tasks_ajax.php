<?php

use Illuminate\Support\Facades\Route;
use Modules\Projects\Controllers\TasksAjaxController;

// Route registrations for tasks::TasksAjaxController.

Route::prefix('tasks/tasksajax')
    ->name('tasks.tasksajax.')
    ->controller(TasksAjaxController::class)
    ->group(function () {
        Route::post('modal_task_lookups', 'modal_task_lookups')->name('modal_task_lookups');
        Route::post('process_task_selections', 'process_task_selections')->name('process_task_selections');
    });
