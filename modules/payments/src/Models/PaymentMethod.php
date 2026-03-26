<?php

namespace Modules\Payments\Models;

use Modules\Payments\Services\PaymentMethodService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class PaymentMethod extends PaymentMethodService
{
    public $table = 'ip_payment_methods';

    public $primary_key = 'ip_payment_methods.payment_method_id';

    public $timestamps = false;
}
