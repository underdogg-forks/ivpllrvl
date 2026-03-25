<?php

namespace Modules\Invoices\Tests;

use Modules\Invoices\Controllers\RecurringController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(RecurringController::class)]
class RecurringControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(RecurringController::class));
    }
}
