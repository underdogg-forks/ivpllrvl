<?php

namespace Modules\Invoices\Tests;

use Modules\Invoices\Controllers\RecurringController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(RecurringController::class)]
class RecurringControllerTest extends TestCase
{
    #[Test]
    public function it_registers_a_route_for_every_public_controller_action(): void
    {
        /* Arrange */
        $actions = $this->discoverPublicControllerActions(RecurringController::class);
        $registeredRoutes = $this->discoverRouteDefinitionsForController(RecurringController::class);

        /* Act */
        $missingActions = array_values(array_diff($actions, array_keys($registeredRoutes)));

        /* Assert */
        self::assertNotEmpty($actions);
        self::assertSame([], $missingActions);
    }

    #[Test]
    public function it_registers_expected_http_verbs_and_uris_for_every_action(): void
    {
        /* Arrange */
        $actions = $this->discoverPublicControllerActions(RecurringController::class);
        $registeredRoutes = $this->discoverRouteDefinitionsForController(RecurringController::class);

        /* Act */
        $reflection = new \ReflectionClass(RecurringController::class);
        $module = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', explode('\\', $reflection->getNamespaceName())[1] ?? 'core'));
        $controller = strtolower(str_replace('Controller', '', $reflection->getShortName()));

        /* Assert */
        foreach ($actions as $action) {
            $expectedVerb = 'GET';
            if (str_contains($reflection->getShortName(), 'AjaxController')) {
                $expectedVerb = 'POST';
            } elseif ($action === 'form') {
                $expectedVerb = 'GET';
            } elseif (preg_match('/^(save|store|create|update|delete|remove|insert)/i', $action) === 1) {
                $expectedVerb = 'POST';
            }

            self::assertArrayHasKey($action, $registeredRoutes);
            self::assertSame($expectedVerb, $registeredRoutes[$action]['verb']);
            self::assertSame($module . '/' . $controller . '/' . $action, $registeredRoutes[$action]['uri']);
        }
    }

    #[Test]
    public function it_does_not_register_routes_for_missing_actions(): void
    {
        /* Arrange */
        $registeredRoutes = $this->discoverRouteDefinitionsForController(RecurringController::class);

        /* Act */
        $missingAction = 'non_existing_action';

        /* Assert */
        self::assertArrayNotHasKey($missingAction, $registeredRoutes);
    }

    #[Test]
    public function it_builds_happy_and_failing_required_field_scenarios_from_service_validation_rules(): void
    {
        /* Arrange */
        $controllerReflection = new \ReflectionClass(RecurringController::class);
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
