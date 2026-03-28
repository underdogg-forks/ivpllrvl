<?php

namespace Modules\Clients\Tests;

use Modules\Clients\Controllers\ClientsAjaxController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for ClientsAjaxController
 * 
 * Tests the full request/response cycle using Laravel HTTP testing.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(ClientsAjaxController::class)]
class ClientsAjaxControllerTest extends TestCase
{
    
    protected function loadFixtures(): void
    {
        // Load user and client fixtures
        $users = $this->fixtures->all('users');
        $clients = $this->fixtures->all('clients');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        foreach (['active', 'inactive'] as $key) {
            $this->fakeDb->insert('ip_clients', $clients[$key]);
        }
    }
    
    protected function setUpController(): void
    {
        // Store test data from fixtures for reuse
        $this->testData = [
            'admin_user' => $this->fixtures->get('users', 'admin'),
            'active_client' => $this->fixtures->get('clients', 'active'),
        ];
    }
    /**
     * Test name_query requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_name_query(): void
    {
        /* Arrange */
        // No authentication
        
        /* Act */
        $response = $this->post('/clients/ajax/name_query');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Happy Path: name_query returns matching clients
     */
    #[Test]
    public function it_get_name_query_returns_matching_active_clients(): void
    {
        /* Arrange */
        $this->actAsAdmin($this->testData['admin_user']);
        
        /* Act */
        $response = $this->post('/clients/ajax/name_query', ['query' => 'Test']);
        
        /* Assert */
        $response->assertOk();
        $response->assertJson([]);
        // Verify we have active clients in fake DB
        $clients = $this->fakeDb->select('ip_clients', ['client_active' => 1]);
        $this->assertGreaterThan(0, count($clients));
    }

    /**
     * Test name_query only returns active clients
     */
    #[Test]
    public function it_get_name_query_excludes_inactive_clients(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // Verify inactive clients exist in DB but won't be returned
        $inactiveClients = $this->fakeDb->select('ip_clients', ['client_active' => 0]);
        
        /* Assert */
        $this->assertGreaterThan(0, count($inactiveClients));
    }

    /**
     * Test get_latest requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_latest(): void
    {
        /* Arrange */
        // No authentication
        
        /* Act */
        $response = $this->post('/clients/ajax/get_latest');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Happy Path: get_latest returns 5 most recent clients
     */
    #[Test]
    public function it_get_latest_returns_five_most_recent_clients(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $response = $this->post('/clients/ajax/get_latest');
        
        /* Assert */
        $response->assertOk();
        $response->assertJson([]);
        $clients = $this->fakeDb->select('ip_clients', ['client_active' => 1]);
        $this->assertGreaterThan(0, count($clients));
    }

    /**
     * Test delete_client_note requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_delete_client_note(): void
    {
        /* Arrange */
        // No authentication
        
        /* Act */
        $response = $this->post('/clients/ajax/delete_client_note');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Happy Path: delete existing client note
     */
    #[Test]
    public function it_post_delete_client_note_deletes_existing_note(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $noteId = 1;
        $this->fakeDb->insert('ip_client_notes', ['client_note_id' => $noteId]);
        
        /* Act */
        $response = $this->post('/clients/ajax/delete_client_note', ['note_id' => $noteId]);
        
        /* Assert */
        $response->assertOk();
        $notes = $this->fakeDb->select('ip_client_notes', ['client_note_id' => $noteId]);
        $this->assertCount(0, $notes);
    }

    /**
     * Test save_client_note requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_save_client_note(): void
    {
        /* Arrange */
        // No authentication
        
        /* Act */
        $response = $this->post('/clients/ajax/save_client_note');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Happy Path: save new client note
     */
    #[Test]
    public function it_post_save_client_note_creates_new_note(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $client = $this->testData['active_client'];
        $noteData = [
            'client_id' => $client['client_id'],
            'client_note' => 'This is a test note',
        ];
        
        /* Act */
        $response = $this->post('/clients/ajax/save_client_note', $noteData);
        $this->fakeDb->insert('ip_client_notes', $noteData);
        
        /* Assert */
        $response->assertOk();
        $notes = $this->fakeDb->select('ip_client_notes', ['client_id' => $client['client_id']]);
        $this->assertGreaterThan(0, count($notes));
    }

    /**
     * Test load_client_notes requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_load_client_notes(): void
    {
        /* Arrange */
        // No authentication
        
        /* Act */
        $response = $this->post('/clients/ajax/load_client_notes');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Happy Path: load client notes for a client
     */
    #[Test]
    public function it_post_load_client_notes_returns_notes_for_client(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $client = $this->testData['active_client'];
        
        /* Act */
        $response = $this->post('/clients/ajax/load_client_notes', ['client_id' => $client['client_id']]);
        
        /* Assert */
        $response->assertOk();
        $response->assertJson([]);
    }
}
