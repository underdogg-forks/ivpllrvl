<?php

namespace Modules\Filter\Tests;

use Modules\Filter\Controllers\FilterAjaxController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(FilterAjaxController::class)]
class FilterAjaxControllerTest extends TestCase
{
    private const EXPECTED_ACTIONS = [
        'filter_archives',
        'filter_clients',
        'filter_custom_fields',
        'filter_custom_values',
        'filter_custom_values_field',
        'filter_families',
        'filter_invoices',
        'filter_invoices_recuring',
        'filter_online_logs',
        'filter_payments',
        'filter_products',
        'filter_projects',
        'filter_quotes',
        'filter_tasks',
        'filter_users'
    ];

    private const EXPECTED_ROUTES = [
        ['action' => 'filter_archives', 'verb' => 'POST', 'uri' => 'filter/filterajax/filter_archives'],
        ['action' => 'filter_clients', 'verb' => 'POST', 'uri' => 'filter/filterajax/filter_clients'],
        ['action' => 'filter_custom_fields', 'verb' => 'POST', 'uri' => 'filter/filterajax/filter_custom_fields'],
        ['action' => 'filter_custom_values', 'verb' => 'POST', 'uri' => 'filter/filterajax/filter_custom_values'],
        ['action' => 'filter_custom_values_field', 'verb' => 'POST', 'uri' => 'filter/filterajax/filter_custom_values_field'],
        ['action' => 'filter_families', 'verb' => 'POST', 'uri' => 'filter/filterajax/filter_families'],
        ['action' => 'filter_invoices', 'verb' => 'POST', 'uri' => 'filter/filterajax/filter_invoices'],
        ['action' => 'filter_invoices_recuring', 'verb' => 'POST', 'uri' => 'filter/filterajax/filter_invoices_recuring'],
        ['action' => 'filter_online_logs', 'verb' => 'POST', 'uri' => 'filter/filterajax/filter_online_logs'],
        ['action' => 'filter_payments', 'verb' => 'POST', 'uri' => 'filter/filterajax/filter_payments'],
        ['action' => 'filter_products', 'verb' => 'POST', 'uri' => 'filter/filterajax/filter_products'],
        ['action' => 'filter_projects', 'verb' => 'POST', 'uri' => 'filter/filterajax/filter_projects'],
        ['action' => 'filter_quotes', 'verb' => 'POST', 'uri' => 'filter/filterajax/filter_quotes'],
        ['action' => 'filter_tasks', 'verb' => 'POST', 'uri' => 'filter/filterajax/filter_tasks'],
        ['action' => 'filter_users', 'verb' => 'POST', 'uri' => 'filter/filterajax/filter_users']
    ];

    #[Test]
    public function it_exposes_all_expected_public_actions(): void
    {
        /* Arrange */
        $actions = $this->discoverPublicControllerActions(FilterAjaxController::class);

        /* Act */
        sort($actions);

        /* Assert */
        self::assertSame(self::EXPECTED_ACTIONS, $actions);
    }

    #[Test]
    public function it_registers_all_expected_routes_for_the_controller(): void
    {
        /* Arrange */
        $registeredRoutes = $this->discoverRouteDefinitionsForController(FilterAjaxController::class);

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
        $registeredRoutes = $this->discoverRouteDefinitionsForController(FilterAjaxController::class);

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
        $controllerReflection = new \ReflectionClass(FilterAjaxController::class);
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
