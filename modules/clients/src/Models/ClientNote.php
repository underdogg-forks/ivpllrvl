<?php

namespace Modules\Clients\Models;

use Modules\Clients\Services\ClientNoteService;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class ClientNote extends ClientNoteService
{
    public $table = 'ip_client_notes';

    public $primary_key = 'ip_client_notes.client_note_id';

    public $timestamps = false;
}
