<?php

use Illuminate\Support\Facades\Route;
use Modules\Units\Controllers\UnitsController;

// Auto-generated routes for units::UnitsController actions.

Route::match(['POST'], 'units/units/delete', [UnitsController::class, 'delete'])->name('units.units.delete');
Route::match(['GET'], 'units/units/form', [UnitsController::class, 'form'])->name('units.units.form');
Route::match(['GET'], 'units/units/index', [UnitsController::class, 'index'])->name('units.units.index');
