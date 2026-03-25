<?php

use Illuminate\Support\Facades\Route;
use Modules\Filter\Controllers\FilterAjaxController;

// Route registrations for filter::FilterAjaxController.

Route::prefix('filter/filterajax')
    ->name('filter.filterajax.')
    ->controller(FilterAjaxController::class)
    ->group(function () {
        Route::post('filter_archives', 'filter_archives')->name('filter_archives');
        Route::post('filter_clients', 'filter_clients')->name('filter_clients');
        Route::post('filter_custom_fields', 'filter_custom_fields')->name('filter_custom_fields');
        Route::post('filter_custom_values', 'filter_custom_values')->name('filter_custom_values');
        Route::post('filter_custom_values_field', 'filter_custom_values_field')->name('filter_custom_values_field');
        Route::post('filter_families', 'filter_families')->name('filter_families');
        Route::post('filter_invoices', 'filter_invoices')->name('filter_invoices');
        Route::post('filter_invoices_recurring', 'filter_invoices_recurring')->name('filter_invoices_recurring');
        Route::post('filter_online_logs', 'filter_online_logs')->name('filter_online_logs');
        Route::post('filter_payments', 'filter_payments')->name('filter_payments');
        Route::post('filter_products', 'filter_products')->name('filter_products');
        Route::post('filter_projects', 'filter_projects')->name('filter_projects');
        Route::post('filter_quotes', 'filter_quotes')->name('filter_quotes');
        Route::post('filter_tasks', 'filter_tasks')->name('filter_tasks');
        Route::post('filter_users', 'filter_users')->name('filter_users');
    });
