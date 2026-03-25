<?php

namespace Modules\CustomFields\Models;

use Modules\CustomFields\Services\QuoteCustomService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class QuoteCustom extends QuoteCustomService
{
}
