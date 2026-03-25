<?php

namespace Modules\Clients\Tests;

use Modules\Core\Providers\ModuleResourceRegistry;
use Modules\Core\Testing\LaravelStyleTestCase;
use PHPUnit\Framework\Attributes\Test;

class ClientModuleBootTest extends LaravelStyleTestCase
{
    #[Test]
    public function it_resolves_client_controllers_from_the_real_module_layout(): void
    {
        /* Arrange */
        $resourceRegistry = new ModuleResourceRegistry();
        $modulesPath = dirname(__DIR__, 3) . '/modules/';

        /* Act */
        $controllersDirectory = $resourceRegistry->resolveDirectoryForModuleAtLocation('clients', 'controllers/', $modulesPath);
        $legacyControllersDirectory = $resourceRegistry->resolveDirectoryForModuleAtLocation('clients', 'legacy-controllers/', $modulesPath);

        /* Assert */
        self::assertIsString($controllersDirectory);
        self::assertStringEndsWith('/modules/clients/src/Controllers/', $controllersDirectory);
        self::assertDirectoryExists($controllersDirectory);
        self::assertNull($legacyControllersDirectory);
    }
}
