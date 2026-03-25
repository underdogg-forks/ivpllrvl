<?php

namespace Modules\Quotes\Models;

use Modules\Quotes\Services\QuoteAmountService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class QuoteAmount extends QuoteAmountService
{
    public $table = 'ip_quote_amounts';

    public $primary_key = 'ip_quote_amounts.quote_amount_id';

    public $timestamps = false;
}
