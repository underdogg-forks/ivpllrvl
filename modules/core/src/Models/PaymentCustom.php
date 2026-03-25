<?php

namespace Modules\CustomFields\Models;

use Modules\CustomFields\Services\PaymentCustomService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class PaymentCustom extends PaymentCustomService
{
}
