<?php

namespace Modules\EmailTemplates\Tests;

use Modules\EmailTemplates\Controllers\EmailTemplatesAjaxController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EmailTemplatesAjaxController::class)]
class EmailTemplatesAjaxControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(EmailTemplatesAjaxController::class));
    }
}
