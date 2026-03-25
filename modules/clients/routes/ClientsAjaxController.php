<?php

use Illuminate\Support\Facades\Route;
use Modules\Clients\Controllers\ClientsAjaxController;

// Auto-generated routes for clients::ClientsAjaxController actions.

Route::match(['POST'], 'clients/clientsajax/delete_client_note', [ClientsAjaxController::class, 'delete_client_note'])->name('clients.clientsajax.delete_client_note');
Route::match(['POST'], 'clients/clientsajax/get_latest', [ClientsAjaxController::class, 'get_latest'])->name('clients.clientsajax.get_latest');
Route::match(['POST'], 'clients/clientsajax/load_client_notes', [ClientsAjaxController::class, 'load_client_notes'])->name('clients.clientsajax.load_client_notes');
Route::match(['POST'], 'clients/clientsajax/name_query', [ClientsAjaxController::class, 'name_query'])->name('clients.clientsajax.name_query');
Route::match(['POST'], 'clients/clientsajax/save_client_note', [ClientsAjaxController::class, 'save_client_note'])->name('clients.clientsajax.save_client_note');
Route::match(['POST'], 'clients/clientsajax/save_preference_permissive_search_clients', [ClientsAjaxController::class, 'save_preference_permissive_search_clients'])->name('clients.clientsajax.save_preference_permissive_search_clients');
