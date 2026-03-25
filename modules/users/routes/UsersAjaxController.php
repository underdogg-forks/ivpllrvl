<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Controllers\UsersAjaxController;

// Auto-generated routes for users::UsersAjaxController actions.

Route::match(['POST'], 'users/usersajax/get_latest', [UsersAjaxController::class, 'get_latest'])->name('users.usersajax.get_latest');
Route::match(['POST'], 'users/usersajax/load_user_client_table', [UsersAjaxController::class, 'load_user_client_table'])->name('users.usersajax.load_user_client_table');
Route::match(['POST'], 'users/usersajax/modal_add_user_client', [UsersAjaxController::class, 'modal_add_user_client'])->name('users.usersajax.modal_add_user_client');
Route::match(['POST'], 'users/usersajax/name_query', [UsersAjaxController::class, 'name_query'])->name('users.usersajax.name_query');
Route::match(['POST'], 'users/usersajax/save_preference_permissive_search_users', [UsersAjaxController::class, 'save_preference_permissive_search_users'])->name('users.usersajax.save_preference_permissive_search_users');
Route::match(['POST'], 'users/usersajax/save_user_client', [UsersAjaxController::class, 'save_user_client'])->name('users.usersajax.save_user_client');
