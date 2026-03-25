<?php

namespace Modules\Dashboard\Tests;

use Modules\Dashboard\Controllers\DashboardController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(DashboardController::class)]
class DashboardControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(DashboardController::class));
    }
}
