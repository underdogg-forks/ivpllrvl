<?php

namespace Modules\Quotes\Models;

use Modules\Quotes\Services\QuoteService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Quote extends QuoteService
{
    public $table = 'ip_quotes';

    public $primary_key = 'ip_quotes.quote_id';

    public $timestamps = false;

    public $date_modified_field = 'quote_date_modified';
}
