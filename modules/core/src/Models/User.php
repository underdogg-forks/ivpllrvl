<?php

namespace Modules\Core\Models;

use Modules\Core\Services\UserService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class User extends UserService
{
    public $table = 'ip_users';

    public $primary_key = 'ip_users.user_id';

    public $timestamps = true;

    public $date_created_field = 'user_date_created';

    public $date_modified_field = 'user_date_modified';
}
