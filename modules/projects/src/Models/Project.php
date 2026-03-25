<?php

namespace Modules\Projects\Models;

use Modules\Projects\Services\ProjectService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Project extends ProjectService
{
    public $table = 'ip_projects';

    public $primary_key = 'ip_projects.project_id';

    public $timestamps = false;
}
