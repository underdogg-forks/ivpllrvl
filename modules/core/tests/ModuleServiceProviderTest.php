<?php

namespace Modules\Core\Tests;

use Modules\Core\Providers\ModuleResourceRegistry;
use Modules\Core\Providers\ModuleServiceProvider;
use Modules\Core\Testing\LaravelStyleTestCase;
use PHPUnit\Framework\Attributes\Test;

class ModuleServiceProviderTest extends LaravelStyleTestCase
{
    private string $modulesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulesPath = dirname(__DIR__, 3) . '/modules/';
    }

    #[Test]
    public function it_discovers_module_resource_paths_without_requiring_symlinks(): void
    {
        /* Arrange */
        $provider = new ModuleServiceProvider();
        $moduleLocations = [$this->modulesPath => '../modules/'];

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

    #[Test]
    public function it_returns_empty_array_when_module_locations_is_empty(): void
    {
        /* Arrange */
        $provider = new ModuleServiceProvider();
        $moduleLocations = [];

        /* Act */
        $viewPaths    = $provider->discoverViewPaths('clients', $moduleLocations);
        $configPaths  = $provider->discoverConfigPaths('clients', $moduleLocations);
        $routePaths   = $provider->discoverRoutePaths('clients', $moduleLocations);
        $helperPaths  = $provider->discoverHelperPaths('clients', $moduleLocations);
        $servicePaths = $provider->discoverServicePaths('clients', $moduleLocations);

        /* Assert */
        self::assertSame([], $viewPaths);
        self::assertSame([], $configPaths);
        self::assertSame([], $routePaths);
        self::assertSame([], $helperPaths);
        self::assertSame([], $servicePaths);
    }

    #[Test]
    public function it_returns_empty_array_when_module_does_not_exist_in_any_location(): void
    {
        /* Arrange */
        $provider = new ModuleServiceProvider();
        $moduleLocations = [$this->modulesPath => '../modules/'];

        /* Act */
        $viewPaths = $provider->discoverViewPaths('nonexistent_module_xyz', $moduleLocations);

        /* Assert */
        self::assertSame([], $viewPaths);
    }

    #[Test]
    public function it_discovers_helper_paths_for_module_that_has_helpers_directory(): void
    {
        /* Arrange */
        $provider = new ModuleServiceProvider();
        $moduleLocations = [$this->modulesPath => '../modules/'];

        /* Act */
        $helperPaths = $provider->discoverHelperPaths('core', $moduleLocations);

        /* Assert */
        self::assertNotEmpty($helperPaths);
        self::assertStringEndsWith('/modules/core/helpers/', $helperPaths[0]);
        self::assertDirectoryExists($helperPaths[0]);
    }

    #[Test]
    public function it_returns_empty_array_for_helper_paths_when_module_has_no_helpers_directory(): void
    {
        /* Arrange */
        $provider = new ModuleServiceProvider();
        $moduleLocations = [$this->modulesPath => '../modules/'];

        /* Act */
        $helperPaths = $provider->discoverHelperPaths('clients', $moduleLocations);

        /* Assert */
        self::assertSame([], $helperPaths);
    }

    #[Test]
    public function it_returns_empty_array_for_service_paths_when_no_module_has_services_directory(): void
    {
        /* Arrange */
        $provider = new ModuleServiceProvider();
        $moduleLocations = [$this->modulesPath => '../modules/'];

        /* Act */
        $servicePaths = $provider->discoverServicePaths('clients', $moduleLocations);

        /* Assert */
        self::assertSame([], $servicePaths);
    }

    #[Test]
    public function it_accepts_a_custom_resource_registry_via_constructor(): void
    {
        /* Arrange */
        $registry = new ModuleResourceRegistry();
        $provider = new ModuleServiceProvider($registry);
        $moduleLocations = [$this->modulesPath => '../modules/'];

        /* Act */
        $viewPaths = $provider->discoverViewPaths('clients', $moduleLocations);

        /* Assert */
        self::assertNotEmpty($viewPaths);
        self::assertStringEndsWith('/modules/clients/resources/views/', $viewPaths[0]);
    }

    #[Test]
    public function it_returns_empty_array_when_location_path_does_not_exist_on_filesystem(): void
    {
        /* Arrange */
        $provider = new ModuleServiceProvider();
        $moduleLocations = ['/tmp/nonexistent_modules_path_xyz/' => '../modules/'];

        /* Act */
        $viewPaths = $provider->discoverViewPaths('clients', $moduleLocations);

        /* Assert */
        self::assertSame([], $viewPaths);
    }

    #[Test]
    public function it_aggregates_paths_from_multiple_valid_locations(): void
    {
        /* Arrange */
        $provider = new ModuleServiceProvider();
        $moduleLocations = [
            $this->modulesPath          => '../modules/',
            $this->modulesPath . '/../' => '../../',
        ];

        /* Act */
        $viewPaths = $provider->discoverViewPaths('clients', $moduleLocations);

        /* Assert */
        self::assertGreaterThanOrEqual(1, count($viewPaths));
        self::assertStringEndsWith('/modules/clients/resources/views/', $viewPaths[0]);
    }

    #[Test]
    public function it_discovers_view_paths_for_clients_module(): void
    {
        /* Arrange */
        $provider = new ModuleServiceProvider();
        $moduleLocations = [$this->modulesPath => '../modules/'];

        /* Act */
        $viewPaths = $provider->discoverViewPaths('clients', $moduleLocations);

        /* Assert */
        self::assertNotEmpty($viewPaths);
        self::assertStringEndsWith('/modules/clients/resources/views/', $viewPaths[0]);
        self::assertDirectoryExists($viewPaths[0]);
    }
}