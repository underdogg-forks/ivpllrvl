<?php

use Illuminate\Support\Facades\Route;
use Modules\Families\Controllers\FamiliesController;

// Route registrations for families::FamiliesController.

Route::match(['GET'], 'families/index', [FamiliesController::class, 'index'])->name('families.index');
Route::match(['GET'], 'families/form', [FamiliesController::class, 'form'])->name('families.form');
Route::match(['GET'], 'families/form/{id}', [FamiliesController::class, 'form'])->name('families.form.id');
Route::match(['GET'], 'families/delete/{id}', [FamiliesController::class, 'delete'])->name('families.delete.id');
