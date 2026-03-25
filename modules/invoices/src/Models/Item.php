<?php

namespace Modules\Invoices\Models;

use Modules\Invoices\Services\ItemService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Item extends ItemService
{
    public $table = 'ip_invoice_items';

    public $primary_key = 'ip_invoice_items.item_id';

    public $timestamps = false;

    public $date_created_field = 'item_date_added';
}
