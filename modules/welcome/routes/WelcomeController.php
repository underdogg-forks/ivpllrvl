<?php

use Illuminate\Support\Facades\Route;
use Modules\Welcome\Controllers\WelcomeController;

// Auto-generated routes for welcome::WelcomeController actions.

Route::match(['GET'], 'welcome/welcome/index', [WelcomeController::class, 'index'])->name('welcome.welcome.index');
