<?php

namespace Modules\Core\Tests;

use Modules\Core\Providers\ModuleResourceRegistry;
use Modules\Core\Testing\LaravelStyleTestCase;
use PHPUnit\Framework\Attributes\Test;

class ModuleResourceRegistryTest extends LaravelStyleTestCase
{
    private ModuleResourceRegistry $registry;
    private string $modulesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry    = new ModuleResourceRegistry();
        $this->modulesPath = dirname(__DIR__, 3) . '/modules/';
    }

    #[Test]
    public function it_returns_correct_candidates_for_controllers_resource_type(): void
    {
        /* Act */
        $candidates = $this->registry->resourceCandidates('controllers/');

        /* Assert */
        self::assertIsArray($candidates);
        self::assertContains('src/Controllers/', $candidates);
        self::assertContains('controllers/', $candidates);
    }

    #[Test]
    public function it_returns_modern_style_directory_first_for_controllers(): void
    {
        /* Act */
        $candidates = $this->registry->resourceCandidates('controllers/');

        /* Assert */
        self::assertSame('src/Controllers/', $candidates[0]);
    }

    #[Test]
    public function it_returns_correct_candidates_for_views_resource_type(): void
    {
        /* Act */
        $candidates = $this->registry->resourceCandidates('views/');

        /* Assert */
        self::assertIsArray($candidates);
        self::assertContains('resources/views/', $candidates);
        self::assertContains('views/', $candidates);
    }

    #[Test]
    public function it_returns_correct_candidates_for_models_resource_type(): void
    {
        /* Act */
        $candidates = $this->registry->resourceCandidates('models/');

        /* Assert */
        self::assertIsArray($candidates);
        self::assertContains('src/Models/', $candidates);
        self::assertContains('models/', $candidates);
    }

    #[Test]
    public function it_returns_correct_candidates_for_libraries_resource_type(): void
    {
        /* Act */
        $candidates = $this->registry->resourceCandidates('libraries/');

        /* Assert */
        self::assertIsArray($candidates);
        self::assertContains('src/Libraries/', $candidates);
        self::assertContains('libraries/', $candidates);
    }

    #[Test]
    public function it_returns_correct_candidates_for_config_resource_type(): void
    {
        /* Act */
        $candidates = $this->registry->resourceCandidates('config/');

        /* Assert */
        self::assertIsArray($candidates);
        self::assertContains('config/', $candidates);
    }

    #[Test]
    public function it_returns_resource_type_itself_as_fallback_for_unknown_type(): void
    {
        /* Act */
        $candidates = $this->registry->resourceCandidates('custom-thing/');

        /* Assert */
        self::assertIsArray($candidates);
        self::assertCount(1, $candidates);
        self::assertSame('custom-thing/', $candidates[0]);
    }

    #[Test]
    public function it_returns_resource_type_itself_as_fallback_for_completely_arbitrary_type(): void
    {
        /* Act */
        $candidates = $this->registry->resourceCandidates('legacy-controllers/');

        /* Assert */
        self::assertCount(1, $candidates);
        self::assertSame('legacy-controllers/', $candidates[0]);
    }

    #[Test]
    public function it_resolves_controllers_directory_for_module_with_modern_layout(): void
    {
        /* Act */
        $path = $this->registry->resolveDirectoryForModuleAtLocation('clients', 'controllers/', $this->modulesPath);

        /* Assert */
        self::assertIsString($path);
        self::assertStringEndsWith('/modules/clients/src/Controllers/', $path);
        self::assertDirectoryExists($path);
    }

    #[Test]
    public function it_resolves_views_directory_for_module_with_modern_layout(): void
    {
        /* Act */
        $path = $this->registry->resolveDirectoryForModuleAtLocation('clients', 'views/', $this->modulesPath);

        /* Assert */
        self::assertIsString($path);
        self::assertStringEndsWith('/modules/clients/resources/views/', $path);
        self::assertDirectoryExists($path);
    }

    #[Test]
    public function it_resolves_libraries_directory_for_module_with_modern_layout(): void
    {
        /* Act */
        $path = $this->registry->resolveDirectoryForModuleAtLocation('core', 'libraries/', $this->modulesPath);

        /* Assert */
        self::assertIsString($path);
        self::assertStringEndsWith('/modules/core/src/Libraries/', $path);
        self::assertDirectoryExists($path);
    }

    #[Test]
    public function it_resolves_models_directory_for_module_with_modern_layout(): void
    {
        /* Act */
        $path = $this->registry->resolveDirectoryForModuleAtLocation('clients', 'models/', $this->modulesPath);

        /* Assert */
        self::assertIsString($path);
        self::assertStringEndsWith('/modules/clients/src/Models/', $path);
        self::assertDirectoryExists($path);
    }

    #[Test]
    public function it_returns_null_for_non_existent_module(): void
    {
        /* Act */
        $path = $this->registry->resolveDirectoryForModuleAtLocation('no_such_module_xyz', 'controllers/', $this->modulesPath);

        /* Assert */
        self::assertNull($path);
    }

    #[Test]
    public function it_returns_null_for_unknown_resource_type_with_no_matching_directory(): void
    {
        /* Act */
        $path = $this->registry->resolveDirectoryForModuleAtLocation('clients', 'legacy-controllers/', $this->modulesPath);

        /* Assert */
        self::assertNull($path);
    }

    #[Test]
    public function it_returns_null_when_module_has_no_services_directory(): void
    {
        /* Act */
        $path = $this->registry->resolveDirectoryForModuleAtLocation('clients', 'services/', $this->modulesPath);

        /* Assert */
        self::assertNull($path);
    }

    #[Test]
    public function it_handles_location_without_trailing_slash(): void
    {
        /* Arrange */
        $locationWithoutTrailingSlash = rtrim($this->modulesPath, '/');

        /* Act */
        $path = $this->registry->resolveDirectoryForModuleAtLocation('clients', 'controllers/', $locationWithoutTrailingSlash);

        /* Assert */
        self::assertIsString($path);
        self::assertStringEndsWith('/modules/clients/src/Controllers/', $path);
    }

    #[Test]
    public function it_handles_module_name_with_leading_slash(): void
    {
        /* Act */
        $path = $this->registry->resolveDirectoryForModuleAtLocation('/clients', 'controllers/', $this->modulesPath);

        /* Assert */
        self::assertIsString($path);
        self::assertStringEndsWith('/modules/clients/src/Controllers/', $path);
    }

    #[Test]
    public function it_handles_module_name_with_trailing_slash(): void
    {
        /* Act */
        $path = $this->registry->resolveDirectoryForModuleAtLocation('clients/', 'controllers/', $this->modulesPath);

        /* Assert */
        self::assertIsString($path);
        self::assertStringEndsWith('/modules/clients/src/Controllers/', $path);
    }

    #[Test]
    public function it_resolves_relative_controllers_directory_for_clients_module(): void
    {
        /* Act */
        $relativePath = $this->registry->resolveRelativeDirectoryForModuleAtLocation('clients', 'controllers/', $this->modulesPath);

        /* Assert */
        self::assertIsString($relativePath);
        self::assertSame('src/Controllers/', $relativePath);
    }

    #[Test]
    public function it_resolves_relative_views_directory_for_clients_module(): void
    {
        /* Act */
        $relativePath = $this->registry->resolveRelativeDirectoryForModuleAtLocation('clients', 'views/', $this->modulesPath);

        /* Assert */
        self::assertIsString($relativePath);
        self::assertSame('resources/views/', $relativePath);
    }

    #[Test]
    public function it_resolves_relative_libraries_directory_for_core_module(): void
    {
        /* Act */
        $relativePath = $this->registry->resolveRelativeDirectoryForModuleAtLocation('core', 'libraries/', $this->modulesPath);

        /* Assert */
        self::assertIsString($relativePath);
        self::assertSame('src/Libraries/', $relativePath);
    }

    #[Test]
    public function it_returns_null_from_resolve_relative_when_directory_does_not_exist(): void
    {
        /* Act */
        $relativePath = $this->registry->resolveRelativeDirectoryForModuleAtLocation('clients', 'legacy-views/', $this->modulesPath);

        /* Assert */
        self::assertNull($relativePath);
    }

    #[Test]
    public function it_returns_null_from_resolve_relative_for_non_existent_module(): void
    {
        /* Act */
        $relativePath = $this->registry->resolveRelativeDirectoryForModuleAtLocation('nonexistent_module_xyz', 'controllers/', $this->modulesPath);

        /* Assert */
        self::assertNull($relativePath);
    }

    #[Test]
    public function it_resolves_relative_path_correctly_when_location_lacks_trailing_slash(): void
    {
        /* Arrange */
        $locationWithoutTrailingSlash = rtrim($this->modulesPath, '/');

        /* Act */
        $relativePath = $this->registry->resolveRelativeDirectoryForModuleAtLocation('clients', 'controllers/', $locationWithoutTrailingSlash);

        /* Assert */
        self::assertIsString($relativePath);
        self::assertSame('src/Controllers/', $relativePath);
    }

    #[Test]
    public function it_resolves_config_directory_for_core_module(): void
    {
        /* Act */
        $path = $this->registry->resolveDirectoryForModuleAtLocation('core', 'config/', $this->modulesPath);

        /* Assert */
        self::assertIsString($path);
        self::assertStringEndsWith('/modules/core/config/', $path);
        self::assertDirectoryExists($path);
    }

    #[Test]
    public function it_resolves_routes_directory_for_core_module(): void
    {
        /* Act */
        $path = $this->registry->resolveDirectoryForModuleAtLocation('core', 'routes/', $this->modulesPath);

        /* Assert */
        self::assertIsString($path);
        self::assertStringEndsWith('/modules/core/routes/', $path);
        self::assertDirectoryExists($path);
    }
}