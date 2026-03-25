<?php

namespace Modules\Clients\Tests;

use Modules\Core\Providers\ModuleResourceRegistry;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(Modules\Core\Providers\ModuleResourceRegistry::class)]
class ClientModuleBootTest extends TestCase
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
