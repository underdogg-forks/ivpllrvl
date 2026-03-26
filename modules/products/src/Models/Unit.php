<?php

namespace Modules\Products\Models;

use Modules\Products\Services\UnitService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Unit extends UnitService
{
    public $table = 'ip_units';

    public $primary_key = 'ip_units.unit_id';

    public $timestamps = false;
}
