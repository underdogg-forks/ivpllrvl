<?php

namespace Modules\TaxRates\Models;

use Modules\TaxRates\Services\TaxRateService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class TaxRate extends TaxRateService
{
}
