<?php

namespace Modules\EmailTemplates\Models;

use Modules\EmailTemplates\Services\EmailTemplateService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class EmailTemplate extends EmailTemplateService
{
}
