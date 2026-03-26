<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Controllers\TaxRatesController;

// Route registrations for tax_rates::TaxRatesController.

Route::prefix('tax_rates/taxrates')
    ->name('tax_rates.taxrates.')
    ->controller(TaxRatesController::class)
    ->group(function () {
        Route::get('index', 'index')->name('index');
        Route::get('form', 'form')->name('form');
        Route::post('delete', 'delete')->name('delete');
    });
