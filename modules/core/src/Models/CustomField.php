<?php

namespace Modules\CustomFields\Models;

use Modules\CustomFields\Services\CustomFieldService;

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
