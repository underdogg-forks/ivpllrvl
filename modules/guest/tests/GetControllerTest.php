<?php

namespace Modules\Guest\Tests;

use Modules\Guest\Controllers\GetController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(GetController::class)]
class GetControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(GetController::class));
    }
}
