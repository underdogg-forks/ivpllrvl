<?php

namespace Modules\Invoices\Models;

use Modules\Invoices\Services\TemplateService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Template extends TemplateService
{
}
