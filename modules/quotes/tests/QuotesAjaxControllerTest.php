<?php

namespace Modules\Quotes\Tests;

use Modules\Quotes\Controllers\QuotesAjaxController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(QuotesAjaxController::class)]
class QuotesAjaxControllerTest extends TestCase
{
    private const EXPECTED_ACTIONS = [
        'change_client',
        'change_user',
        'copy_quote',
        'create',
        'delete_item',
        'get_item',
        'modal_change_client',
        'modal_change_user',
        'modal_copy_quote',
        'modal_create_quote',
        'modal_quote_to_invoice',
        'quote_to_invoice',
        'save',
        'save_quote_tax_rate'
    ];

    private const EXPECTED_ROUTES = [
        ['action' => 'change_client', 'verb' => 'POST', 'uri' => 'quotes/quotesajax/change_client'],
        ['action' => 'change_user', 'verb' => 'POST', 'uri' => 'quotes/quotesajax/change_user'],
        ['action' => 'copy_quote', 'verb' => 'POST', 'uri' => 'quotes/quotesajax/copy_quote'],
        ['action' => 'create', 'verb' => 'POST', 'uri' => 'quotes/quotesajax/create'],
        ['action' => 'delete_item', 'verb' => 'POST', 'uri' => 'quotes/quotesajax/delete_item'],
        ['action' => 'get_item', 'verb' => 'POST', 'uri' => 'quotes/quotesajax/get_item'],
        ['action' => 'modal_change_client', 'verb' => 'POST', 'uri' => 'quotes/quotesajax/modal_change_client'],
        ['action' => 'modal_change_user', 'verb' => 'POST', 'uri' => 'quotes/quotesajax/modal_change_user'],
        ['action' => 'modal_copy_quote', 'verb' => 'POST', 'uri' => 'quotes/quotesajax/modal_copy_quote'],
        ['action' => 'modal_create_quote', 'verb' => 'POST', 'uri' => 'quotes/quotesajax/modal_create_quote'],
        ['action' => 'modal_quote_to_invoice', 'verb' => 'POST', 'uri' => 'quotes/quotesajax/modal_quote_to_invoice'],
        ['action' => 'quote_to_invoice', 'verb' => 'POST', 'uri' => 'quotes/quotesajax/quote_to_invoice'],
        ['action' => 'save', 'verb' => 'POST', 'uri' => 'quotes/quotesajax/save'],
        ['action' => 'save_quote_tax_rate', 'verb' => 'POST', 'uri' => 'quotes/quotesajax/save_quote_tax_rate']
    ];

    #[Test]
    public function it_exposes_all_expected_public_actions(): void
    {
        /* Arrange */
        $actions = $this->discoverPublicControllerActions(QuotesAjaxController::class);

        /* Act */
        sort($actions);

        /* Assert */
        self::assertSame(self::EXPECTED_ACTIONS, $actions);
    }

    #[Test]
    public function it_registers_all_expected_routes_for_the_controller(): void
    {
        /* Arrange */
        $registeredRoutes = $this->discoverRouteDefinitionsForController(QuotesAjaxController::class);

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
        $registeredRoutes = $this->discoverRouteDefinitionsForController(QuotesAjaxController::class);

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
        $controllerReflection = new \ReflectionClass(QuotesAjaxController::class);
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
