<?php

namespace Modules\Core\Models;

use Modules\Core\Services\ClientCustomService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class ClientCustom extends ClientCustomService
{
    public $table = 'ip_client_custom';

    public $primary_key = 'ip_client_custom.client_custom_id';

    public $timestamps = false;
}
