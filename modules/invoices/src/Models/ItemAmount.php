<?php

namespace Modules\Invoices\Models;

use Modules\Invoices\Services\ItemAmountService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class ItemAmount extends ItemAmountService
{
}
