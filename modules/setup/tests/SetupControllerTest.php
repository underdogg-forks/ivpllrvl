<?php

namespace Modules\Setup\Tests;

use Modules\Setup\Controllers\SetupController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(SetupController::class)]
class SetupControllerTest extends TestCase
{
    private const EXPECTED_ACTIONS = [
        'calculation_info',
        'complete',
        'configure_database',
        'create_user',
        'index',
        'install_tables',
        'language',
        'prerequisites',
        'upgrade_tables'
    ];

    private const EXPECTED_ROUTES = [
        ['action' => 'calculation_info', 'verb' => 'GET', 'uri' => 'setup/setup/calculation_info'],
        ['action' => 'complete', 'verb' => 'GET', 'uri' => 'setup/setup/complete'],
        ['action' => 'configure_database', 'verb' => 'GET', 'uri' => 'setup/setup/configure_database'],
        ['action' => 'create_user', 'verb' => 'POST', 'uri' => 'setup/setup/create_user'],
        ['action' => 'index', 'verb' => 'GET', 'uri' => 'setup/setup/index'],
        ['action' => 'install_tables', 'verb' => 'GET', 'uri' => 'setup/setup/install_tables'],
        ['action' => 'language', 'verb' => 'GET', 'uri' => 'setup/setup/language'],
        ['action' => 'prerequisites', 'verb' => 'GET', 'uri' => 'setup/setup/prerequisites'],
        ['action' => 'upgrade_tables', 'verb' => 'GET', 'uri' => 'setup/setup/upgrade_tables']
    ];

    #[Test]
    public function it_exposes_all_expected_public_actions(): void
    {
        /* Arrange */
        $actions = $this->discoverPublicControllerActions(SetupController::class);

        /* Act */
        sort($actions);

        /* Assert */
        self::assertSame(self::EXPECTED_ACTIONS, $actions);
    }

    #[Test]
    public function it_registers_all_expected_routes_for_the_controller(): void
    {
        /* Arrange */
        $registeredRoutes = $this->discoverRouteDefinitionsForController(SetupController::class);

        /* Act */
        $registeredRouteKeys = [];
        foreach ($registeredRoutes as $route) {
            $registeredRouteKeys[] = $route['verb'] . ' ' . $route['uri'] . ' ' . $route['action'];
        }

        $expectedRouteKeys = [];
        foreach (self::EXPECTED_ROUTES as $route) {
            $expectedRouteKeys[] = $route['verb'] . ' ' . $route['uri'] . ' ' . $route['action'];
        }

        sort($registeredRouteKeys);
        sort($expectedRouteKeys);

        /* Assert */
        self::assertSame($expectedRouteKeys, $registeredRouteKeys);
    }

    #[Test]
    public function it_fails_for_a_route_that_is_not_registered(): void
    {
        /* Arrange */
        $registeredRoutes = $this->discoverRouteDefinitionsForController(SetupController::class);

        /* Act */
        $hasMissingRoute = $this->routeDefinitionExists(
            $registeredRoutes,
            'GET',
            'missing/route/for/testing',
            'missing_action'
        );

        /* Assert */
        self::assertFalse($hasMissingRoute);
    }

    #[Test]
    public function it_builds_happy_and_failing_required_field_scenarios_from_service_validation_rules(): void
    {
        /* Arrange */
        $controllerReflection = new \ReflectionClass(SetupController::class);
        $modulePath = dirname($controllerReflection->getFileName(), 2);
        $serviceFiles = glob($modulePath . '/Services/*Service.php') ?: [];

        $requiredFields = [];
        foreach ($serviceFiles as $serviceFile) {
            $requiredFields = $this->extractRequiredFieldsFromServiceFile($serviceFile);
            if ($requiredFields !== []) {
                break;
            }
        }

        if ($requiredFields === []) {
            self::markTestSkipped('No required validation rules found in related service files.');
        }

        $happyPayload = [];
        foreach ($requiredFields as $field) {
            $happyPayload[$field] = 'value';
        }

        /* Act */
        $happyPathResult = $this->validateRequiredFields($happyPayload, $requiredFields);
        $failingPayload = $happyPayload;
        unset($failingPayload[$requiredFields[0]]);
        $failingPathResult = $this->validateRequiredFields($failingPayload, $requiredFields);

        /* Assert */
        self::assertTrue($happyPathResult);
        self::assertFalse($failingPathResult);
    }
}
