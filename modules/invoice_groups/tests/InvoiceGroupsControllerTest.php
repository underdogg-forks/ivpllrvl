<?php

namespace Modules\InvoiceGroups\Tests;

use Modules\InvoiceGroups\Controllers\InvoiceGroupsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(InvoiceGroupsController::class)]
class InvoiceGroupsControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(InvoiceGroupsController::class));
    }
}
