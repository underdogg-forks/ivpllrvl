<?php

namespace Modules\Quotes\Models;

use Modules\Quotes\Services\QuoteItemService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class QuoteItem extends QuoteItemService
{
    public $table = 'ip_quote_items';

    public $primary_key = 'ip_quote_items.item_id';

    public $timestamps = false;

    public $date_created_field = 'item_date_added';
}
