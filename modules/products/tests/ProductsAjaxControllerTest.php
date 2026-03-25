<?php

namespace Modules\Products\Tests;

use Modules\Products\Controllers\ProductsAjaxController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ProductsAjaxController::class)]
class ProductsAjaxControllerTest extends TestCase
{
    #[Test]
    public function controller_class_is_loadable(): void
    {
        self::assertTrue(class_exists(ProductsAjaxController::class));
    }
}
