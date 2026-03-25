<?php

namespace Modules\Payments\Tests;

use Modules\Payments\Controllers\PaymentsAjaxController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(PaymentsAjaxController::class)]
class PaymentsAjaxControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(PaymentsAjaxController::class));
    }
}
