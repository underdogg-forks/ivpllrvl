<?php

namespace Modules\Units\Models;

use Modules\Units\Services\UnitService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Unit extends UnitService
{
}
