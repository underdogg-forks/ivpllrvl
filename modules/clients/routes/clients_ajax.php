<?php

use Illuminate\Support\Facades\Route;
use Modules\Clients\Controllers\ClientsAjaxController;

// Route registrations for clients::ClientsAjaxController.

Route::prefix('clients/clientsajax')
    ->name('clients.clientsajax.')
    ->controller(ClientsAjaxController::class)
    ->group(function () {
        Route::post('delete_client_note', 'delete_client_note')->name('delete_client_note');
        Route::post('get_latest', 'get_latest')->name('get_latest');
        Route::post('load_client_notes', 'load_client_notes')->name('load_client_notes');
        Route::post('name_query', 'name_query')->name('name_query');
        Route::post('save_client_note', 'save_client_note')->name('save_client_note');
        Route::post('save_preference_permissive_search_clients', 'save_preference_permissive_search_clients')->name('save_preference_permissive_search_clients');
    });
