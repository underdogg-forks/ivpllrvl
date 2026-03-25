<?php

namespace Modules\InvoiceGroups\Models;

use Modules\InvoiceGroups\Services\InvoiceGroupService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class InvoiceGroup extends InvoiceGroupService
{
    public $table = 'ip_invoice_groups';

    public $primary_key = 'ip_invoice_groups.invoice_group_id';

    public $timestamps = false;
}
