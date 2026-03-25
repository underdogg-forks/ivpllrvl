<?php

namespace Modules\Reports\Models;

use Modules\Reports\Services\ReportService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Report extends ReportService
{
}
