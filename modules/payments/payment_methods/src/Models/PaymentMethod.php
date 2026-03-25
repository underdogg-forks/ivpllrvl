<?php

namespace Modules\PaymentMethods\Models;

use Modules\PaymentMethods\Services\PaymentMethodService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class PaymentMethod extends PaymentMethodService
{
}
