<?php

namespace Modules\Guest\Tests;

use Modules\Guest\Controllers\InvoicesController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(InvoicesController::class)]
class InvoicesControllerTest extends TestCase
{
}
