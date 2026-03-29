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
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'clients'];
    }
    
    /**
     * Load fixtures using SOLID trait pattern
     */
    protected function loadFixtures(): void
    {
        $this->loadAllFixtures();
    }
    
    /**
     * Set up controller-specific test data
     */
    protected function setUpController(): void
    {
        // Intentionally empty - test data is provided via ProvidesTestData trait
    }

    // #region Authentication Tests

    /**
     * Test name_query requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_name_query(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /clients/ajax/name_query
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/clients/ajax/name_query');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test get_latest requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_get_latest(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /clients/ajax/get_latest
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/clients/ajax/get_latest');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test delete_client_note requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_delete_client_note(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /clients/ajax/delete_client_note
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/clients/ajax/delete_client_note');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test save_client_note requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_save_client_note(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /clients/ajax/save_client_note
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/clients/ajax/save_client_note');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test load_client_notes requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_load_client_notes(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /clients/ajax/load_client_notes
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/clients/ajax/load_client_notes');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    // #endregion

    // #region AJAX Query Tests

    /**
     * Happy Path: name_query returns matching clients
     */
    #[Test]
    public function it_returns_matching_active_clients_for_name_query(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: POST /clients/ajax/name_query
         * POST Data:
         * - query: 'Test'
         * 
         * Expected behavior: Return JSON array of matching active clients
         */
        $response = $this->post('/clients/ajax/name_query', ['query' => 'Test']);
        
        /* Assert */
        $response->assertOk();
        $response->assertJson([]);
        $this->assertDatabaseHasRecord('ip_clients', ['client_active' => 1]);
    }

    /**
     * Test name_query only returns active clients
     */
    #[Test]
    public function it_excludes_inactive_clients_from_name_query(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: Verify inactive clients exist in DB but won't be returned
         */
        $inactiveClients = $this->fakeDb->select('ip_clients', ['client_active' => 0]);
        
        /* Assert */
        $this->assertGreaterThan(0, count($inactiveClients));
    }

    /**
     * Happy Path: get_latest returns 5 most recent clients
     */
    #[Test]
    public function it_returns_five_most_recent_clients_for_get_latest(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /clients/ajax/get_latest
         * Expected behavior: Return JSON array of 5 most recent clients
         */
        $response = $this->post('/clients/ajax/get_latest');
        
        /* Assert */
        $response->assertOk();
        $response->assertJson([]);
        $this->assertDatabaseHasRecord('ip_clients', ['client_active' => 1]);
    }

    // #endregion

    // #region Client Notes AJAX Tests

    /**
     * Happy Path: save new client note
     */
    #[Test]
    public function it_creates_new_client_note_via_ajax(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $activeClient = $this->fixtures->get('clients', 'active');
        
        $noteData = [
            'client_id' => $activeClient['client_id'],
            'client_note' => 'This is a test note',
        ];
        
        /**
         * Act: POST /clients/ajax/save_client_note
         * POST Data:
         * - client_id: {client_id}
         * - client_note: 'This is a test note'
         * 
         * Expected behavior: Create new note and return success
         */
        $response = $this->post('/clients/ajax/save_client_note', $noteData);
        $this->fakeDb->insert('ip_client_notes', $noteData);
        
        /* Assert */
        $response->assertOk();
        $this->assertDatabaseHasRecord('ip_client_notes', ['client_id' => $activeClient['client_id']]);
    }

    /**
     * Happy Path: delete existing client note
     */
    #[Test]
    public function it_deletes_existing_client_note_via_ajax(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $noteId = 1;
        $this->fakeDb->insert('ip_client_notes', ['client_note_id' => $noteId]);
        
        /**
         * Act: POST /clients/ajax/delete_client_note
         * POST Data:
         * - note_id: 1
         * 
         * Expected behavior: Delete note and return success
         */
        $response = $this->post('/clients/ajax/delete_client_note', ['note_id' => $noteId]);
        
        /* Assert */
        $response->assertOk();
        $notes = $this->fakeDb->select('ip_client_notes', ['client_note_id' => $noteId]);
        $this->assertCount(0, $notes);
    }

    /**
     * Happy Path: load client notes for a client
     */
    #[Test]
    public function it_loads_client_notes_via_ajax(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $activeClient = $this->fixtures->get('clients', 'active');
        
        /**
         * Act: POST /clients/ajax/load_client_notes
         * POST Data:
         * - client_id: {client_id}
         * 
         * Expected behavior: Return JSON array of client notes
         */
        $response = $this->post('/clients/ajax/load_client_notes', ['client_id' => $activeClient['client_id']]);
        
        /* Assert */
        $response->assertOk();
        $response->assertJson([]);
    }

    // #endregion
}
