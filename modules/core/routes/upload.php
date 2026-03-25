<?php

use Illuminate\Support\Facades\Route;
use Modules\Upload\Controllers\UploadController;

// Route registrations for upload::UploadController.

Route::get( 'upload/form', [UploadController::class, 'upload_file'])->name('upload.form');
Route::post( 'upload/delete/{id}', [UploadController::class, 'delete_file'])->name('upload.delete.id');
Route::post( 'upload/save', [UploadController::class, 'upload_file'])->name('upload.save');
