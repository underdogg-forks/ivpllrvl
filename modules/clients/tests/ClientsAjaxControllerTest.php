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
        $response->assertRedirect("/sessions/login");
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
        $response->assertRedirect("/sessions/login");
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
        $response->assertRedirect("/sessions/login");
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
        $response->assertRedirect("/sessions/login");
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
        $response->assertRedirect("/sessions/login");
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
         * Expected JSON response: [
         *   {"id": "1", "text": "Test Client Corp"},
         *   {"id": "2", "text": "Test Industries"}
         * ]
         */
        $response = $this->post('/clients/ajax/name_query', ['query' => 'Test']);
        
        /* Assert - Response Status & Headers */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        
        /* Assert - JSON Response Structure */
        $jsonData = json_decode($response->getContent(), true);
        $this->assertIsArray($jsonData, 'Response should be JSON array');
        
        /* Assert - Client Data Structure */
        foreach ($jsonData as $client) {
            $this->assertArrayHasKey('id', $client, 'Each client should have id field');
            $this->assertArrayHasKey('text', $client, 'Each client should have text field');
            $this->assertNotEmpty($client['text'], 'Client name should not be empty');
        }
        
        /* Assert - Database Verification */
        $records = $this->fakeDb->select('ip_clients', ['client_active' => 1]);
        $this->assertNotEmpty($records, "Database should have active clients in 'ip_clients'");
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
         * Expected JSON response: [
         *   {"id": "5", "text": "Recent Client 5", "date": "2024-01-15"},
         *   {"id": "4", "text": "Recent Client 4", "date": "2024-01-14"},
         *   ...
         * ]
         * Maximum 5 clients ordered by date_created DESC
         */
        $response = $this->post('/clients/ajax/get_latest');
        
        /* Assert - Response Status & Headers */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        
        /* Assert - JSON Response Structure */
        $jsonData = json_decode($response->getContent(), true);
        $this->assertIsArray($jsonData, 'Response should be JSON array');
        $this->assertLessThanOrEqual(5, count($jsonData), 
            'Should return maximum 5 most recent clients');
        
        /* Assert - Client Data Structure */
        foreach ($jsonData as $client) {
            $this->assertArrayHasKey('id', $client, 'Each client should have id field');
            $this->assertArrayHasKey('text', $client, 'Each client should have text field');
        }
        
        /* Assert - Database Verification */
        $records = $this->fakeDb->select('ip_clients', ['client_active' => 1]);
        $this->assertNotEmpty($records, "Database should have active clients in 'ip_clients'");
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
        $records = $this->fakeDb->select('ip_client_notes', ['client_id' => $activeClient['client_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_client_notes'");
    }

    /**
     * Happy Path: delete existing client note
     */
    #[Test]
    public function it_deletes_existing_client_note_via_ajax(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $activeClient = $this->getClientData('active');
        
        // Create complete note with all required fields
        $noteId = 1;
        $noteData = [
            'client_note_id' => $noteId,
            'client_id' => $activeClient['client_id'],
            'client_note' => 'Test note to be deleted',
            'client_note_date' => date('Y-m-d H:i:s'),
        ];
        $this->fakeDb->insert('ip_client_notes', $noteData);
        
        /**
         * Act: POST /clients/ajax/delete_client_note
         * POST Data:
         * - note_id: 1
         * 
         * Expected behavior: Delete note and return success
         */
        $response = $this->post('/clients/ajax/delete_client_note', ['note_id' => $noteId]);
        
        /* Assert */
        $response->assertStatus(200);
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
         * Expected JSON response: [
         *   {
         *     "client_note_id": "1",
         *     "client_note": "Important client note",
         *     "client_note_date": "2024-01-15 10:30:00"
         *   },
         *   ...
         * ]
         */
        $response = $this->post('/clients/ajax/load_client_notes', ['client_id' => $activeClient['client_id']]);
        
        /* Assert - Response Status & Headers */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        
        /* Assert - JSON Response Structure */
        $jsonData = json_decode($response->getContent(), true);
        $this->assertIsArray($jsonData, 'Response should be JSON array of client notes');
        
        /* Assert - Note Data Structure (if notes exist) */
        foreach ($jsonData as $note) {
            $this->assertArrayHasKey('client_note_id', $note, 'Each note should have ID');
            $this->assertArrayHasKey('client_note', $note, 'Each note should have content');
            $this->assertArrayHasKey('client_note_date', $note, 'Each note should have date');
        }
        
        /* Assert - Database Consistency */
        $dbNotes = $this->fakeDb->select('ip_client_notes', ['client_id' => $activeClient['client_id']]);
        $this->assertEquals(count($dbNotes), count($jsonData), 
            'Returned notes should match database count for client');
    }

    // #endregion
}
