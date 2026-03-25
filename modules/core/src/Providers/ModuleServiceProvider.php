<?php

namespace Modules\Core\Providers;

class ModuleServiceProvider
{
    public function __construct(
        private readonly ModuleResourceRegistry $resourceRegistry = new ModuleResourceRegistry()
    ) {}

    /**
     * @param array<string, string> $moduleLocations
     *
     * @return array<int, string>
     */
    public function discoverViewPaths(string $module, array $moduleLocations): array
    {
        return $this->discoverResourcePaths($module, 'views/', $moduleLocations);
    }

    /**
     * @param array<string, string> $moduleLocations
     *
     * @return array<int, string>
     */
    public function discoverConfigPaths(string $module, array $moduleLocations): array
    {
        return $this->discoverResourcePaths($module, 'config/', $moduleLocations);
    }

    /**
     * @param array<string, string> $moduleLocations
     *
     * @return array<int, string>
     */
    public function discoverRoutePaths(string $module, array $moduleLocations): array
    {
        return $this->discoverResourcePaths($module, 'routes/', $moduleLocations);
    }

    /**
     * @param array<string, string> $moduleLocations
     *
     * @return array<int, string>
     */
    public function discoverHelperPaths(string $module, array $moduleLocations): array
    {
        return $this->discoverResourcePaths($module, 'helpers/', $moduleLocations);
    }

    /**
     * @param array<string, string> $moduleLocations
     *
     * @return array<int, string>
     */
    public function discoverServicePaths(string $module, array $moduleLocations): array
    {
        return $this->discoverResourcePaths($module, 'services/', $moduleLocations);
    }

    /**
     * @param array<string, string> $moduleLocations
     *
     * @return array<int, string>
     */
    private function discoverResourcePaths(string $module, string $resourceType, array $moduleLocations): array
    {
        $paths = [];

        foreach (array_keys($moduleLocations) as $location) {
            $resourcePath = $this->resourceRegistry->resolveDirectoryForModuleAtLocation($module, $resourceType, $location);

            if ($resourcePath === null) {
                continue;
            }

            $paths[] = $resourcePath;
        }

        return $paths;
    }
}
