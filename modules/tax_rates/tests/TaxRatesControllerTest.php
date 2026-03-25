<?php

namespace Modules\TaxRates\Tests;

use Modules\TaxRates\Controllers\TaxRatesController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(TaxRatesController::class)]
class TaxRatesControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(TaxRatesController::class));
    }
}
