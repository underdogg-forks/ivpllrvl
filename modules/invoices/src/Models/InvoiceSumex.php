<?php

namespace Modules\Invoices\Models;

use Modules\Invoices\Services\InvoiceSumexService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class InvoiceSumex extends InvoiceSumexService
{
    public $table = 'ip_invoice_sumex';

    public $primary_key = 'ip_invoice_sumex.sumex_id';

    public $timestamps = false;
}
