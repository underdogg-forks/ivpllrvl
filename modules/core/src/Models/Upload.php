<?php

namespace Modules\Core\Models;

use Modules\Core\Services\UploadService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Upload extends UploadService
{
    public $table = 'ip_uploads';

    public $primary_key = 'ip_uploads.upload_id';

    public $timestamps = false;

    public $date_modified_field = 'uploaded_date';
}
