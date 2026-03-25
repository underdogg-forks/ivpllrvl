<?php

namespace Modules\Core\Tests;

use Modules\Core\Providers\ModuleServiceProvider;
use Modules\Core\Testing\LaravelStyleTestCase;
use PHPUnit\Framework\Attributes\Test;

class ModuleServiceProviderTest extends LaravelStyleTestCase
{
    #[Test]
    public function it_discovers_module_resource_paths_without_requiring_symlinks(): void
    {
        /* Arrange */
        $provider = new ModuleServiceProvider();
        $moduleLocations = [dirname(__DIR__, 3) . '/modules/' => '../modules/'];

        /* Act */
        $viewPaths = $provider->discoverViewPaths('invoices', $moduleLocations);
        $configPaths = $provider->discoverConfigPaths('core', $moduleLocations);
        $routePaths = $provider->discoverRoutePaths('core', $moduleLocations);

        /* Assert */
        self::assertNotEmpty($viewPaths);
        self::assertNotEmpty($configPaths);
        self::assertNotEmpty($routePaths);
        self::assertStringEndsWith('/modules/invoices/resources/views/', $viewPaths[0]);
        self::assertStringEndsWith('/modules/core/config/', $configPaths[0]);
        self::assertStringEndsWith('/modules/core/routes/', $routePaths[0]);
    }
}
