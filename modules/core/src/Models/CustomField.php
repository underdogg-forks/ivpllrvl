<?php

namespace Modules\CustomFields\Models;

use Modules\CustomFields\Services\CustomFieldService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class CustomField extends CustomFieldService
{
}
