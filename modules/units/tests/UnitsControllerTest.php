<?php

namespace Modules\Units\Tests;

use Modules\Units\Controllers\UnitsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(UnitsController::class)]
class UnitsControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(UnitsController::class));
    }
}
