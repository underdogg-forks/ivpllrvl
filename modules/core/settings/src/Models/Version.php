<?php

namespace Modules\Settings\Models;

use Modules\Settings\Services\VersionService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Version extends VersionService
{
}
