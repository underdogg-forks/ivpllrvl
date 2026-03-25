<?php

namespace Modules\Invoices\Models;

use Modules\Invoices\Services\InvoiceAmountService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class InvoiceAmount extends InvoiceAmountService
{
}
