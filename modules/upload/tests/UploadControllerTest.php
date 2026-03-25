<?php

namespace Modules\Upload\Tests;

use Modules\Upload\Controllers\UploadController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(UploadController::class)]
class UploadControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(UploadController::class));
    }
}
