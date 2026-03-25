<?php

namespace Modules\Core\Tests;

use Modules\Core\Providers\ModuleResourceRegistry;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(Modules\Core\Providers\ModuleResourceRegistry::class)]
class ModuleResourceRegistryTest extends TestCase
{
    private ModuleResourceRegistry $registry;
    private string $modulesLocation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new ModuleResourceRegistry();
        $this->modulesLocation = base_path('modules');
    }

    #[Test]
    public function it_returns_resource_candidates_for_routes(): void
    {
        // Arrange / Act
        $candidates = $this->registry->resourceCandidates('routes/');

        // Assert
        $this->assertContains('routes/', $candidates);
    }

    #[Test]
    public function it_returns_resource_candidates_for_controllers(): void
    {
        // Arrange / Act
        $candidates = $this->registry->resourceCandidates('controllers/');

        // Assert
        $this->assertContains('src/Controllers/', $candidates);
        $this->assertContains('controllers/', $candidates);
    }

    #[Test]
    public function it_returns_resource_type_as_fallback_for_unknown_type(): void
    {
        // Arrange / Act
        $candidates = $this->registry->resourceCandidates('custom_resource/');

        // Assert
        $this->assertSame(['custom_resource/'], $candidates);
    }

    #[Test]
    public function it_resolves_directory_for_existing_module_and_routes(): void
    {
        // Arrange / Act
        $path = $this->registry->resolveDirectoryForModuleAtLocation('core', 'routes/', $this->modulesLocation);

        // Assert
        $this->assertNotNull($path);
        $this->assertStringEndsWith('routes/', $path);
        $this->assertDirectoryExists($path);
    }

    #[Test]
    public function it_returns_null_for_nonexistent_module(): void
    {
        // Arrange / Act
        $path = $this->registry->resolveDirectoryForModuleAtLocation('nonexistent_module_xyz', 'routes/', $this->modulesLocation);

        // Assert
        $this->assertNull($path);
    }

    #[Test]
    public function it_resolves_relative_directory_for_module(): void
    {
        // Arrange / Act
        $relativePath = $this->registry->resolveRelativeDirectoryForModuleAtLocation('core', 'routes/', $this->modulesLocation);

        // Assert
        $this->assertNotNull($relativePath);
        $this->assertStringEndsWith('routes/', $relativePath);
        // Relative path should not contain the modules location prefix
        $this->assertStringNotContainsString($this->modulesLocation, $relativePath);
    }

    #[Test]
    public function it_returns_null_relative_directory_for_nonexistent_module(): void
    {
        // Arrange / Act
        $relativePath = $this->registry->resolveRelativeDirectoryForModuleAtLocation('nonexistent_module_xyz', 'routes/', $this->modulesLocation);

        // Assert
        $this->assertNull($relativePath);
    }
}
