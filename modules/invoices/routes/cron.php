<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Controllers\CronController;

// Route registrations for invoices::CronController actions.

Route::get('invoices/cron/recur', [CronController::class, 'recur'])->name('invoices.cron.recur');
