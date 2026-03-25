<?php

namespace Modules\Layout\Tests;

use Modules\Layout\Controllers\LayoutController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(LayoutController::class)]
class LayoutControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(LayoutController::class));
    }
}
