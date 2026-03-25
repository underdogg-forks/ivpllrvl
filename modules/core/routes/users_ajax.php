<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Controllers\UsersAjaxController;

// Route registrations for users::UsersAjaxController.

Route::prefix('users/usersajax')
    ->name('users.usersajax.')
    ->controller(UsersAjaxController::class)
    ->group(function () {
        Route::post('get_latest', 'get_latest')->name('get_latest');
        Route::post('load_user_client_table', 'load_user_client_table')->name('load_user_client_table');
        Route::post('modal_add_user_client', 'modal_add_user_client')->name('modal_add_user_client');
        Route::post('name_query', 'name_query')->name('name_query');
        Route::post('save_preference_permissive_search_users', 'save_preference_permissive_search_users')->name('save_preference_permissive_search_users');
        Route::post('save_user_client', 'save_user_client')->name('save_user_client');
    });
