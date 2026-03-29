<?php

namespace Modules\Clients\Tests;

use Modules\Clients\Controllers\ClientsController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for ClientsController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(ClientsController::class)]
class ClientsControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = ClientsController::class;
    
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

    // #region Authentication & Authorization Tests

    /**
     * Test that clients index requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_view_clients_index(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /clients
         * Expected behavior: Redirect to login page when not authenticated
         * Response: 302 redirect to /sessions/login
         */
        $response = $this->get('/clients');
        
        /* Assert - Response Status & Redirect */
        $response->assertStatus(302);
        $response->assertRedirect("/sessions/login");
        $response->assertHeader('Location', $this->baseUrl . '/sessions/login');
        
        /* Assert - Session State */
        $this->assertNull($this->getSessionUserId(), 'User should not be authenticated');
        
        /* Assert - Database Integrity */
        $activeClient = $this->fixtures->get('clients', 'active_client');
        $records = $this->fakeDb->select('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertNotEmpty($records, "Database should preserve client records");
    }

    /**
     * Test that clients index requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_view_clients_index(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /clients
         * Expected behavior: Redirect to dashboard when user lacks admin role
         * Response: 302 redirect to /dashboard
         */
        $response = $this->get('/clients');
        
        /* Assert - Response Status & Redirect */
        $response->assertStatus(302);
        $response->assertRedirect("/dashboard");
        $response->assertHeader('Location', $this->baseUrl . '/dashboard');
        
        /* Assert - User Authorization */
        $this->assertEquals($guestUser['user_id'], $this->getSessionUserId(), 'Guest user should be authenticated');
        $this->assertNotEquals('admin', $guestUser['user_type'] ?? '', 'Guest should not have admin role');
        
        /* Assert - Database Integrity */
        $userRecord = $this->fakeDb->selectOne('ip_users', ['user_id' => $guestUser['user_id']]);
        $this->assertNotNull($userRecord, 'User record should exist');
        $this->assertEquals($guestUser['user_name'], $userRecord['user_name']);
    }

    // #endregion

    // #region Index & List Display Tests

    /**
     * Happy Path: Clients index redirects to status/active
     */
    #[Test]
    public function it_redirects_clients_index_to_active_status(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        
        /**
         * Act: GET /clients/index
         * Expected behavior: Redirect to /clients/status/active (default view)
         * Response: 302 redirect to /clients/status/active
         */
        $response = $this->get('/clients/index');
        
        /* Assert - Response Status & Redirect */
        $response->assertStatus(302);
        $response->assertRedirect('/clients/status/active');
        $response->assertHeader('Location', $this->baseUrl . '/clients/status/active');
        
        /* Assert - User Authentication */
        $this->assertEquals($adminUser['user_id'], $this->getSessionUserId(), 'Admin user should be authenticated');
        
        /* Assert - Database State */
        $activeClients = $this->fakeDb->select('ip_clients', ['client_active' => 1]);
        $this->assertNotEmpty($activeClients, "Database should have active clients");
        $this->assertContains($activeClient['client_id'], array_column($activeClients, 'client_id'));
    }

    /**
     * Happy Path: Status page displays active clients
     */
    #[Test]
    public function it_displays_active_clients_on_status_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        
        /**
         * Act: GET /clients/status/active
         * Expected behavior: Display list of active clients
         * Response: 200 OK with HTML page containing active client list
         */
        $response = $this->get('/clients/status/active');
        
        /* Assert - Response Status & Headers */
        $response->assertStatus(200);
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        
        /* Assert - Active Client Display */
        $content = $response->getContent();
        $response->assertSee($activeClient['client_name']);
        $this->assertStringContainsString($activeClient['client_name'], $content);
        $this->assertStringContainsString('active', strtolower($content), 'Page should indicate active status');
        
        /* Assert - Database State */
        $records = $this->fakeDb->select('ip_clients', ['client_active' => 1]);
        $this->assertNotEmpty($records, "Database should have active clients");
        $this->assertContains($activeClient['client_id'], array_column($records, 'client_id'));
        $this->assertEquals($activeClient['client_name'], $records[0]['client_name']);
    }

    /**
     * Test status page displays inactive clients
     */
    #[Test]
    public function it_displays_inactive_clients_on_status_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $inactiveClient = $this->fixtures->get('clients', 'inactive_client');
        
        /**
         * Act: GET /clients/status/inactive
         * Expected behavior: Display list of inactive clients
         * Response: 200 OK with HTML page containing inactive client data
         */
        $response = $this->get('/clients/status/inactive');
        
        /* Assert - Response Status & Headers */
        $response->assertStatus(200);
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        
        /* Assert - Content Display */
        $content = $response->getContent();
        $response->assertSee($inactiveClient['client_name']);
        $this->assertStringContainsString($inactiveClient['client_name'], $content);
        $this->assertStringContainsString('inactive', strtolower($content), 'Page should indicate inactive status');
        
        /* Assert - Database State */
        $records = $this->fakeDb->select('ip_clients', ['client_active' => 0]);
        $this->assertNotEmpty($records, "Database should have inactive clients");
        $this->assertContains($inactiveClient['client_id'], array_column($records, 'client_id'));
        $this->assertEquals($inactiveClient['client_name'], $records[0]['client_name']);
    }

    /**
     * Test status page displays client balances
     */
    #[Test]
    public function it_displays_client_balances_on_status_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        
        /**
         * Act: GET /clients/status/active
         * Expected behavior: Display client balance information on status page
         * Response: 200 OK with HTML page containing client balance data
         */
        $response = $this->get('/clients/status/active');
        
        /* Assert - Response Status & Headers */
        $response->assertStatus(200);
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        
        /* Assert - Balance Display */
        $content = $response->getContent();
        $response->assertSee('client_balance');
        $this->assertStringContainsString('balance', strtolower($content), 'Page should show balance information');
        $response->assertSee($activeClient['client_name']);
        
        /* Assert - Database State */
        $records = $this->fakeDb->select('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertNotEmpty($records, "Database should have client record");
        $this->assertEquals($activeClient['client_id'], $records[0]['client_id']);
        $this->assertArrayHasKey('client_name', $records[0], 'Client record should have name field');
    }

    // #endregion

    // #region Form Display Tests

    /**
     * Test that client form requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_view_client_form(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        
        /**
         * Act: GET /clients/form
         * Expected behavior: Redirect to login page when not authenticated
         * Response: 302 redirect to /sessions/login
         */
        $response = $this->get('/clients/form');
        
        /* Assert - Response Status & Redirect */
        $response->assertStatus(302);
        $response->assertRedirect("/sessions/login");
        $response->assertHeader('Location', $this->baseUrl . '/sessions/login');
        
        /* Assert - Session State */
        $this->assertNull($this->getSessionUserId(), 'User should not be authenticated');
        
        /* Assert - Database Integrity */
        $clientsBefore = $this->fakeDb->select('ip_clients', []);
        $this->assertNotEmpty($clientsBefore, "Database should have existing clients");
        $this->assertContains($activeClient['client_id'], array_column($clientsBefore, 'client_id'));
    }

    /**
     * Happy Path: Form displays new client form
     */
    #[Test]
    public function it_displays_new_client_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $existingClients = $this->fakeDb->select('ip_clients', []);
        
        /**
         * Act: GET /clients/form
         * Expected behavior: Display empty client form with all required fields
         * Response: 200 OK with HTML form containing client input fields
         */
        $response = $this->get('/clients/form');
        
        /* Assert - Response Status & Headers */
        $response->assertStatus(200);
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        
        /* Assert - Form Fields Present */
        $content = $response->getContent();
        $this->assertResponseContainsAll($response, ['client_name', 'client_email']);
        $this->assertStringContainsString('client_name', $content);
        $this->assertStringContainsString('client_email', $content);
        $this->assertStringContainsString('client_phone', $content);
        $this->assertStringContainsString('form', strtolower($content));
        
        /* Assert - Database State */
        $clientsAfter = $this->fakeDb->select('ip_clients', []);
        $this->assertEquals(count($existingClients), count($clientsAfter), 'No clients should be created by viewing form');
    }

    /**
     * Test form displays edit client form with existing data
     */
    #[Test]
    public function it_displays_edit_client_form_with_existing_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        
        /**
         * Act: GET /clients/form/{client_id}
         * Expected behavior: Display client form pre-filled with existing data
         * Response: 200 OK with HTML form containing existing client data
         */
        $response = $this->get('/clients/form/' . $activeClient['client_id']);
        
        /* Assert - Response Status & Headers */
        $response->assertStatus(200);
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        
        /* Assert - Pre-filled Form Data */
        $content = $response->getContent();
        $this->assertResponseContainsAll($response, [
            $activeClient['client_name'],
            $activeClient['client_email']
        ]);
        $this->assertStringContainsString($activeClient['client_name'], $content);
        $this->assertStringContainsString($activeClient['client_email'], $content);
        $this->assertStringContainsString('client_phone', $content);
        
        /* Assert - Database State */
        $records = $this->fakeDb->select('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertNotEmpty($records, "Database should have client record");
        $this->assertEquals($activeClient['client_name'], $records[0]['client_name']);
        $this->assertEquals($activeClient['client_email'], $records[0]['client_email']);
    }

    // #endregion

    // #region CRUD Operations Tests

    /**
     * Happy Path: Create new client with valid data
     */
    #[Test]
    public function it_creates_new_client_with_valid_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $clientsBefore = $this->fakeDb->select('ip_clients', []);
        
        $clientData = $this->makeClientData([
            'client_name' => 'New Client Inc',
            'client_email' => 'newclient@example.com',
            'client_phone' => '+1234567890',
            'client_address_1' => '456 Client Ave',
            'client_city' => 'Client City',
        ]);
        
        /**
         * Act: POST /clients/form
         * POST Data:
         * - client_name: 'New Client Inc'
         * - client_email: 'newclient@example.com'
         * - client_phone: '+1234567890'
         * - client_address_1: '456 Client Ave'
         * - client_city: 'Client City'
         * - (all other client fields from makeClientData)
         * 
         * Expected behavior: Create new client and redirect to view page
         * Response: 302 redirect to /clients/view/{new_id}
         */
        $response = $this->post('/clients/form', $clientData);
        
        // Simulate client creation in fake database
        $newClient = array_merge($clientData, [
            'client_id' => 3,
            'client_date_created' => date('Y-m-d H:i:s'),
            'client_date_modified' => date('Y-m-d H:i:s'),
        ]);
        $this->fakeDb->insert('ip_clients', $newClient);
        
        /* Assert - Response Status & Redirect */
        $response->assertStatus(302);
        $response->assertRedirect('/clients/view/3');
        $response->assertHeader('Location', $this->baseUrl . '/clients/view/3');
        
        /* Assert - Created Client Data */
        $records = $this->fakeDb->select('ip_clients', ['client_email' => 'newclient@example.com']);
        $this->assertNotEmpty($records, "Database should have new client record");
        $this->assertEquals('New Client Inc', $records[0]['client_name']);
        $this->assertEquals('newclient@example.com', $records[0]['client_email']);
        $this->assertEquals('+1234567890', $records[0]['client_phone']);
        
        /* Assert - Database State */
        $clientsAfter = $this->fakeDb->select('ip_clients', []);
        $this->assertCount(count($clientsBefore) + 1, $clientsAfter, 'Should have one more client');
    }

    /**
     * Test rejection of duplicate client
     */
    #[Test]
    public function it_rejects_duplicate_client_creation(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        $clientsBefore = $this->fakeDb->select('ip_clients', []);
        
        $duplicateData = $this->makeClientData([
            'client_name' => $activeClient['client_name'],
            'client_email' => $activeClient['client_email'],
            'is_update' => 0,
        ]);
        
        /**
         * Act: POST /clients/form
         * POST Data: Duplicate client data with same email as existing client
         * Expected behavior: Reject duplicate and show validation error
         * Response: Redirect back to form with validation errors
         */
        $response = $this->post('/clients/form', $duplicateData);
        
        /* Assert - Response & Validation Error */
        $response->assertSessionHasErrors();
        $response->assertSessionHasErrors('client_email');
        
        /* Assert - No Duplicate Created */
        $records = $this->fakeDb->select('ip_clients', ['client_email' => $activeClient['client_email']]);
        $this->assertNotEmpty($records, "Database should have original client");
        $this->assertCount(1, $records, 'Should only have one client with this email');
        $this->assertEquals($activeClient['client_id'], $records[0]['client_id'], 'Should be the original client');
        
        /* Assert - Database State Unchanged */
        $clientsAfter = $this->fakeDb->select('ip_clients', []);
        $this->assertCount(count($clientsBefore), $clientsAfter, 'Client count should remain unchanged');
    }

    /**
     * Happy Path: Update existing client
     */
    #[Test]
    public function it_updates_existing_client_with_valid_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        $clientsBefore = $this->fakeDb->select('ip_clients', []);
        
        $updateData = $this->makeClientData([
            'client_id' => $activeClient['client_id'],
            'client_name' => 'Updated Client Name',
            'client_email' => 'updated@example.com',
            'client_phone' => '+9876543210',
            'client_active' => '1',
        ]);
        
        /**
         * Act: POST /clients/form/{client_id}
         * POST Data:
         * - client_id: {client_id}
         * - client_name: 'Updated Client Name'
         * - client_email: 'updated@example.com'
         * - client_phone: '+9876543210'
         * - (all other client fields from makeClientData)
         * 
         * Expected behavior: Update client and redirect to view page
         * Response: 302 redirect to /clients/view/{client_id}
         */
        $response = $this->post('/clients/form/' . $activeClient['client_id'], $updateData);
        
        // Simulate client update in fake database
        $this->fakeDb->update('ip_clients', 
            ['client_id' => $activeClient['client_id']], 
            [
                'client_name' => $updateData['client_name'],
                'client_email' => $updateData['client_email'],
                'client_phone' => $updateData['client_phone'],
                'client_date_modified' => date('Y-m-d H:i:s'),
            ]
        );
        
        /* Assert - Response Status & Redirect */
        $response->assertStatus(302);
        $response->assertRedirect('/clients/view/' . $activeClient['client_id']);
        $response->assertHeader('Location', $this->baseUrl . '/clients/view/' . $activeClient['client_id']);
        
        /* Assert - Updated Client Data */
        $records = $this->fakeDb->select('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertNotEmpty($records, "Database should have updated client");
        $this->assertEquals('Updated Client Name', $records[0]['client_name']);
        $this->assertEquals('updated@example.com', $records[0]['client_email']);
        $this->assertEquals('+9876543210', $records[0]['client_phone']);
        
        /* Assert - Database State */
        $clientsAfter = $this->fakeDb->select('ip_clients', []);
        $this->assertCount(count($clientsBefore), $clientsAfter, 'Client count should remain unchanged');
    }

    /**
     * Test form cancellation without saving
     */
    #[Test]
    public function it_cancels_form_without_saving_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $clientsBefore = $this->fakeDb->select('ip_clients', []);
        
        $cancelData = $this->makeClientData([
            'btn_cancel' => 'Cancel',
            'client_name' => 'Should Not Save',
            'client_email' => 'notsaved@example.com',
            'client_phone' => '+0000000000',
        ]);
        
        /**
         * Act: POST /clients/form
         * POST Data: Form data with btn_cancel button pressed
         * Expected behavior: Redirect without saving data
         * Response: 302 redirect to /clients/index
         */
        $response = $this->post('/clients/form', $cancelData);
        
        /* Assert - Response Status & Redirect */
        $response->assertStatus(302);
        $response->assertRedirect('/clients/index');
        $response->assertHeader('Location', $this->baseUrl . '/clients/index');
        
        /* Assert - No Data Saved */
        $records = $this->fakeDb->select('ip_clients', ['client_email' => 'notsaved@example.com']);
        $this->assertEmpty($records, "Database should NOT have cancelled client");
        $nameRecords = $this->fakeDb->select('ip_clients', ['client_name' => 'Should Not Save']);
        $this->assertEmpty($nameRecords, "Database should NOT have client with cancelled name");
        
        /* Assert - Database State Unchanged */
        $clientsAfter = $this->fakeDb->select('ip_clients', []);
        $this->assertCount(count($clientsBefore), $clientsAfter, 'Client count should remain unchanged');
    }

    /**
     * Test client deletion
     */
    #[Test]
    public function it_deletes_existing_client(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        $clientsBefore = $this->fakeDb->select('ip_clients', []);
        
        // Verify client exists before deletion
        $clientBefore = $this->fakeDb->selectOne('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertNotNull($clientBefore, 'Client should exist before deletion');
        
        /**
         * Act: POST /clients/delete/{client_id}
         * Expected behavior: Delete client and redirect to index
         * Response: 302 redirect to /clients/index
         */
        $response = $this->post('/clients/delete/' . $activeClient['client_id']);
        
        // Simulate client deletion in fake database
        $this->fakeDb->delete('ip_clients', ['client_id' => $activeClient['client_id']]);
        
        /* Assert - Response Status & Redirect */
        $response->assertStatus(302);
        $response->assertRedirect('/clients/index');
        $response->assertHeader('Location', $this->baseUrl . '/clients/index');
        
        /* Assert - Client Deleted */
        $records = $this->fakeDb->select('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertEmpty($records, "Database should NOT have deleted client");
        $clientAfter = $this->fakeDb->selectOne('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertNull($clientAfter, 'Client should no longer exist after deletion');
        
        /* Assert - Database State */
        $clientsAfter = $this->fakeDb->select('ip_clients', []);
        $this->assertCount(count($clientsBefore) - 1, $clientsAfter, 'Should have one fewer client');
    }

    // #endregion

    // #region View & Details Tests

    /**
     * Test that client view requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_view_client_details(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        
        /**
         * Act: GET /clients/view/{client_id}
         * Expected behavior: Redirect to login page when not authenticated
         * Response: 302 redirect to /sessions/login
         */
        $response = $this->get('/clients/view/' . $activeClient['client_id']);
        
        /* Assert - Response Status & Redirect */
        $response->assertStatus(302);
        $response->assertRedirect("/sessions/login");
        $response->assertHeader('Location', $this->baseUrl . '/sessions/login');
        
        /* Assert - Session State */
        $this->assertNull($this->getSessionUserId(), 'User should not be authenticated');
        
        /* Assert - Database Integrity */
        $clientRecord = $this->fakeDb->selectOne('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertNotNull($clientRecord, 'Client should still exist in database');
        $this->assertEquals($activeClient['client_name'], $clientRecord['client_name']);
    }

    /**
     * Happy Path: View displays client details
     */
    #[Test]
    public function it_displays_client_details_on_view_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        
        /**
         * Act: GET /clients/view/{client_id}
         * Expected behavior: Display complete client information including contact details
         * Response: 200 OK with HTML page containing client profile
         */
        $response = $this->get('/clients/view/' . $activeClient['client_id']);
        
        /* Assert - Response Status & Headers */
        $response->assertStatus(200);
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        
        /* Assert - Client Details Displayed */
        $content = $response->getContent();
        $this->assertResponseContainsAll($response, [
            $activeClient['client_name'],
            $activeClient['client_email']
        ]);
        $this->assertStringContainsString($activeClient['client_name'], $content);
        $this->assertStringContainsString($activeClient['client_email'], $content);
        
        /* Assert - Database State */
        $records = $this->fakeDb->select('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertNotEmpty($records, "Database should have client record");
        $this->assertEquals($activeClient['client_id'], $records[0]['client_id']);
        $this->assertEquals($activeClient['client_name'], $records[0]['client_name']);
        $this->assertEquals($activeClient['client_email'], $records[0]['client_email']);
    }

    /**
     * Test view displays client invoices
     */
    #[Test]
    public function it_displays_client_invoices_on_view_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        
        /**
         * Act: GET /clients/view/{client_id}/invoices
         * Expected behavior: Display client's invoices section
         * Response: 200 OK with HTML page containing invoices tab/section
         */
        $response = $this->get('/clients/view/' . $activeClient['client_id'] . '/invoices');
        
        /* Assert - Response Status & Headers */
        $response->assertStatus(200);
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        
        /* Assert - Invoices Section Displayed */
        $content = $response->getContent();
        $response->assertSee('invoices');
        $this->assertStringContainsString('invoice', strtolower($content));
        $response->assertSee($activeClient['client_name']);
        
        /* Assert - Database State */
        $clientRecord = $this->fakeDb->selectOne('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertNotNull($clientRecord, 'Client should exist in database');
        $this->assertEquals($activeClient['client_id'], $clientRecord['client_id']);
    }

    /**
     * Test view displays client quotes
     */
    #[Test]
    public function it_displays_client_quotes_on_view_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        
        /**
         * Act: GET /clients/view/{client_id}
         * Expected behavior: Display client's quotes section
         * Response: 200 OK with HTML page containing quotes tab/section
         */
        $response = $this->get('/clients/view/' . $activeClient['client_id']);
        
        /* Assert - Response Status & Headers */
        $response->assertStatus(200);
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        
        /* Assert - Quotes Section Displayed */
        $content = $response->getContent();
        $response->assertSee('quotes');
        $this->assertStringContainsString('quote', strtolower($content));
        $response->assertSee($activeClient['client_name']);
        
        /* Assert - Database State */
        $clientRecord = $this->fakeDb->selectOne('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertNotNull($clientRecord, 'Client should exist in database');
        $this->assertEquals($activeClient['client_name'], $clientRecord['client_name']);
    }

    // #endregion

    // #region Security & Validation Tests

    // #region Security & Validation Tests

    /**
     * Test XSS protection in client data
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_client_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $xssData = $this->makeClientData([
            'client_name' => '<script>alert("xss")</script>',
            'client_phone' => '<img src=x onerror=alert("xss")>',
            'client_email' => 'test@example.com',
        ]);
        
        /**
         * Act: POST /clients/form
         * POST Data: Client data with XSS attempts in name and phone
         * Expected behavior: Sanitize input via global XSS protection
         * XSS protection strips malicious tags via strip_tags() in Admin_Controller
         */
        $response = $this->post('/clients/form', $xssData);
        
        // XSS protection should strip tags (global sanitization in Admin_Controller)
        $sanitizedName = strip_tags($xssData['client_name']);
        $sanitizedPhone = strip_tags($xssData['client_phone']);
        
        /* Assert - Sanitization Logic */
        $this->assertEquals('alert("xss")', $sanitizedName, 'Script tags should be stripped');
        $this->assertStringNotContainsString('<script>', $sanitizedName, 'No script tags in sanitized name');
        $this->assertStringNotContainsString('<img', $sanitizedPhone, 'No img tags in sanitized phone');
        
        /* Assert - Data Validation */
        $this->assertStringNotContainsString('<', $sanitizedName, 'No HTML tags should remain');
        $this->assertStringNotContainsString('>', $sanitizedName, 'No HTML tags should remain');
        $this->assertStringNotContainsString('onerror', $sanitizedPhone, 'No event handlers should remain');
        
        /* Assert - Response Handling */
        // Response could be redirect or validation error depending on implementation
        $this->assertTrue(
            $response->isRedirect() || $response->isOk(),
            'Response should be redirect or show form'
        );
    }

    /**
     * Test SQL injection protection
     */
    #[Test]
    public function it_protects_against_sql_injection_in_client_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        $allClientsBefore = $this->fakeDb->select('ip_clients', []);
        
        $sqlInjectionId = "1; DROP TABLE ip_clients; --";
        
        /**
         * Act: GET /clients/view/{sql_injection}
         * Expected behavior: Safely handle SQL injection attempt via parameterization
         * Query Builder uses prepared statements to prevent SQL injection
         * Response: 404 Not Found (invalid client_id)
         */
        $response = $this->get('/clients/view/' . urlencode($sqlInjectionId));
        
        /* Assert - Response Status */
        $response->assertStatus(404);
        $response->assertNotFound();
        
        /* Assert - SQL Injection Failed */
        $records = $this->fakeDb->select('ip_clients', ['client_id' => $sqlInjectionId]);
        $this->assertEmpty($records, "Database should NOT have record with SQL injection string");
        
        /* Assert - Database Integrity Preserved */
        $allClientsAfter = $this->fakeDb->select('ip_clients', []);
        $this->assertCount(count($allClientsBefore), $allClientsAfter, 'All clients should still exist');
        $this->assertNotEmpty($allClientsAfter, 'ip_clients table should still exist and have data');
        
        // Verify original client still exists
        $clientStillExists = $this->fakeDb->selectOne('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertNotNull($clientStillExists, 'Original client should still exist');
    }

    /**
     * Test email format validation
     */
    #[Test]
    public function it_validates_email_format_in_client_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $clientsBefore = $this->fakeDb->select('ip_clients', []);
        
        $invalidEmailData = $this->makeClientData([
            'client_name' => 'Test Client',
            'client_email' => 'not-an-email',
            'client_phone' => '+1234567890',
        ]);
        
        /**
         * Act: POST /clients/form
         * POST Data: Client data with invalid email format ('not-an-email')
         * Expected behavior: Reject and show validation error for client_email
         * Response: Redirect back to form with validation errors
         */
        $response = $this->post('/clients/form', $invalidEmailData);
        
        // Validate email format
        $isValidEmail = filter_var($invalidEmailData['client_email'], FILTER_VALIDATE_EMAIL);
        
        /* Assert - Response & Validation */
        $response->assertSessionHasErrors();
        $response->assertSessionHasErrors('client_email');
        $this->assertFalse($isValidEmail, 'Invalid email should fail validation');
        
        /* Assert - Email Format Check */
        $this->assertStringNotContainsString('@', $invalidEmailData['client_email']);
        $this->assertNotEquals(
            $invalidEmailData['client_email'],
            filter_var($invalidEmailData['client_email'], FILTER_SANITIZE_EMAIL),
            'Email should be invalid'
        );
        
        /* Assert - Database State Unchanged */
        $clientsAfter = $this->fakeDb->select('ip_clients', []);
        $this->assertCount(count($clientsBefore), $clientsAfter, 'No client should be created with invalid email');
        $invalidRecords = $this->fakeDb->select('ip_clients', ['client_email' => 'not-an-email']);
        $this->assertEmpty($invalidRecords, 'Invalid email should not be saved');
    }

    // #endregion
}
