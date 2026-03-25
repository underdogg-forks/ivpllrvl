<?php

use Illuminate\Support\Facades\Route;
use Modules\Filter\Controllers\FilterAjaxController;

// Auto-generated routes for filter::FilterAjaxController actions.

Route::post( 'filter/filterajax/filter_archives', [FilterAjaxController::class, 'filter_archives'])->name('filter.filterajax.filter_archives');
Route::post( 'filter/filterajax/filter_clients', [FilterAjaxController::class, 'filter_clients'])->name('filter.filterajax.filter_clients');
Route::post( 'filter/filterajax/filter_custom_fields', [FilterAjaxController::class, 'filter_custom_fields'])->name('filter.filterajax.filter_custom_fields');
Route::post( 'filter/filterajax/filter_custom_values', [FilterAjaxController::class, 'filter_custom_values'])->name('filter.filterajax.filter_custom_values');
Route::post( 'filter/filterajax/filter_custom_values_field', [FilterAjaxController::class, 'filter_custom_values_field'])->name('filter.filterajax.filter_custom_values_field');
Route::post( 'filter/filterajax/filter_families', [FilterAjaxController::class, 'filter_families'])->name('filter.filterajax.filter_families');
Route::post( 'filter/filterajax/filter_invoices', [FilterAjaxController::class, 'filter_invoices'])->name('filter.filterajax.filter_invoices');
Route::post( 'filter/filterajax/filter_invoices_recuring', [FilterAjaxController::class, 'filter_invoices_recuring'])->name('filter.filterajax.filter_invoices_recuring');
Route::post( 'filter/filterajax/filter_online_logs', [FilterAjaxController::class, 'filter_online_logs'])->name('filter.filterajax.filter_online_logs');
Route::post( 'filter/filterajax/filter_payments', [FilterAjaxController::class, 'filter_payments'])->name('filter.filterajax.filter_payments');
Route::post( 'filter/filterajax/filter_products', [FilterAjaxController::class, 'filter_products'])->name('filter.filterajax.filter_products');
Route::post( 'filter/filterajax/filter_projects', [FilterAjaxController::class, 'filter_projects'])->name('filter.filterajax.filter_projects');
Route::post( 'filter/filterajax/filter_quotes', [FilterAjaxController::class, 'filter_quotes'])->name('filter.filterajax.filter_quotes');
Route::post( 'filter/filterajax/filter_tasks', [FilterAjaxController::class, 'filter_tasks'])->name('filter.filterajax.filter_tasks');
Route::post( 'filter/filterajax/filter_users', [FilterAjaxController::class, 'filter_users'])->name('filter.filterajax.filter_users');
