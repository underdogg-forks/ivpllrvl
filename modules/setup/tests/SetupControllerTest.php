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
    public function it_validates_all_actions_with_happy_and_failing_paths(): void
    {
        /* Arrange */
        $actions = $this->discoverPublicControllerActions(SetupController::class);

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
        $registeredRoutes = $this->discoverRouteDefinitionsForController(SetupController::class);

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
