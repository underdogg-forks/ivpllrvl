<?php

namespace Modules\Families\Models;

use Modules\Families\Services\FamilyService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Family extends FamilyService
{
}
