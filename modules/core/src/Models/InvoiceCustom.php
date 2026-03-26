<?php

namespace Modules\Core\Models;

use Modules\Core\Services\InvoiceCustomService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class InvoiceCustom extends InvoiceCustomService
{
    public $table = 'ip_invoice_custom';

    public $primary_key = 'ip_invoice_custom.invoice_custom_id';

    public $timestamps = false;
}
