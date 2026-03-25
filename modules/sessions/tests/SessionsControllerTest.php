<?php

namespace Modules\Sessions\Tests;

use Modules\Sessions\Controllers\SessionsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(SessionsController::class)]
class SessionsControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(SessionsController::class));
    }
}
