<?php

namespace Modules\Settings\Tests;

use Modules\Settings\Controllers\VersionsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(VersionsController::class)]
class VersionsControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(VersionsController::class));
    }
}
