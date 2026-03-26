<?php

namespace Modules\Projects\Models;

use Modules\Projects\Services\TaskService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Task extends TaskService
{
    public $table = 'ip_tasks';

    public $primary_key = 'ip_tasks.task_id';

    public $timestamps = false;
}
