<?php

use Illuminate\Support\Facades\Route;
use Modules\Products\Controllers\ProductsAjaxController;

// Route registrations for products::ProductsAjaxController.

Route::prefix('products/productsajax')
    ->name('products.productsajax.')
    ->controller(ProductsAjaxController::class)
    ->group(function () {
        Route::post('modal_product_lookups', 'modal_product_lookups')->name('modal_product_lookups');
        Route::post('process_product_selections', 'process_product_selections')->name('process_product_selections');
    });
