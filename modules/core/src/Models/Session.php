<?php

namespace Modules\Core\Models;

use Modules\Core\Services\SessionService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Session extends SessionService
{
    public $table = 'ip_users';

    public $primary_key = 'ip_users.user_id';

    public $timestamps = false;
}
