<?php

namespace Modules\Invoices\Models;

use Modules\Invoices\Services\InvoiceSumexService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class InvoiceSumex extends InvoiceSumexService
{
}
