<?php

namespace Modules\Invoices\Models;

use Modules\Invoices\Services\ItemAmountService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class ItemAmount extends ItemAmountService
{
    public $table = 'ip_invoice_item_amounts';

    public $primary_key = 'ip_invoice_item_amounts.item_amount_id';

    public $timestamps = false;
}
