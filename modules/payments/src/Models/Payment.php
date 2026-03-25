<?php

namespace Modules\Payments\Models;

use Modules\Payments\Services\PaymentService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Payment extends PaymentService
{
    public $table = 'ip_payments';

    public $primary_key = 'ip_payments.payment_id';

    public $timestamps = false;
}
