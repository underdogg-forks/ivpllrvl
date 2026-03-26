<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Controllers\MailerController;

// Route registrations for mailer::MailerController.

Route::prefix('mailer')
    ->name('mailer.')
    ->controller(MailerController::class)
    ->group(function () {
        Route::get('invoice/{id}', 'invoice')->name('invoice.id')->whereNumber('id');
        Route::get('quote/{id}', 'quote')->name('quote.id')->whereNumber('id');
    });
