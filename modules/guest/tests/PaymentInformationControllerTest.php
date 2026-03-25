<?php

namespace Modules\Guest\Tests;

use Modules\Guest\Controllers\PaymentInformationController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(PaymentInformationController::class)]
class PaymentInformationControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(PaymentInformationController::class));
    }
}
