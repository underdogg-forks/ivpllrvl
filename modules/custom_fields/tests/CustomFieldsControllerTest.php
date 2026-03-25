<?php

namespace Modules\CustomFields\Tests;

use Modules\CustomFields\Controllers\CustomFieldsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(CustomFieldsController::class)]
class CustomFieldsControllerTest extends TestCase
{
    private const EXPECTED_ACTIONS = [
        'delete',
        'form',
        'index',
        'table'
    ];

    private const EXPECTED_ROUTES = [
        ['action' => 'index', 'verb' => 'GET', 'uri' => 'custom_fields'],
        ['action' => 'index', 'verb' => 'GET', 'uri' => 'custom_fields/index'],
        ['action' => 'form', 'verb' => 'GET', 'uri' => 'custom_fields/form'],
        ['action' => 'form', 'verb' => 'GET', 'uri' => 'custom_fields/form/{id}'],
        ['action' => 'delete', 'verb' => 'GET', 'uri' => 'custom_fields/delete/{id}'],
        ['action' => 'table', 'verb' => 'GET', 'uri' => 'custom_fields/table/all'],
        ['action' => 'table', 'verb' => 'GET', 'uri' => 'custom_fields/table/client'],
        ['action' => 'table', 'verb' => 'GET', 'uri' => 'custom_fields/table/invoice'],
        ['action' => 'table', 'verb' => 'GET', 'uri' => 'custom_fields/table/payment'],
        ['action' => 'table', 'verb' => 'GET', 'uri' => 'custom_fields/table/quote'],
        ['action' => 'table', 'verb' => 'GET', 'uri' => 'custom_fields/table/user']
    ];

    #[Test]
    public function it_exposes_all_expected_public_actions(): void
    {
        /* Arrange */
        $actions = $this->discoverPublicControllerActions(CustomFieldsController::class);

        /* Act */
        sort($actions);

        /* Assert */
        self::assertSame(self::EXPECTED_ACTIONS, $actions);
    }

    #[Test]
    public function it_registers_all_expected_routes_for_the_controller(): void
    {
        /* Arrange */
        $registeredRoutes = $this->discoverRouteDefinitionsForController(CustomFieldsController::class);

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
        $registeredRoutes = $this->discoverRouteDefinitionsForController(CustomFieldsController::class);

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
        $controllerReflection = new \ReflectionClass(CustomFieldsController::class);
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
