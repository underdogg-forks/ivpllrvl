<?php

namespace Modules\Users\Tests;

use Modules\Users\Controllers\UsersAjaxController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(UsersAjaxController::class)]
class UsersAjaxControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(UsersAjaxController::class));
    }
}
