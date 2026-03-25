<?php

namespace Modules\CustomValues\Models;

use Modules\CustomValues\Services\CustomValueService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class CustomValue extends CustomValueService
{
}
