<?php

namespace Modules\TaxRates\Models;

use Modules\TaxRates\Services\TaxRateService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class TaxRate extends TaxRateService
{
    public $table = 'ip_tax_rates';

    public $primary_key = 'ip_tax_rates.tax_rate_id';

    public $timestamps = false;
}
