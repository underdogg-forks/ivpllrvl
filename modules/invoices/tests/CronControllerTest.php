<?php

namespace Modules\Invoices\Tests;

use Modules\Invoices\Controllers\CronController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(CronController::class)]
class CronControllerTest extends TestCase
{
}
