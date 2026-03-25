<?php

namespace Modules\UserClients\Models;

use Modules\UserClients\Services\UserClientService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class UserClient extends UserClientService
{
}
