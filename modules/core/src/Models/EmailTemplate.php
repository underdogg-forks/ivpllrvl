<?php

namespace Modules\Core\Models;

use Modules\Core\Services\EmailTemplateService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class EmailTemplate extends EmailTemplateService
{
    public $table = 'ip_email_templates';

    public $primary_key = 'ip_email_templates.email_template_id';

    public $timestamps = false;
}
