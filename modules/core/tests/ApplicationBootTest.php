<?php

namespace Modules\Core\Tests;

use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(Modules\Core\Providers\ModuleServiceProvider::class)]
class ApplicationBootTest extends TestCase
{
}
