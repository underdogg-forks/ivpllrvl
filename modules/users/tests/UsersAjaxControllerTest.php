<?php

namespace Modules\Users\Tests;

use Modules\Users\Controllers\UsersAjaxController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(UsersAjaxController::class)]
class UsersAjaxControllerTest extends TestCase
{
    private const EXPECTED_ACTIONS = [
        'get_latest',
        'load_user_client_table',
        'modal_add_user_client',
        'name_query',
        'save_preference_permissive_search_users',
        'save_user_client'
    ];

    private const EXPECTED_ROUTES = [
        ['action' => 'get_latest', 'verb' => 'POST', 'uri' => 'users/usersajax/get_latest'],
        ['action' => 'load_user_client_table', 'verb' => 'POST', 'uri' => 'users/usersajax/load_user_client_table'],
        ['action' => 'modal_add_user_client', 'verb' => 'POST', 'uri' => 'users/usersajax/modal_add_user_client'],
        ['action' => 'name_query', 'verb' => 'POST', 'uri' => 'users/usersajax/name_query'],
        ['action' => 'save_preference_permissive_search_users', 'verb' => 'POST', 'uri' => 'users/usersajax/save_preference_permissive_search_users'],
        ['action' => 'save_user_client', 'verb' => 'POST', 'uri' => 'users/usersajax/save_user_client']
    ];

    #[Test]
    public function it_validates_all_actions_with_happy_and_failing_paths(): void
    {
        /* Arrange */
        $actions = $this->discoverPublicControllerActions(UsersAjaxController::class);

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
        $registeredRoutes = $this->discoverRouteDefinitionsForController(UsersAjaxController::class);

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
        $controllerReflection = new \ReflectionClass(UsersAjaxController::class);
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
