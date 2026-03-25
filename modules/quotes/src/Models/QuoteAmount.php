<?php

namespace Modules\Quotes\Models;

use Modules\Quotes\Services\QuoteAmountService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class QuoteAmount extends QuoteAmountService
{
}
