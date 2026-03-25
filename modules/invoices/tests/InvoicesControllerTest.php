<?php

namespace Modules\Invoices\Tests;

use Modules\Invoices\Controllers\InvoicesController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(InvoicesController::class)]
class InvoicesControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(InvoicesController::class));
    }
}
