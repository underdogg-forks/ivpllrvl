<?php

namespace Modules\Core\Models;

use Modules\Core\Services\PaymentCustomService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class PaymentCustom extends PaymentCustomService
{
    public $table = 'ip_payment_custom';

    public $primary_key = 'ip_payment_custom.payment_custom_id';

    public $timestamps = false;
}
