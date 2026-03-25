<?php

namespace Modules\CustomFields\Models;

use Modules\CustomFields\Services\UserCustomService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class UserCustom extends UserCustomService
{
}
