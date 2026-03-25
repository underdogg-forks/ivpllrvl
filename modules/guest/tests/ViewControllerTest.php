<?php

namespace Modules\Guest\Tests;

use Modules\Guest\Controllers\ViewController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ViewController::class)]
class ViewControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(ViewController::class));
    }
}
