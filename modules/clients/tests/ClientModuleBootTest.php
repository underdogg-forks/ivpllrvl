<?php

namespace Modules\Clients\Tests;

use PHPUnit\Framework\TestCase;

class ClientModuleBootTest extends TestCase
{
    public function test_client_module_loads(): void
    {
        self::assertDirectoryExists(dirname(__DIR__) . '/src/Controllers');
    }
}
