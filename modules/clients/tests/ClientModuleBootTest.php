<?php

namespace Modules\Clients\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ClientModuleBootTest extends TestCase
{
    #[Test]
    public function it_has_the_client_module_controllers_directory(): void
    {
        // Arrange
        $controllersDirectory = dirname(__DIR__) . '/src/Controllers';

        // Act
        $directoryExists = is_dir($controllersDirectory);

        // Assert
        self::assertTrue($directoryExists);
    }
}
