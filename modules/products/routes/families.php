<?php

use Illuminate\Support\Facades\Route;
use Modules\Families\Controllers\FamiliesController;

// Route registrations for families::FamiliesController.

Route::get( 'families/index', [FamiliesController::class, 'index'])->name('families.index');
Route::get( 'families/form', [FamiliesController::class, 'form'])->name('families.form');
Route::get( 'families/form/{id}', [FamiliesController::class, 'form'])->name('families.form.id');
Route::post( 'families/delete/{id}', [FamiliesController::class, 'delete'])->name('families.delete.id');
