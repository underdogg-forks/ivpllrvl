<?php

namespace Modules\Core\Models;

use Modules\Core\Services\CustomFieldService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class CustomField extends CustomFieldService
{
    public $table = 'ip_custom_fields';

    public $primary_key = 'ip_custom_fields.custom_field_id';

    public $timestamps = false;
}
