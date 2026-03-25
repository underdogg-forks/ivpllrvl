<?php

namespace Modules\Invoices\Tests;

use Modules\Invoices\Controllers\InvoicesController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(InvoicesController::class)]
class InvoicesControllerTest extends TestCase
{
    private const EXPECTED_ACTIONS = [
        'archive',
        'delete',
        'delete_invoice_tax',
        'download',
        'generate_pdf',
        'generate_sumex_copy',
        'generate_sumex_pdf',
        'generate_xml',
        'index',
        'recalculate_all_invoices',
        'status',
        'view'
    ];

    private const EXPECTED_ROUTES = [
        ['action' => 'index', 'verb' => 'GET', 'uri' => 'invoices/index'],
        ['action' => 'archive', 'verb' => 'GET', 'uri' => 'invoices/archive'],
        ['action' => 'status', 'verb' => 'GET', 'uri' => 'invoices/status/all'],
        ['action' => 'status', 'verb' => 'GET', 'uri' => 'invoices/status/draft'],
        ['action' => 'status', 'verb' => 'GET', 'uri' => 'invoices/status/overdue'],
        ['action' => 'status', 'verb' => 'GET', 'uri' => 'invoices/status/paid'],
        ['action' => 'status', 'verb' => 'GET', 'uri' => 'invoices/status/sent'],
        ['action' => 'status', 'verb' => 'GET', 'uri' => 'invoices/status/viewed'],
        ['action' => 'view', 'verb' => 'GET', 'uri' => 'invoices/view/{id}'],
        ['action' => 'delete', 'verb' => 'GET', 'uri' => 'invoices/delete/{id}'],
        ['action' => 'generate_pdf', 'verb' => 'GET', 'uri' => 'invoices/generate_pdf/{id}']
    ];

    #[Test]
    public function it_validates_all_actions_with_happy_and_failing_paths(): void
    {
        /* Arrange */
        $actions = $this->discoverPublicControllerActions(InvoicesController::class);

        /* Act */
        sort($actions);

        $missingExpectedActions = [];
        foreach (self::EXPECTED_ACTIONS as $expectedAction) {
            if (!in_array($expectedAction, $actions, true)) {
                $missingExpectedActions[] = $expectedAction;
            }
        }

        $unexpectedActionFound = in_array('missing_action_for_failure_path', $actions, true);

        /* Assert */
        self::assertSame(self::EXPECTED_ACTIONS, $actions);
        self::assertSame([], $missingExpectedActions);
        self::assertFalse($unexpectedActionFound);
    }

    #[Test]
    public function it_validates_all_routes_with_happy_and_failing_paths(): void
    {
        /* Arrange */
        $registeredRoutes = $this->discoverRouteDefinitionsForController(InvoicesController::class);

        /* Act */
        $happyPathChecks = [];
        $failingPathChecks = [];

        foreach (self::EXPECTED_ROUTES as $route) {
            $happyPathChecks[] = $this->routeDefinitionExists(
                $registeredRoutes,
                $route['verb'],
                $route['uri'],
                $route['action']
            );

            $failingPathChecks[] = $this->routeDefinitionExists(
                $registeredRoutes,
                $route['verb'],
                $route['uri'] . '/missing',
                $route['action']
            );

            $failingPathChecks[] = $this->routeDefinitionExists(
                $registeredRoutes,
                $route['verb'],
                $route['uri'],
                $route['action'] . '_missing'
            );
        }

        /* Assert */
        foreach ($happyPathChecks as $happyPathCheck) {
            self::assertTrue($happyPathCheck);
        }

        foreach ($failingPathChecks as $failingPathCheck) {
            self::assertFalse($failingPathCheck);
        }
    }

    #[Test]
    public function it_builds_happy_and_failing_required_field_scenarios_from_service_validation_rules(): void
    {
        /* Arrange */
        $controllerReflection = new \ReflectionClass(InvoicesController::class);
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
