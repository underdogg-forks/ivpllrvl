<?php

namespace Modules\Families\Tests;

use Modules\Families\Controllers\FamiliesController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(FamiliesController::class)]
class FamiliesControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(FamiliesController::class));
    }
}
