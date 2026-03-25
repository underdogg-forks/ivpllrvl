<?php

use Illuminate\Support\Facades\Route;
use Modules\Products\Controllers\ProductsController;

// Auto-generated routes for products::ProductsController actions.

Route::match(['POST'], 'products/products/delete', [ProductsController::class, 'delete'])->name('products.products.delete');
Route::match(['GET'], 'products/products/form', [ProductsController::class, 'form'])->name('products.products.form');
Route::match(['GET'], 'products/products/index', [ProductsController::class, 'index'])->name('products.products.index');
