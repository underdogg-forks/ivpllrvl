<?php

namespace Modules\Clients\Models;

use Modules\Clients\Services\ClientService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Client extends ClientService
{
    public $table = 'ip_clients';

    public $primary_key = 'ip_clients.client_id';

    public $timestamps = true;

    public $date_created_field = 'client_date_created';

    public $date_modified_field = 'client_date_modified';
}
