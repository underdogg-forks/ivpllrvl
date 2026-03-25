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
        $moduleRoot = rtrim($location, '/') . '/' . trim($module, '/') . '/';

        foreach ($this->resourceCandidates($resourceType) as $candidate) {
            $candidatePath = $moduleRoot . $candidate;

            if (is_dir($candidatePath)) {
                return $candidatePath;
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
    public function resourceCandidates(string $resourceType): array
    {
        return $this->resourcePaths[$resourceType] ?? [$resourceType];
    }
}
