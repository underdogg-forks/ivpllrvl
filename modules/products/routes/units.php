<?php

use Illuminate\Support\Facades\Route;
use Modules\Units\Controllers\UnitsController;

// Route registrations for units::UnitsController.

Route::get( 'units/index', [UnitsController::class, 'index'])->name('units.index');
Route::get( 'units/form', [UnitsController::class, 'form'])->name('units.form');
Route::get( 'units/form/{id}', [UnitsController::class, 'form'])->name('units.form.id');
Route::post( 'units/delete/{id}', [UnitsController::class, 'delete'])->name('units.delete.id');
