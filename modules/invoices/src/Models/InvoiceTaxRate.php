<?php

namespace Modules\Invoices\Models;

use Modules\Invoices\Services\InvoiceTaxRateService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class InvoiceTaxRate extends InvoiceTaxRateService
{
    public $table = 'ip_invoice_tax_rates';

    public $primary_key = 'ip_invoice_tax_rates.invoice_tax_rate_id';

    public $timestamps = false;
}
