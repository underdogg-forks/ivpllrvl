<?php

namespace Modules\Invoices\Models;

use Modules\Invoices\Services\InvoicesRecurringService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class InvoicesRecurring extends InvoicesRecurringService
{
}
