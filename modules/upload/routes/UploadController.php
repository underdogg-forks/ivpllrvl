<?php

use Illuminate\Support\Facades\Route;
use Modules\Upload\Controllers\UploadController;

// Auto-generated routes for upload::UploadController actions.

Route::match(['POST'], 'upload/upload/create_dir', [UploadController::class, 'create_dir'])->name('upload.upload.create_dir');
Route::match(['POST'], 'upload/upload/delete_file', [UploadController::class, 'delete_file'])->name('upload.upload.delete_file');
Route::match(['GET'], 'upload/upload/get_file', [UploadController::class, 'get_file'])->name('upload.upload.get_file');
Route::match(['GET'], 'upload/upload/show_files', [UploadController::class, 'show_files'])->name('upload.upload.show_files');
Route::match(['GET'], 'upload/upload/upload_file', [UploadController::class, 'upload_file'])->name('upload.upload.upload_file');
