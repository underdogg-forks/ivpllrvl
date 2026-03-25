<?php

namespace Modules\Core\Testing;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected mixed $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = require base_path('bootstrap/app.php');
    }

    /**
     * @return array<int, string>
     */
    protected function discoverPublicControllerActions(string $controllerClass): array
    {
        $reflection = new \ReflectionClass($controllerClass);
        $actions = [];

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $controllerClass) {
                continue;
            }

            if ($method->isConstructor() || str_starts_with($method->getName(), '_')) {
                continue;
            }

            $actions[] = $method->getName();
        }

        sort($actions);

        return $actions;
    }

    /**
     * @return array{verb: string, uri: string}
     */
    protected function guessRouteForControllerAction(string $controllerClass, string $action): array
    {
        $reflection = new \ReflectionClass($controllerClass);
        $module = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', explode('\\', $reflection->getNamespaceName())[1] ?? 'core'));
        $controller = strtolower(str_replace('Controller', '', $reflection->getShortName()));

        $verb = 'GET';

        if (str_contains($reflection->getShortName(), 'AjaxController')) {
            $verb = 'POST';
        } elseif ($action === 'form') {
            $verb = 'GET';
        } elseif (preg_match('/^(save|store|create|update|delete|remove|insert)/i', $action) === 1) {
            $verb = 'POST';
        }

        return [
            'verb' => $verb,
            'uri' => $module . '/' . $controller . '/' . $action,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function extractRequiredFieldsFromServiceFile(string $serviceFile): array
    {
        $contents = (string) file_get_contents($serviceFile);
        $required = [];

        if (preg_match_all("/'field'\\s*=>\\s*'([^']+)'[\\s\\S]*?'rules'\\s*=>\\s*'[^']*required[^']*'/m", $contents, $matches) !== false) {
            $required = $matches[1];
        }

        return array_values(array_unique($required));
    }

    protected function validateRequiredFields(array $payload, array $requiredFields): bool
    {
        foreach ($requiredFields as $field) {
            if (!isset($payload[$field]) || $payload[$field] === '') {
                return false;
            }
        }

        return true;
    }

}


if (! function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 4);

        return $path === '' ? $basePath : $basePath . '/' . ltrim($path, '/');
    }
}

if (! function_exists('trans')) {
    function trans(string $key): string
    {
        return $key;
    }
}
