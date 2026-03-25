<?php

namespace Modules\Payments\Models;

use Modules\Payments\Services\PaymentLogService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class PaymentLog extends PaymentLogService
{
}
