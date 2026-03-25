<?php

namespace Modules\Payments\Models;

use Modules\Payments\Services\PaymentLogService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class PaymentLog extends PaymentLogService
{
    public $table = 'ip_merchant_responses';

    public $primary_key = 'ip_merchant_responses.merchant_response_id';

    public $timestamps = false;
}
