<?php

namespace Modules\Core\Models;

use Modules\Core\Services\VersionService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Version extends VersionService
{
    public $table = 'ip_versions';

    public $primary_key = 'ip_versions.version_id';

    public $timestamps = false;
}
