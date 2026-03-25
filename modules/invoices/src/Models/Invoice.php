<?php

namespace Modules\Invoices\Models;

use Modules\Invoices\Services\InvoiceService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Invoice extends InvoiceService
{
}
