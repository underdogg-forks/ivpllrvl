<?php

namespace Modules\Invoices\Models;

use Modules\Invoices\Services\InvoicesRecurringService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class InvoicesRecurring extends InvoicesRecurringService
{
    public $table = 'ip_invoices_recurring';

    public $primary_key = 'ip_invoices_recurring.invoice_recurring_id';

    public $timestamps = false;
}
