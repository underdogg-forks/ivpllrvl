<?php

namespace Modules\Clients\Tests;

use Modules\Clients\Controllers\ClientsAjaxController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ClientsAjaxController::class)]
class ClientsAjaxControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(ClientsAjaxController::class));
    }
}
