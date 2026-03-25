<?php

namespace Modules\Welcome\Tests;

use Modules\Welcome\Controllers\WelcomeController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(WelcomeController::class)]
class WelcomeControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(WelcomeController::class));
    }
}
