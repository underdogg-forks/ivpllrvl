<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Controllers\InvoicesController;

// Route registrations for guest::InvoicesController.

Route::match(['GET'], 'guest/invoice/{id}', [InvoicesController::class, 'view'])->name('guest.invoice.id');
