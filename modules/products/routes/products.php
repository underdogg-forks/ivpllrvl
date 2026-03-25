<?php

use Illuminate\Support\Facades\Route;
use Modules\Products\Controllers\ProductsController;

// Route registrations for products::ProductsController.

Route::get( 'products/index', [ProductsController::class, 'index'])->name('products.index');
Route::get( 'products/form', [ProductsController::class, 'form'])->name('products.form');
Route::get( 'products/form/{id}', [ProductsController::class, 'form'])->name('products.form.id');
Route::post( 'products/delete/{id}', [ProductsController::class, 'delete'])->name('products.delete.id');
