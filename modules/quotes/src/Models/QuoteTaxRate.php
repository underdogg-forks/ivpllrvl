<?php

namespace Modules\Quotes\Models;

use Modules\Quotes\Services\QuoteTaxRateService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class QuoteTaxRate extends QuoteTaxRateService
{
    public $table = 'ip_quote_tax_rates';

    public $primary_key = 'ip_quote_tax_rates.quote_tax_rate_id';

    public $timestamps = false;
}
