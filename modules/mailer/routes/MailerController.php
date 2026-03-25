<?php

use Illuminate\Support\Facades\Route;
use Modules\Mailer\Controllers\MailerController;

// Auto-generated routes for mailer::MailerController actions.

Route::match(['GET'], 'mailer/mailer/invoice', [MailerController::class, 'invoice'])->name('mailer.mailer.invoice');
Route::match(['GET'], 'mailer/mailer/quote', [MailerController::class, 'quote'])->name('mailer.mailer.quote');
Route::match(['GET'], 'mailer/mailer/send_invoice', [MailerController::class, 'send_invoice'])->name('mailer.mailer.send_invoice');
Route::match(['GET'], 'mailer/mailer/send_quote', [MailerController::class, 'send_quote'])->name('mailer.mailer.send_quote');
