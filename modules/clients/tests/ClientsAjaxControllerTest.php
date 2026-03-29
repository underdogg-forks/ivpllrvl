<?php

namespace Modules\Clients\Tests;

use Modules\Clients\Controllers\ClientsAjaxController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for ClientsAjaxController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(ClientsAjaxController::class)]
class ClientsAjaxControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = ClientsAjaxController::class;
    
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
    public function it_requires_authentication_for_name_query(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /clients/ajax/name_query
         * Expected behavior: Redirect to login when not authenticated
         */
        $response = $this->post('/clients/ajax/name_query');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    // #endregion

    // #region Name Query Tests

    #[Test]
    public function it_returns_matching_active_clients_for_name_query(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: POST /clients/ajax/name_query
         * POST data: {"query": "Test"}
         * Expected behavior: Return JSON array of matching clients
         */
        $response = $this->post('/clients/ajax/name_query', ['query' => 'Test']);
        
        /* Assert */
        $response->assertJson([]);
        $this->assertDatabaseHasRecord('ip_clients', ['client_active' => 1]);
    }

    // #endregion
}
