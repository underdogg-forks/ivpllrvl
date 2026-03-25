<?php

namespace Modules\Settings\Tests;

use Modules\Settings\Controllers\SettingsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(SettingsController::class)]
class SettingsControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(SettingsController::class));
    }
}
