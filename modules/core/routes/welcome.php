<?php

use Illuminate\Support\Facades\Route;
use Modules\Welcome\Controllers\WelcomeController;

// Manually maintained routes for welcome::WelcomeController actions.

Route::get('welcome/welcome/index', [WelcomeController::class, 'index'])->name('welcome.welcome.index');
