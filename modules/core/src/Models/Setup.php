<?php

namespace Modules\Core\Models;

use Modules\Core\Services\SetupService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Setup extends SetupService
{
    public $table = 'ip_versions';

    public $primary_key = 'ip_versions.version_id';

    public $timestamps = false;
}
