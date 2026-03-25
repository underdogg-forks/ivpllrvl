<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Controllers\QuotesController;

// Route registrations for guest::QuotesController.

Route::get('guest/quote/{id}', [QuotesController::class, 'view'])->name('guest.quote.id');
