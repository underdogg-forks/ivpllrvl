<?php

namespace Modules\EmailTemplates\Tests;

use Modules\EmailTemplates\Controllers\EmailTemplatesController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EmailTemplatesController::class)]
class EmailTemplatesControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(EmailTemplatesController::class));
    }
}
