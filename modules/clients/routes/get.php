<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Controllers\GetController;

// Auto-generated routes for guest::GetController actions.

Route::get( 'guest/get/attachment', [GetController::class, 'attachment'])->name('guest.get.attachment');
Route::get( 'guest/get/get_file', [GetController::class, 'get_file'])->name('guest.get.get_file');
Route::get( 'guest/get/show_files', [GetController::class, 'show_files'])->name('guest.get.show_files');
