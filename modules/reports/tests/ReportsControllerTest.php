<?php

namespace Modules\Reports\Tests;

use Modules\Reports\Controllers\ReportsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ReportsController::class)]
class ReportsControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(ReportsController::class));
    }
}
