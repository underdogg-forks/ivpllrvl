<?php

namespace Modules\Core\Models;

use Modules\Core\Services\CustomValueService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class CustomValue extends CustomValueService
{
    public $table = 'ip_custom_values';

    public $primary_key = 'ip_custom_values.custom_values_id';

    public $timestamps = false;
}
