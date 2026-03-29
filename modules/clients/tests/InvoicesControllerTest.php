<?php

namespace Modules\Clients\Tests;

use Modules\Clients\Controllers\InvoicesController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for InvoicesController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(InvoicesController::class)]
class InvoicesControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = InvoicesController::class;
    
    protected function fixtureTypes(): array
    {
        return ['users', 'clients'];
    }
    
    protected function loadFixtures(): void
    {
        $this->loadAllFixtures();
    }
    
    protected function setUpController(): void
    {
    }

    // #region Authentication Tests

    #[Test]
    public function it_requires_authentication_to_access_controller(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET request to controller
         * Expected behavior: Redirect to login when not authenticated
         */
        $response = $this->get('/');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    // #endregion
}
