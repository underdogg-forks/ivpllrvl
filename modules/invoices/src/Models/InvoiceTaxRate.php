<?php

namespace Modules\Invoices\Models;

use Modules\Invoices\Services\InvoiceTaxRateService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class InvoiceTaxRate extends InvoiceTaxRateService
{
}
