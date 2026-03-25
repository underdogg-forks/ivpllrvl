<?php

use Illuminate\Support\Facades\Route;
use Modules\EmailTemplates\Controllers\EmailTemplatesController;

// Route registrations for email_templates::EmailTemplatesController.

Route::prefix('email_templates')
    ->name('email_templates.')
    ->controller(EmailTemplatesController::class)
    ->group(function () {
        Route::get('index', 'index')->name('index');
        Route::get('form', 'form')->name('form');
        Route::get('form/{id}', 'form')->name('form.id')->whereNumber('id');
        Route::post('delete/{id}', 'delete')->name('delete.id')->whereNumber('id');
    });
