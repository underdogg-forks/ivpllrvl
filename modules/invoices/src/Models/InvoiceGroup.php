<?php

namespace Modules\InvoiceGroups\Models;

use Modules\InvoiceGroups\Services\InvoiceGroupService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class InvoiceGroup extends InvoiceGroupService
{
}
