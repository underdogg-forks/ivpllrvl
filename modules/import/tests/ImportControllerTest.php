<?php

namespace Modules\Import\Tests;

use Modules\Import\Controllers\ImportController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ImportController::class)]
class ImportControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(ImportController::class));
    }
}
