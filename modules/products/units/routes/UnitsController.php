<?php

use Illuminate\Support\Facades\Route;
use Modules\Units\Controllers\UnitsController;

// Route registrations for units::UnitsController.

Route::match(['GET'], 'units/index', [UnitsController::class, 'index'])->name('units.index');
Route::match(['GET'], 'units/form', [UnitsController::class, 'form'])->name('units.form');
Route::match(['GET'], 'units/form/{id}', [UnitsController::class, 'form'])->name('units.form.id');
Route::match(['GET'], 'units/delete/{id}', [UnitsController::class, 'delete'])->name('units.delete.id');
