<?php

namespace Modules\Products\Tests;

use Modules\Products\Controllers\ProductsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(ProductsController::class)]
class ProductsControllerTest extends TestCase
{
}
