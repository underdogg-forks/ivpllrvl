<?php

namespace Modules\Invoices\Models;

use Modules\Invoices\Services\InvoiceAmountService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class InvoiceAmount extends InvoiceAmountService
{
    public $table = 'ip_invoice_amounts';

    public $primary_key = 'ip_invoice_amounts.invoice_amount_id';

    public $timestamps = false;
}
