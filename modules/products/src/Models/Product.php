<?php

namespace Modules\Products\Models;

use Modules\Products\Services\ProductService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Product extends ProductService
{
}
