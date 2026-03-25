#!/usr/bin/env php
<?php

/**
 * Comprehensive Route Test Generator
 * 
 * This script generates thorough tests for all routes in the InvoicePlane application.
 * Each route gets both happy path and failure path tests.
 */

$modulesPath = __DIR__ . '/../modules';
$modules = ['core', 'clients', 'invoices', 'payments', 'products', 'projects', 'quotes'];

function parseRouteFile(string $filePath): array
{
    $content = file_get_contents($filePath);
    $routes = [];
    
    // Parse Route::get() and Route::post() patterns
    preg_match_all(
        "/Route::(get|post|put|patch|delete)\s*\(\s*['\"]([^'\"]+)['\"]\s*,\s*\[([^\]]+)::class,\s*['\"]([^'\"]+)['\"]\]/",
        $content,
        $matches,
        PREG_SET_ORDER
    );
    
    foreach ($matches as $match) {
        $routes[] = [
            'method' => strtoupper($match[1]),
            'uri' => $match[2],
            'controller' => trim($match[3]),
            'action' => $match[4],
        ];
    }
    
    // Also parse grouped routes with controller() method
    if (preg_match("/->controller\(([^\)]+)::class\)/", $content, $controllerMatch)) {
        $groupController = trim($controllerMatch[1]);
        
        preg_match_all(
            "/Route::(get|post|put|patch|delete)\s*\(\s*['\"]([^'\"]+)['\"]\s*,\s*['\"]([^'\"]+)['\"]\)/",
            $content,
            $groupMatches,
            PREG_SET_ORDER
        );
        
        foreach ($groupMatches as $match) {
            $routes[] = [
                'method' => strtoupper($match[1]),
                'uri' => $match[2],
                'controller' => $groupController,
                'action' => $match[3],
            ];
        }
    }
    
    return $routes;
}

function getControllerShortName(string $fullControllerName): string
{
    $parts = explode('\\', $fullControllerName);
    return end($parts);
}

function generateTestClass(string $module, string $controller, array $routes): string
{
    $controllerShortName = getControllerShortName($controller);
    $testClassName = str_replace('Controller', 'ControllerTest', $controllerShortName);
    $namespace = "Modules\\" . ucfirst($module) . "\\Tests";
    $controllerNamespace = $controller;
    
    $testMethods = '';
    
    foreach ($routes as $route) {
        $testMethods .= generateHappyPathTest($route);
        $testMethods .= generateFailurePathTests($route);
    }
    
    return <<<PHP
<?php

namespace {$namespace};

use {$controllerNamespace};
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass({$controllerShortName}::class)]
class {$testClassName} extends TestCase
{
{$testMethods}
}

PHP;
}

function generateHappyPathTest(array $route): string
{
    $methodName = 'it_' . strtolower($route['method']) . '_' . sanitizeMethodName($route['action']) . '_returns_successful_response';
    $uri = $route['uri'];
    $method = $route['method'];
    
    $testBody = generateTestBody($route, 'happy');
    
    return <<<PHP

    #[Test]
    public function {$methodName}(): void
    {
{$testBody}
    }

PHP;
}

function generateFailurePathTests(array $route): string
{
    $tests = '';
    
    // Test 1: Unauthenticated access
    $tests .= generateUnauthenticatedTest($route);
    
    // Test 2: Invalid parameters (for routes with parameters)
    if (str_contains($route['uri'], '{')) {
        $tests .= generateInvalidParameterTest($route);
    }
    
    // Test 3: Missing required data (for POST routes)
    if ($route['method'] === 'POST') {
        $tests .= generateMissingDataTest($route);
    }
    
    // Test 4: Invalid data format
    if ($route['method'] === 'POST') {
        $tests .= generateInvalidDataTest($route);
    }
    
    return $tests;
}

function generateUnauthenticatedTest(array $route): string
{
    $methodName = 'it_' . strtolower($route['method']) . '_' . sanitizeMethodName($route['action']) . '_requires_authentication';
    
    return <<<PHP

    #[Test]
    public function {$methodName}(): void
    {
        // Arrange - No authenticated user
        
        // Act
        \$response = \$this->call('{$route['method']}', '{$route['uri']}');
        
        // Assert
        \$this->assertNotEquals(200, \$response->getStatusCode(), 'Unauthenticated request should not return 200');
        // Should redirect to login or return 401/403
        \$this->assertTrue(
            in_array(\$response->getStatusCode(), [302, 401, 403]),
            'Expected redirect to login or unauthorized response'
        );
    }

PHP;
}

function generateInvalidParameterTest(array $route): string
{
    $methodName = 'it_' . strtolower($route['method']) . '_' . sanitizeMethodName($route['action']) . '_validates_parameters';
    $invalidUri = preg_replace('/\{[^}]+\}/', 'invalid', $route['uri']);
    
    return <<<PHP

    #[Test]
    public function {$methodName}(): void
    {
        // Arrange - Invalid parameter
        // TODO: Setup authenticated user
        
        // Act
        \$response = \$this->call('{$route['method']}', '{$invalidUri}');
        
        // Assert
        \$this->assertNotEquals(200, \$response->getStatusCode(), 'Invalid parameter should not return success');
    }

PHP;
}

function generateMissingDataTest(array $route): string
{
    $methodName = 'it_' . strtolower($route['method']) . '_' . sanitizeMethodName($route['action']) . '_validates_required_fields';
    
    return <<<PHP

    #[Test]
    public function {$methodName}(): void
    {
        // Arrange - Authenticated user but missing required data
        // TODO: Setup authenticated user
        
        // Act
        \$response = \$this->call('{$route['method']}', '{$route['uri']}', []);
        
        // Assert
        // Should return validation errors or redirect back with errors
        \$this->assertTrue(
            \$response->getStatusCode() >= 400 || \$response->getStatusCode() === 302,
            'Missing required data should return error or redirect'
        );
    }

PHP;
}

function generateInvalidDataTest(array $route): string
{
    $methodName = 'it_' . strtolower($route['method']) . '_' . sanitizeMethodName($route['action']) . '_rejects_invalid_data';
    
    return <<<PHP

    #[Test]
    public function {$methodName}(): void
    {
        // Arrange - Authenticated user with invalid data formats
        // TODO: Setup authenticated user
        \$invalidData = [
            'test_field' => '<script>alert("xss")</script>',
            'email' => 'not-an-email',
            'number' => 'not-a-number',
        ];
        
        // Act
        \$response = \$this->call('{$route['method']}', '{$route['uri']}', \$invalidData);
        
        // Assert
        \$this->assertNotEquals(200, \$response->getStatusCode(), 'Invalid data format should not succeed');
    }

PHP;
}

function generateTestBody(array $route, string $type): string
{
    if ($type === 'happy') {
        return <<<PHP
        // Arrange
        // TODO: Setup authenticated user with proper permissions
        // TODO: Setup any required database fixtures
        
        // Act
        \$response = \$this->call('{$route['method']}', '{$route['uri']}');
        
        // Assert
        \$this->assertEquals(200, \$response->getStatusCode(), 'Expected successful response');
        // TODO: Add specific assertions for response content
        // TODO: Verify database changes if applicable
        // TODO: Verify any side effects (emails, notifications, etc.)
PHP;
    }
    
    return '';
}

function sanitizeMethodName(string $name): string
{
    return preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
}

// Main execution
echo "Scanning for routes...\n";

foreach ($modules as $module) {
    $routesPath = $modulesPath . '/' . $module . '/routes';
    
    if (!is_dir($routesPath)) {
        echo "Skipping module '$module' - no routes directory\n";
        continue;
    }
    
    $routeFiles = glob($routesPath . '/*.php');
    
    foreach ($routeFiles as $routeFile) {
        echo "Processing: $routeFile\n";
        
        $routes = parseRouteFile($routeFile);
        
        if (empty($routes)) {
            echo "  No routes found\n";
            continue;
        }
        
        // Group routes by controller
        $routesByController = [];
        foreach ($routes as $route) {
            $controller = $route['controller'];
            if (!isset($routesByController[$controller])) {
                $routesByController[$controller] = [];
            }
            $routesByController[$controller][] = $route;
        }
        
        // Generate test file for each controller
        foreach ($routesByController as $controller => $controllerRoutes) {
            $testCode = generateTestClass($module, $controller, $controllerRoutes);
            
            $testDir = $modulesPath . '/' . $module . '/tests';
            if (!is_dir($testDir)) {
                mkdir($testDir, 0755, true);
            }
            
            $controllerShortName = getControllerShortName($controller);
            $testFileName = $testDir . '/' . str_replace('Controller', 'ControllerTest', $controllerShortName) . '.php';
            
            echo "  Generated: $testFileName\n";
            file_put_contents($testFileName, $testCode);
        }
    }
}

echo "\nTest generation complete!\n";
echo "Next steps:\n";
echo "1. Review generated tests\n";
echo "2. Fill in TODOs with proper authentication setup\n";
echo "3. Add specific assertions for each route\n";
echo "4. Run tests: vendor/bin/phpunit\n";
