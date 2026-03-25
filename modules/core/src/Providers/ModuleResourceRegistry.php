<?php

namespace Modules\Core\Providers;

class ModuleResourceRegistry
{
    /**
     * @var array<string, string[]>
     */
    private array $resourcePaths = [
        'controllers/' => ['src/Controllers/', 'controllers/'],
        'models/' => ['src/Models/', 'models/'],
        'views/' => ['resources/views/', 'views/'],
        'libraries/' => ['src/Libraries/', 'libraries/'],
        'config/' => ['config/'],
        'helpers/' => ['helpers/'],
        'routes/' => ['routes/'],
        'services/' => ['src/Services/', 'services/'],
    ];

    public function resolveDirectoryForModuleAtLocation(string $module, string $resourceType, string $location): ?string
    {
        foreach ($this->moduleRoots($module, $location) as $moduleRoot) {
            foreach ($this->resourceCandidates($resourceType) as $candidate) {
                $candidatePath = $moduleRoot . $candidate;

                if (is_dir($candidatePath)) {
                    return $candidatePath;
                }
            }
        }

        return null;
    }

    public function resolveRelativeDirectoryForModuleAtLocation(string $module, string $resourceType, string $location): ?string
    {
        $moduleDirectory = $this->resolveDirectoryForModuleAtLocation($module, $resourceType, $location);

        if ($moduleDirectory === null) {
            return null;
        }

        return str_replace(
            rtrim($location, '/') . '/' . trim($module, '/') . '/',
            '',
            $moduleDirectory
        );
    }


    /**
     * @return string[]
     */
    private function moduleRoots(string $module, string $location): array
    {
        $location = rtrim($location, '/');
        $module = trim($module, '/');

        $roots = [
            $location . '/' . $module . '/',
        ];

        $parent = $this->moduleParentMap()[$module] ?? null;

        if (is_string($parent) && $parent !== '') {
            $roots[] = $location . '/' . trim($parent, '/') . '/';

            if ($parent !== $module) {
                $roots[] = $location . '/' . trim($parent, '/') . '/' . $module . '/';
            }
        }

        return array_values(array_unique($roots));
    }

    /**
     * @return array<string, string>
     */
    private function moduleParentMap(): array
    {
        $map = config('app_modules.map', []);

        return is_array($map) ? $map : [];
    }

    /**
     * @return string[]
     */
    public function resourceCandidates(string $resourceType): array
    {
        return $this->resourcePaths[$resourceType] ?? [$resourceType];
    }
}
