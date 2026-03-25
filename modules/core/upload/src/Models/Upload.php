<?php

namespace Modules\Upload\Models;

use Modules\Upload\Services\UploadService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Upload extends UploadService
{
}
