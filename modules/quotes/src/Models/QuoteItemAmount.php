<?php

namespace Modules\Quotes\Models;

use Modules\Quotes\Services\QuoteItemAmountService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class QuoteItemAmount extends QuoteItemAmountService
{
    public $table = 'ip_quote_item_amounts';

    public $primary_key = 'ip_quote_item_amounts.item_amount_id';

    public $timestamps = false;
}
