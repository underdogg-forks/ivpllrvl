<?php

use Illuminate\Support\Facades\Route;
use Modules\EmailTemplates\Controllers\EmailTemplatesController;

// Auto-generated routes for email_templates::EmailTemplatesController actions.

Route::match(['POST'], 'email_templates/emailtemplates/delete', [EmailTemplatesController::class, 'delete'])->name('email_templates.emailtemplates.delete');
Route::match(['GET'], 'email_templates/emailtemplates/form', [EmailTemplatesController::class, 'form'])->name('email_templates.emailtemplates.form');
Route::match(['GET'], 'email_templates/emailtemplates/index', [EmailTemplatesController::class, 'index'])->name('email_templates.emailtemplates.index');
