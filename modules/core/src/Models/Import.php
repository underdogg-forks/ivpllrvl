<?php

namespace Modules\Import\Models;

use Modules\Import\Services\ImportService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Import extends ImportService
{
    public $table = 'ip_imports';

    public $primary_key = 'ip_imports.import_id';

    public $timestamps = false;
}
