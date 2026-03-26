<?php

namespace Modules\Core\Models;

use Modules\Core\Services\QuoteCustomService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class QuoteCustom extends QuoteCustomService
{
    public $table = 'ip_quote_custom';

    public $primary_key = 'ip_quote_custom.quote_custom_id';

    public $timestamps = false;
}
