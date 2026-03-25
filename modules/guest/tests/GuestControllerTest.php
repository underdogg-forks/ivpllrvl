<?php

namespace Modules\Guest\Tests;

use Modules\Guest\Controllers\GuestController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(GuestController::class)]
class GuestControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(GuestController::class));
    }
}
