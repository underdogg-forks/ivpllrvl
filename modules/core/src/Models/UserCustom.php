<?php

namespace Modules\CustomFields\Models;

use Modules\CustomFields\Services\UserCustomService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class UserCustom extends UserCustomService
{
    public $table = 'ip_user_custom';

    public $primary_key = 'ip_user_custom.user_custom_id';

    public $timestamps = false;
}
