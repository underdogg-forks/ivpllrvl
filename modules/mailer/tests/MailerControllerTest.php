<?php

namespace Modules\Mailer\Tests;

use Modules\Mailer\Controllers\MailerController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(MailerController::class)]
class MailerControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(MailerController::class));
    }
}
