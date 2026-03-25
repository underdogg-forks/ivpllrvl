<?php

namespace Modules\Reports\Models;

use Modules\Reports\Services\ReportService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Report extends ReportService
{
    public $table = 'ip_clients';

    public $primary_key = 'ip_clients.client_id';

    public $timestamps = false;
}
