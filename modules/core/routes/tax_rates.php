<?php

use Illuminate\Support\Facades\Route;
use Modules\TaxRates\Controllers\TaxRatesController;

// Auto-generated routes for tax_rates::TaxRatesController actions.

Route::post( 'tax_rates/taxrates/delete', [TaxRatesController::class, 'delete'])->name('tax_rates.taxrates.delete');
Route::get( 'tax_rates/taxrates/form', [TaxRatesController::class, 'form'])->name('tax_rates.taxrates.form');
Route::get( 'tax_rates/taxrates/index', [TaxRatesController::class, 'index'])->name('tax_rates.taxrates.index');
