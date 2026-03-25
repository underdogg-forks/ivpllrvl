<?php

use Illuminate\Support\Facades\Route;

Route::any('{any}', function () {
    require base_path('bootstrap/invoiceplane.php');
})->where('any', '.*');
