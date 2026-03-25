<?php

namespace Modules\Import\Models;

use Modules\Import\Services\ImportService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Import extends ImportService
{
}
