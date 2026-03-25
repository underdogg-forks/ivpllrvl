<?php

namespace Modules\Guest\Tests;

use Modules\Guest\Controllers\PaymentsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(PaymentsController::class)]
class PaymentsControllerTest extends TestCase
{
}
