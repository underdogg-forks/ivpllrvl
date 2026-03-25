<?php

namespace Modules\Invoices\Models;

use Modules\Invoices\Services\InvoiceService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Invoice extends InvoiceService
{
    public $table = 'ip_invoices';

    public $primary_key = 'ip_invoices.invoice_id';

    public $timestamps = false;

    public $date_modified_field = 'invoice_date_modified';
}
