<?php

namespace Modules\Users\Tests;

use Modules\Users\Controllers\UsersController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(UsersController::class)]
class UsersControllerTest extends TestCase
{
}
