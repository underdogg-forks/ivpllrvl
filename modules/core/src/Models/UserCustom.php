<?php

namespace Modules\Core\Models;

use Modules\Core\Services\UserCustomService;

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
