<?php

use Illuminate\Support\Facades\Route;
use Modules\Products\Controllers\ProductsController;

// Route registrations for products::ProductsController.

Route::match(['GET'], 'products/index', [ProductsController::class, 'index'])->name('products.index');
Route::match(['GET'], 'products/form', [ProductsController::class, 'form'])->name('products.form');
Route::match(['GET'], 'products/form/{id}', [ProductsController::class, 'form'])->name('products.form.id');
Route::match(['GET'], 'products/delete/{id}', [ProductsController::class, 'delete'])->name('products.delete.id');
