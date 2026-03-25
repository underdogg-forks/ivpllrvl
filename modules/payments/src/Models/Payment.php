<?php

namespace Modules\Payments\Models;

use Modules\Payments\Services\PaymentService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Payment extends PaymentService
{
}
