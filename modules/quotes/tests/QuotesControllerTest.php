<?php

namespace Modules\Quotes\Tests;

use Modules\Quotes\Controllers\QuotesController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(QuotesController::class)]
class QuotesControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(QuotesController::class));
    }
}
