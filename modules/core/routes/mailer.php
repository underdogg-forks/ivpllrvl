<?php

use Illuminate\Support\Facades\Route;
use Modules\Mailer\Controllers\MailerController;

// Route registrations for mailer::MailerController.

Route::get( 'mailer/invoice/{id}', [MailerController::class, 'invoice'])->name('mailer.invoice.id');
Route::get( 'mailer/quote/{id}', [MailerController::class, 'quote'])->name('mailer.quote.id');
