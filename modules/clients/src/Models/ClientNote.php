<?php

namespace Modules\Clients\Models;

use Modules\Clients\Services\ClientNoteService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class ClientNote extends ClientNoteService
{
}
