<?php

namespace Modules\Quotes\Models;

use Modules\Quotes\Services\QuoteTaxRateService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class QuoteTaxRate extends QuoteTaxRateService
{
}
