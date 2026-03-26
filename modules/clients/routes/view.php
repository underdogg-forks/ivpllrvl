<?php

use Illuminate\Support\Facades\Route;
use Modules\Clients\Controllers\ViewController;

// Route registrations for guest::ViewController.

Route::get('guest/view/{id}', [ViewController::class, 'invoice'])->name('guest.view.id');
