<?php

use Illuminate\Support\Facades\Route;
use Modules\EmailTemplates\Controllers\EmailTemplatesController;

// Route registrations for email_templates::EmailTemplatesController.

Route::match(['GET'], 'email_templates/index', [EmailTemplatesController::class, 'index'])->name('email_templates.index');
Route::match(['GET'], 'email_templates/form', [EmailTemplatesController::class, 'form'])->name('email_templates.form');
Route::match(['GET'], 'email_templates/form/{id}', [EmailTemplatesController::class, 'form'])->name('email_templates.form.id');
Route::match(['GET'], 'email_templates/delete/{id}', [EmailTemplatesController::class, 'delete'])->name('email_templates.delete.id');
