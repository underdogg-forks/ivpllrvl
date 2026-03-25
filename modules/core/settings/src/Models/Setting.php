<?php

namespace Modules\Settings\Models;

use Modules\Settings\Services\SettingService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Setting extends SettingService
{
}
