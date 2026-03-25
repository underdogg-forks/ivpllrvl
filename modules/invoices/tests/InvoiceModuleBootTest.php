<?php

namespace Modules\Invoices\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class InvoiceModuleBootTest extends TestCase
{
    #[Test]
    public function it_has_the_invoice_module_controllers_directory(): void
    {
        // Arrange
        $controllersDirectory = dirname(__DIR__) . '/src/Controllers';

        // Act
        $directoryExists = is_dir($controllersDirectory);

        // Assert
        self::assertTrue($directoryExists);
    }
}
