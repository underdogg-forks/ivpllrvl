<?php

namespace Modules\PaymentMethods\Tests;

use Modules\PaymentMethods\Controllers\PaymentMethodsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(PaymentMethodsController::class)]
class PaymentMethodsControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(PaymentMethodsController::class));
    }
}
