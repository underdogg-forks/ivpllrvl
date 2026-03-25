<?php

use Illuminate\Support\Facades\Route;
use Modules\EmailTemplates\Controllers\EmailTemplatesController;

// Route registrations for email_templates::EmailTemplatesController.

Route::get( 'email_templates/index', [EmailTemplatesController::class, 'index'])->name('email_templates.index');
Route::get( 'email_templates/form', [EmailTemplatesController::class, 'form'])->name('email_templates.form');
Route::get( 'email_templates/form/{id}', [EmailTemplatesController::class, 'form'])->name('email_templates.form.id');
Route::post( 'email_templates/delete/{id}', [EmailTemplatesController::class, 'delete'])->name('email_templates.delete.id');
