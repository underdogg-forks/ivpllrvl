<?php

namespace Modules\Clients\Models;

use Modules\Clients\Services\UserClientService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class UserClient extends UserClientService
{
    public $table = 'ip_user_clients';

    public $primary_key = 'ip_user_clients.user_client_id';

    public $timestamps = false;
}
