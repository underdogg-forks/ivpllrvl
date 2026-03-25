<?php

use Illuminate\Support\Facades\Route;
use Modules\Upload\Controllers\UploadController;

// Route registrations for upload::UploadController.

Route::prefix('upload')
    ->name('upload.')
    ->controller(UploadController::class)
    ->group(function () {
        Route::get('form/{customerId}/{url_key}', 'upload_file')->name('form');
        Route::post('save/{customerId}/{url_key}', 'upload_file')->name('save');
        Route::post('delete/{url_key}', 'delete_file')->name('delete.url_key');
    });
