<?php

namespace Modules\Clients\Models;

use Modules\Clients\Services\ClientService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Client extends ClientService
{
}
