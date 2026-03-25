<?php

namespace Modules\CustomFields\Tests;

use Modules\CustomFields\Controllers\CustomFieldsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(CustomFieldsController::class)]
class CustomFieldsControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(CustomFieldsController::class));
    }
}
