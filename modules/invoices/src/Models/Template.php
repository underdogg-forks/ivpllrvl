<?php

namespace Modules\Invoices\Models;

use Modules\Invoices\Services\TemplateService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Template extends TemplateService
{
    public $table = 'ip_invoices';

    public $primary_key = 'ip_invoices.invoice_id';

    public $timestamps = false;
}
