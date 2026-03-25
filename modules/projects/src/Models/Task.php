<?php

namespace Modules\Tasks\Models;

use Modules\Tasks\Services\TaskService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Task extends TaskService
{
}
