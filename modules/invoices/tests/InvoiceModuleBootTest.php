<?php

namespace Modules\Invoices\Tests;

use PHPUnit\Framework\TestCase;

class InvoiceModuleBootTest extends TestCase
{
    public function test_invoice_module_loads(): void
    {
        self::assertDirectoryExists(dirname(__DIR__) . '/src/Controllers');
    }
}
