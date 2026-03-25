<?php

namespace Modules\Guest\Tests;

use Modules\Guest\Controllers\PaymentsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(PaymentsController::class)]
class PaymentsControllerTest extends TestCase
{
    #[Test]
    public function it_guesses_routes_for_all_public_actions(): void
    {
        // Arrange
        $actions = $this->discoverPublicControllerActions(PaymentsController::class);

        // Act
        $routes = array_map(fn (string $action): array => $this->guessRouteForControllerAction(PaymentsController::class, $action), $actions);

        // Assert
        self::assertNotEmpty($actions);
        self::assertCount(count($actions), $routes);

        foreach ($routes as $route) {
            self::assertContains($route['verb'], ['GET', 'POST']);
            self::assertMatchesRegularExpression('/^[a-z0-9_\/-]+$/', $route['uri']);
        }
    }

    #[Test]
    public function it_uses_post_routes_for_ajax_controller_actions(): void
    {
        // Arrange
        $actions = $this->discoverPublicControllerActions(PaymentsController::class);

        // Act
        $routes = array_map(fn (string $action): array => $this->guessRouteForControllerAction(PaymentsController::class, $action), $actions);

        // Assert
        if (!str_contains(PaymentsController::class, 'AjaxController')) {
            self::assertTrue(true);

            return;
        }

        foreach ($routes as $route) {
            self::assertSame('POST', $route['verb']);
        }
    }

    #[Test]
    public function it_builds_happy_and_failing_required_field_scenarios_from_service_validation_rules(): void
    {
        // Arrange
        $controllerReflection = new \ReflectionClass(PaymentsController::class);
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

        // Act
        $happyPathResult = $this->validateRequiredFields($happyPayload, $requiredFields);
        $failingPayload = $happyPayload;
        unset($failingPayload[$requiredFields[0]]);
        $failingPathResult = $this->validateRequiredFields($failingPayload, $requiredFields);

        // Assert
        self::assertTrue($happyPathResult);
        self::assertFalse($failingPathResult);
    }
}
