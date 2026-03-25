<?php

namespace Modules\CustomValues\Models;

use Modules\CustomValues\Services\CustomValueService;

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
