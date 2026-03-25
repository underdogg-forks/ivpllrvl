<?php

namespace Modules\Quotes\Models;

use Modules\Quotes\Services\QuoteItemService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class QuoteItem extends QuoteItemService
{
}
