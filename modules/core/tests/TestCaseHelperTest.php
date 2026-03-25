<?php

namespace Modules\Core\Tests;

use Modules\Clients\Controllers\ClientsAjaxController;
use Modules\Clients\Controllers\ClientsController;
use Modules\Core\Providers\ModuleServiceProvider;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(TestCase::class)]
class TestCaseHelperTest extends TestCase
{
    // -------------------------------------------------------------------------
    // discoverRouteDefinitionsForController
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_all_routes_for_a_controller_with_get_routes(): void
    {
        /* Arrange */
        $expectedRoutes = [
            ['action' => 'index',  'verb' => 'GET', 'uri' => 'clients'],
            ['action' => 'index',  'verb' => 'GET', 'uri' => 'clients/index'],
            ['action' => 'status', 'verb' => 'GET', 'uri' => 'clients/status/{status}'],
            ['action' => 'status', 'verb' => 'GET', 'uri' => 'clients/status/all'],
            ['action' => 'status', 'verb' => 'GET', 'uri' => 'clients/status/active'],
            ['action' => 'status', 'verb' => 'GET', 'uri' => 'clients/status/inactive'],
            ['action' => 'view',   'verb' => 'GET', 'uri' => 'clients/view/{id}'],
            ['action' => 'view',   'verb' => 'GET', 'uri' => 'clients/view/{id}/invoices'],
            ['action' => 'form',   'verb' => 'GET', 'uri' => 'clients/form'],
            ['action' => 'form',   'verb' => 'GET', 'uri' => 'clients/form/{id}'],
            ['action' => 'delete', 'verb' => 'GET', 'uri' => 'clients/delete/{id}'],
            ['action' => 'delete', 'verb' => 'GET', 'uri' => 'clients/remove/{id}'],
        ];

        /* Act */
        $routes = $this->discoverRouteDefinitionsForController(ClientsController::class);

        /* Assert */
        self::assertCount(12, $routes);
        self::assertSame($expectedRoutes, $routes);
    }

    #[Test]
    public function it_returns_all_routes_for_a_controller_with_post_routes(): void
    {
        /* Arrange */
        $expectedRoutes = [
            ['action' => 'delete_client_note',                      'verb' => 'POST', 'uri' => 'clients/clientsajax/delete_client_note'],
            ['action' => 'get_latest',                              'verb' => 'POST', 'uri' => 'clients/clientsajax/get_latest'],
            ['action' => 'load_client_notes',                       'verb' => 'POST', 'uri' => 'clients/clientsajax/load_client_notes'],
            ['action' => 'name_query',                              'verb' => 'POST', 'uri' => 'clients/clientsajax/name_query'],
            ['action' => 'save_client_note',                        'verb' => 'POST', 'uri' => 'clients/clientsajax/save_client_note'],
            ['action' => 'save_preference_permissive_search_clients', 'verb' => 'POST', 'uri' => 'clients/clientsajax/save_preference_permissive_search_clients'],
        ];

        /* Act */
        $routes = $this->discoverRouteDefinitionsForController(ClientsAjaxController::class);

        /* Assert */
        self::assertCount(6, $routes);
        self::assertSame($expectedRoutes, $routes);
    }

    #[Test]
    public function it_returns_an_empty_array_when_no_route_file_exists_for_the_controller(): void
    {
        /* Arrange */
        // ModuleServiceProvider lives in Modules\Core\Providers, so the route file would be
        // modules/core/routes/ModuleServiceProvider.php — which does not exist.

        /* Act */
        $routes = $this->discoverRouteDefinitionsForController(ModuleServiceProvider::class);

        /* Assert */
        self::assertSame([], $routes);
    }

    #[Test]
    public function it_returns_each_route_entry_with_action_verb_and_uri_keys(): void
    {
        /* Arrange / Act */
        $routes = $this->discoverRouteDefinitionsForController(ClientsController::class);

        /* Assert */
        foreach ($routes as $route) {
            self::assertArrayHasKey('action', $route);
            self::assertArrayHasKey('verb', $route);
            self::assertArrayHasKey('uri', $route);
        }
    }

    #[Test]
    public function it_preserves_route_declaration_order_from_the_file(): void
    {
        /* Arrange */
        $routes = $this->discoverRouteDefinitionsForController(ClientsController::class);

        /* Act */
        $uris = array_column($routes, 'uri');

        /* Assert – first two entries must be the index routes as declared in the file */
        self::assertSame('clients', $uris[0]);
        self::assertSame('clients/index', $uris[1]);
    }

    #[Test]
    public function it_correctly_parses_uris_containing_path_parameter_placeholders(): void
    {
        /* Arrange */
        $routes = $this->discoverRouteDefinitionsForController(ClientsController::class);

        /* Act */
        $urisWithParams = array_values(
            array_filter(array_column($routes, 'uri'), static fn (string $u): bool => str_contains($u, '{'))
        );

        /* Assert */
        self::assertNotEmpty($urisWithParams);
        self::assertContains('clients/view/{id}', $urisWithParams);
        self::assertContains('clients/status/{status}', $urisWithParams);
    }

    #[Test]
    public function it_maps_the_controller_class_namespace_to_the_correct_module_directory(): void
    {
        /* Arrange */
        // ClientsAjaxController → namespace Modules\Clients\Controllers → module "clients"
        // The route file is modules/clients/routes/ClientsAjaxController.php

        /* Act */
        $routes = $this->discoverRouteDefinitionsForController(ClientsAjaxController::class);

        /* Assert */
        self::assertNotEmpty($routes);
        foreach ($routes as $route) {
            self::assertStringStartsWith('clients/', $route['uri']);
        }
    }

    // -------------------------------------------------------------------------
    // routeDefinitionExists
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_true_when_a_matching_route_exists_in_the_collection(): void
    {
        /* Arrange */
        $routes = [
            ['verb' => 'GET',  'uri' => 'clients',       'action' => 'index'],
            ['verb' => 'POST', 'uri' => 'clients/store',  'action' => 'store'],
        ];

        /* Act */
        $exists = $this->routeDefinitionExists($routes, 'GET', 'clients', 'index');

        /* Assert */
        self::assertTrue($exists);
    }

    #[Test]
    public function it_returns_false_when_the_route_collection_is_empty(): void
    {
        /* Arrange */
        $routes = [];

        /* Act */
        $exists = $this->routeDefinitionExists($routes, 'GET', 'clients', 'index');

        /* Assert */
        self::assertFalse($exists);
    }

    #[Test]
    public function it_returns_false_when_the_verb_does_not_match(): void
    {
        /* Arrange */
        $routes = [
            ['verb' => 'POST', 'uri' => 'clients', 'action' => 'index'],
        ];

        /* Act */
        $exists = $this->routeDefinitionExists($routes, 'GET', 'clients', 'index');

        /* Assert */
        self::assertFalse($exists);
    }

    #[Test]
    public function it_returns_false_when_the_uri_does_not_match(): void
    {
        /* Arrange */
        $routes = [
            ['verb' => 'GET', 'uri' => 'clients/list', 'action' => 'index'],
        ];

        /* Act */
        $exists = $this->routeDefinitionExists($routes, 'GET', 'clients', 'index');

        /* Assert */
        self::assertFalse($exists);
    }

    #[Test]
    public function it_returns_false_when_the_action_does_not_match(): void
    {
        /* Arrange */
        $routes = [
            ['verb' => 'GET', 'uri' => 'clients', 'action' => 'list'],
        ];

        /* Act */
        $exists = $this->routeDefinitionExists($routes, 'GET', 'clients', 'index');

        /* Assert */
        self::assertFalse($exists);
    }

    #[Test]
    public function it_returns_false_when_only_two_of_three_criteria_match(): void
    {
        /* Arrange */
        $routes = [
            ['verb' => 'GET',  'uri' => 'clients', 'action' => 'other'],
            ['verb' => 'POST', 'uri' => 'clients', 'action' => 'index'],
            ['verb' => 'GET',  'uri' => 'clients/other', 'action' => 'index'],
        ];

        /* Act */
        $exists = $this->routeDefinitionExists($routes, 'GET', 'clients', 'index');

        /* Assert */
        self::assertFalse($exists);
    }

    #[Test]
    public function it_finds_a_matching_route_that_is_not_the_first_entry(): void
    {
        /* Arrange */
        $routes = [
            ['verb' => 'GET', 'uri' => 'clients',       'action' => 'index'],
            ['verb' => 'GET', 'uri' => 'clients/form',  'action' => 'form'],
            ['verb' => 'GET', 'uri' => 'clients/delete/{id}', 'action' => 'delete'],
        ];

        /* Act */
        $exists = $this->routeDefinitionExists($routes, 'GET', 'clients/delete/{id}', 'delete');

        /* Assert */
        self::assertTrue($exists);
    }

    #[Test]
    public function it_is_case_sensitive_for_the_http_verb(): void
    {
        /* Arrange */
        $routes = [
            ['verb' => 'GET', 'uri' => 'clients', 'action' => 'index'],
        ];

        /* Act */
        $existsLower = $this->routeDefinitionExists($routes, 'get', 'clients', 'index');

        /* Assert */
        self::assertFalse($existsLower);
    }

    #[Test]
    public function it_finds_a_post_route_in_a_mixed_verb_collection(): void
    {
        /* Arrange */
        $routes = $this->discoverRouteDefinitionsForController(ClientsAjaxController::class);

        /* Act */
        $exists = $this->routeDefinitionExists(
            $routes,
            'POST',
            'clients/clientsajax/save_client_note',
            'save_client_note'
        );

        /* Assert */
        self::assertTrue($exists);
    }

    #[Test]
    public function it_does_not_find_a_route_from_a_different_controller_in_the_collection(): void
    {
        /* Arrange */
        $routes = $this->discoverRouteDefinitionsForController(ClientsAjaxController::class);

        /* Act */
        // This URI belongs to ClientsController, not ClientsAjaxController
        $exists = $this->routeDefinitionExists($routes, 'GET', 'clients', 'index');

        /* Assert */
        self::assertFalse($exists);
    }
}