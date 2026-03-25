<?php

namespace Modules\Quotes\Models;

use Modules\Quotes\Services\QuoteService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Quote extends QuoteService
{
}
