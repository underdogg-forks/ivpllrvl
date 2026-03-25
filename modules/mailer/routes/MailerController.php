<?php

use Illuminate\Support\Facades\Route;
use Modules\Mailer\Controllers\MailerController;

// Route registrations for mailer::MailerController.

Route::match(['GET'], 'mailer/invoice/{id}', [MailerController::class, 'invoice'])->name('mailer.invoice.id');
Route::match(['GET'], 'mailer/quote/{id}', [MailerController::class, 'quote'])->name('mailer.quote.id');
