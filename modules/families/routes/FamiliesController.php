<?php

use Illuminate\Support\Facades\Route;
use Modules\Families\Controllers\FamiliesController;

// Auto-generated routes for families::FamiliesController actions.

Route::match(['POST'], 'families/families/delete', [FamiliesController::class, 'delete'])->name('families.families.delete');
Route::match(['GET'], 'families/families/form', [FamiliesController::class, 'form'])->name('families.families.form');
Route::match(['GET'], 'families/families/index', [FamiliesController::class, 'index'])->name('families.families.index');
