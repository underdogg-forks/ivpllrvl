<?php

namespace Modules\Payments\Tests;

use Modules\Payments\Controllers\PaymentsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(PaymentsController::class)]
class PaymentsControllerTest extends TestCase
{
}
