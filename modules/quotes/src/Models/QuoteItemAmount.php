<?php

namespace Modules\Quotes\Models;

use Modules\Quotes\Services\QuoteItemAmountService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class QuoteItemAmount extends QuoteItemAmountService
{
}
