<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Controllers\GuestController;

// Route registrations for guest::GuestController actions.

Route::get('guest/guest/index', [GuestController::class, 'index'])->name('guest.guest.index');
