<?php

use Illuminate\Support\Facades\Route;
use Modules\EmailTemplates\Controllers\EmailTemplatesAjaxController;

// Auto-generated routes for email_templates::EmailTemplatesAjaxController actions.

Route::match(['POST'], 'email_templates/emailtemplatesajax/get_content', [EmailTemplatesAjaxController::class, 'get_content'])->name('email_templates.emailtemplatesajax.get_content');
