<?php

use Illuminate\Support\Facades\Route;
use Modules\Clients\Controllers\InvoicesController;

// Route registrations for guest::InvoicesController.

Route::get('guest/invoice/{id}', [InvoicesController::class, 'view'])->name('guest.invoice.id');
