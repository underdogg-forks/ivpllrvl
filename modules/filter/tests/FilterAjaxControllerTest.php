<?php

namespace Modules\Filter\Tests;

use Modules\Filter\Controllers\FilterAjaxController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(FilterAjaxController::class)]
class FilterAjaxControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(FilterAjaxController::class));
    }
}
