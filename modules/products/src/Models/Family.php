<?php

namespace Modules\Products\Models;

use Modules\Products\Services\FamilyService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Family extends FamilyService
{
    public $table = 'ip_families';

    public $primary_key = 'ip_families.family_id';

    public $timestamps = false;
}
