<?php

namespace Modules\Invoices\Models;

use Modules\Invoices\Services\ItemService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Item extends ItemService
{
}
