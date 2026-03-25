<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Controllers\ViewController;

// Route registrations for guest::ViewController.

Route::match(['GET'], 'guest/view/{id}', [ViewController::class, 'invoice'])->name('guest.view.id');
