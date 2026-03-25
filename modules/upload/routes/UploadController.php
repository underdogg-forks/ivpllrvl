<?php

use Illuminate\Support\Facades\Route;
use Modules\Upload\Controllers\UploadController;

// Route registrations for upload::UploadController.

Route::match(['GET'], 'upload/form', [UploadController::class, 'upload_file'])->name('upload.form');
Route::match(['GET'], 'upload/delete/{id}', [UploadController::class, 'delete_file'])->name('upload.delete.id');
Route::match(['POST'], 'upload/save', [UploadController::class, 'upload_file'])->name('upload.save');
