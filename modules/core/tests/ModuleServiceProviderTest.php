<?php

namespace Modules\Core\Tests;

use Modules\Core\Providers\ModuleResourceRegistry;
use Modules\Core\Providers\ModuleServiceProvider;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(Modules\Core\Providers\ModuleServiceProvider::class)]
class ModuleServiceProviderTest extends TestCase
{
    private ModuleServiceProvider $provider;
    private string $modulesLocation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new ModuleServiceProvider();
        $this->modulesLocation = base_path('modules');
    }

    // #region Route Discovery Tests

    #[Test]
    public function it_discovers_route_paths_for_existing_module(): void
    {
        /* Arrange */
        /* Act */
        $paths = $this->provider->discoverRoutePaths('core', [$this->modulesLocation => 'modules']);

        /* Assert */
        $this->assertNotEmpty($paths);
        $this->assertStringEndsWith('routes/', $paths[0]);
    }

    #[Test]
    public function it_returns_empty_route_paths_for_unknown_module(): void
    {
        /* Arrange */
        /* Act */
        $paths = $this->provider->discoverRoutePaths('nonexistent_module_xyz', [$this->modulesLocation => 'modules']);

        /* Assert */
        $this->assertSame([], $paths);
    }

    #[Test]
    public function it_discovers_route_files_for_existing_module(): void
    {
        /* Arrange */
        /* Act */
        $files = $this->provider->discoverRouteFiles('core', [$this->modulesLocation => 'modules']);

        /* Assert */
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $this->assertStringEndsWith('.php', $file);
            $this->assertFileExists($file);
        }
    }

    #[Test]
    public function it_returns_empty_route_files_for_unknown_module(): void
    {
        /* Arrange */
        /* Act */
        $files = $this->provider->discoverRouteFiles('nonexistent_module_xyz', [$this->modulesLocation => 'modules']);

        /* Assert */
        $this->assertSame([], $files);
    }

    // #endregion

    // #region View Discovery Tests

    #[Test]
    public function it_discovers_view_paths_for_existing_module(): void
    {
        /* Arrange */
        /* Act */
        $paths = $this->provider->discoverViewPaths('quotes', [$this->modulesLocation => 'modules']);

        /* Assert */
        $this->assertNotEmpty($paths);
    }

    #[Test]
    public function it_returns_empty_view_paths_for_unknown_module(): void
    {
        /* Arrange */
        /* Act */
        $paths = $this->provider->discoverViewPaths('nonexistent_module_xyz', [$this->modulesLocation => 'modules']);

        /* Assert */
        $this->assertSame([], $paths);
    }

    // #endregion
}
