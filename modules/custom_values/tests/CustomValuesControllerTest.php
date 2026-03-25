<?php

namespace Modules\CustomValues\Tests;

use Modules\CustomValues\Controllers\CustomValuesController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(CustomValuesController::class)]
class CustomValuesControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(CustomValuesController::class));
    }
}
