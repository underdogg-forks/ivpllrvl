<?php

namespace Modules\Core\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ApplicationBootTest extends TestCase
{
    #[Test]
    public function it_has_the_laravel_bootstrap_file(): void
    {
        // Arrange
        $bootstrapFile = dirname(__DIR__, 3) . '/bootstrap/invoiceplane.php';

        // Act
        $fileExists = file_exists($bootstrapFile);

        // Assert
        self::assertTrue($fileExists);
    }

    #[Test]
    public function it_has_the_invoiceplane_aliases_entrypoint_file(): void
    {
        // Arrange
        $entrypointFile = dirname(__DIR__, 3) . '/bootstrap/aliases.php';

        // Act
        $fileExists = file_exists($entrypointFile);

        // Assert
        self::assertTrue($fileExists);
    }

    #[Test]
    public function it_has_the_invoices_controller_dispatch_path(): void
    {
        // Arrange
        $controllersDirectory = dirname(__DIR__, 2) . '/invoices/src/Controllers';

        // Act
        $directoryExists = is_dir($controllersDirectory);

        // Assert
        self::assertTrue($directoryExists);
    }

    #[Test]
    public function it_has_the_core_module_view_path(): void
    {
        // Arrange
        $viewDirectory = dirname(__DIR__) . '/resources/views';

        // Act
        $directoryExists = is_dir($viewDirectory);

        // Assert
        self::assertTrue($directoryExists);
    }
}
