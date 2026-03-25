<?php

namespace Modules\UserClients\Tests;

use Modules\UserClients\Controllers\UserClientsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(UserClientsController::class)]
class UserClientsControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(UserClientsController::class));
    }
}
