<?php

namespace Modules\Welcome\Tests;

use Modules\Welcome\Controllers\WelcomeController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(WelcomeController::class)]
class WelcomeControllerTest extends TestCase
{
}
