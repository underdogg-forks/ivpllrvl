<?php

namespace Modules\Users\Models;

use Modules\Users\Services\UserService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class User extends UserService
{
}
