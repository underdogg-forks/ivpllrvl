<?php

namespace Modules\Setup\Tests;

use Modules\Setup\Controllers\SetupController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(SetupController::class)]
class SetupControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(SetupController::class));
    }
}
