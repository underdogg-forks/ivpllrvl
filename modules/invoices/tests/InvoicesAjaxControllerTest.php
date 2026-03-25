<?php

namespace Modules\Invoices\Tests;

use Modules\Invoices\Controllers\InvoicesAjaxController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(InvoicesAjaxController::class)]
class InvoicesAjaxControllerTest extends TestCase
{
    private const EXPECTED_ACTIONS = [
        'change_client',
        'change_user',
        'copy_invoice',
        'create',
        'create_credit',
        'create_recurring',
        'delete_item',
        'get_item',
        'get_recur_start_date',
        'modal_change_client',
        'modal_change_user',
        'modal_copy_invoice',
        'modal_create_credit',
        'modal_create_invoice',
        'modal_create_recurring',
        'save',
        'save_invoice_tax_rate'
    ];

    private const EXPECTED_ROUTES = [
        ['action' => 'change_client', 'verb' => 'POST', 'uri' => 'invoices/invoicesajax/change_client'],
        ['action' => 'change_user', 'verb' => 'POST', 'uri' => 'invoices/invoicesajax/change_user'],
        ['action' => 'copy_invoice', 'verb' => 'POST', 'uri' => 'invoices/invoicesajax/copy_invoice'],
        ['action' => 'create', 'verb' => 'POST', 'uri' => 'invoices/invoicesajax/create'],
        ['action' => 'create_credit', 'verb' => 'POST', 'uri' => 'invoices/invoicesajax/create_credit'],
        ['action' => 'create_recurring', 'verb' => 'POST', 'uri' => 'invoices/invoicesajax/create_recurring'],
        ['action' => 'delete_item', 'verb' => 'POST', 'uri' => 'invoices/invoicesajax/delete_item'],
        ['action' => 'get_item', 'verb' => 'POST', 'uri' => 'invoices/invoicesajax/get_item'],
        ['action' => 'get_recur_start_date', 'verb' => 'POST', 'uri' => 'invoices/invoicesajax/get_recur_start_date'],
        ['action' => 'modal_change_client', 'verb' => 'POST', 'uri' => 'invoices/invoicesajax/modal_change_client'],
        ['action' => 'modal_change_user', 'verb' => 'POST', 'uri' => 'invoices/invoicesajax/modal_change_user'],
        ['action' => 'modal_copy_invoice', 'verb' => 'POST', 'uri' => 'invoices/invoicesajax/modal_copy_invoice'],
        ['action' => 'modal_create_credit', 'verb' => 'POST', 'uri' => 'invoices/invoicesajax/modal_create_credit'],
        ['action' => 'modal_create_invoice', 'verb' => 'POST', 'uri' => 'invoices/invoicesajax/modal_create_invoice'],
        ['action' => 'modal_create_recurring', 'verb' => 'POST', 'uri' => 'invoices/invoicesajax/modal_create_recurring'],
        ['action' => 'save', 'verb' => 'POST', 'uri' => 'invoices/invoicesajax/save'],
        ['action' => 'save_invoice_tax_rate', 'verb' => 'POST', 'uri' => 'invoices/invoicesajax/save_invoice_tax_rate']
    ];

    #[Test]
    public function it_validates_all_actions_with_happy_and_failing_paths(): void
    {
        /* Arrange */
        $actions = $this->discoverPublicControllerActions(InvoicesAjaxController::class);

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
        $registeredRoutes = $this->discoverRouteDefinitionsForController(InvoicesAjaxController::class);

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
        $controllerReflection = new \ReflectionClass(InvoicesAjaxController::class);
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
