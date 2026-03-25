<?php

use Illuminate\Support\Facades\Route;
use Modules\Products\Controllers\ProductsAjaxController;

// Auto-generated routes for products::ProductsAjaxController actions.

Route::post( 'products/productsajax/modal_product_lookups', [ProductsAjaxController::class, 'modal_product_lookups'])->name('products.productsajax.modal_product_lookups');
Route::post( 'products/productsajax/process_product_selections', [ProductsAjaxController::class, 'process_product_selections'])->name('products.productsajax.process_product_selections');
