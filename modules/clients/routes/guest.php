<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Controllers\GuestController;

// Auto-generated routes for guest::GuestController actions.

Route::get( 'guest/guest/index', [GuestController::class, 'index'])->name('guest.guest.index');
