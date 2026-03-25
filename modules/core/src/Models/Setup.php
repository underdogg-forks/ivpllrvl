<?php

namespace Modules\Setup\Models;

use Modules\Setup\Services\SetupService;

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
