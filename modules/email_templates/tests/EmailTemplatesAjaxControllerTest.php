<?php

namespace Modules\EmailTemplates\Tests;

use Modules\EmailTemplates\Controllers\EmailTemplatesAjaxController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(EmailTemplatesAjaxController::class)]
class EmailTemplatesAjaxControllerTest extends TestCase
{
    private const EXPECTED_ACTIONS = [
        'get_content'
    ];

    private const EXPECTED_ROUTES = [
        ['action' => 'get_content', 'verb' => 'POST', 'uri' => 'email_templates/emailtemplatesajax/get_content']
    ];

    #[Test]
    public function it_validates_all_actions_with_happy_and_failing_paths(): void
    {
        /* Arrange */
        $actions = $this->discoverPublicControllerActions(EmailTemplatesAjaxController::class);

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
        $registeredRoutes = $this->discoverRouteDefinitionsForController(EmailTemplatesAjaxController::class);

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
        $controllerReflection = new \ReflectionClass(EmailTemplatesAjaxController::class);
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
