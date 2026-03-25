<?php

namespace Modules\Users\Tests;

use Modules\Users\Controllers\UsersController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(UsersController::class)]
class UsersControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(UsersController::class));
    }
}
