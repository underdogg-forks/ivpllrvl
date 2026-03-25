<?php

namespace Modules\Settings\Models;

use Modules\Settings\Services\SettingService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Setting extends SettingService
{
    public $table = 'ip_settings';

    public $primary_key = 'ip_settings.setting_key';

    public $timestamps = false;
}
