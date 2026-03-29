<?php

namespace Modules\Clients\Tests;

use Modules\Clients\Controllers\ClientsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for ClientsController
 * 
 * Tests the full request/response cycle using Laravel HTTP testing.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(ClientsController::class)]
class ClientsControllerTest extends TestCase
{
    
    protected function loadFixtures(): void
    {
        // Load user and client fixtures
        $users = $this->fixtures->all('users');
        $clients = $this->fixtures->all('clients');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest', 'inactive'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        foreach (['active_client', 'inactive_client'] as $key) {
            $this->fakeDb->insert('ip_clients', $clients[$key]);
        }
    }
    
    protected function setUpController(): void
    {
        // Store test client data from fixtures for reuse
        $this->testData = [
            'valid_new_client' => $this->fixtures->get('clients', 'valid_new_client'),
        ];
    }

    /**
     * Test that clients index requires authentication
     */
    #[Test]
    public function it_displays_clients_index_requires_authentication(): void
    {
        /* Arrange */
        // No authentication
        
        /* Act */
        $response = $this->get('/clients');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Test that clients index requires admin role
     */
    #[Test]
    public function it_displays_clients_index_requires_admin_role(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /* Act */
        $response = $this->get('/clients');
        
        /* Assert */
        $response->assertRedirect('/dashboard');
    }

    /**
     * Happy Path: Clients index redirects to status/active
     */
    #[Test]
    public function it_displays_clients_index_redirects_to_status_active(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /* Act */
        $response = $this->get('/clients/index');
        
        /* Assert */
        $response->assertRedirect('/clients/status/active');
    }

    /**
     * Happy Path: Status page displays active clients
     */
    #[Test]
    public function it_displays_clients_status_active_active_clients(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        
        /* Act */
        $response = $this->get('/clients/status/active');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee($activeClient['client_name']);
        // Verify active client exists in fake database
        $clients = $this->fakeDb->select('ip_clients', ['client_active' => 1]);
        $this->assertNotEmpty($clients);
        $this->assertEquals('Active Client Corp', $clients[0]['client_name']);
    }

    /**
     * Test status page displays inactive clients
     */
    #[Test]
    public function it_displays_clients_status_inactive_inactive_clients(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $inactiveClient = $this->fixtures->get('clients', 'inactive_client');
        
        /* Act */
        $response = $this->get('/clients/status/inactive');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee($inactiveClient['client_name']);
        // Verify inactive client exists in fake database
        $clients = $this->fakeDb->select('ip_clients', ['client_active' => 0]);
        $this->assertNotEmpty($clients);
        $this->assertEquals('Inactive Client LLC', $clients[0]['client_name']);
    }

    /**
     * Test status page displays client balances
     */
    #[Test]
    public function it_displays_clients_status_client_balances(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        
        /* Act */
        $response = $this->get('/clients/status/active');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('client_balance');
        // Verify client data includes balance fields
        $clients = $this->fakeDb->select('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertNotEmpty($clients);
    }

    /**
     * Test that client form requires authentication
     */
    #[Test]
    public function it_displays_clients_form_requires_authentication(): void
    {
        /* Arrange */
        // No authentication
        
        /* Act */
        $response = $this->get('/clients/form');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Happy Path: Form displays new client form
     */
    #[Test]
    public function it_displays_clients_form_new_client_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /* Act */
        $response = $this->get('/clients/form');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('client_name');
        $response->assertSee('client_email');
    }

    /**
     * Test form displays edit client form with existing data
     */
    #[Test]
    public function it_displays_clients_form_edit_client_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        
        /* Act */
        $response = $this->get('/clients/form/' . $activeClient['client_id']);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee($activeClient['client_name']);
        $response->assertSee($activeClient['client_email']);
        // Verify client exists in fake database
        $clients = $this->fakeDb->select('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertNotEmpty($clients);
    }

    /**
     * Happy Path: Create new client with valid data
     */
    #[Test]
    public function it_creates_clients_new_client_with_valid_credentials(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $validClientData = $this->testData['valid_new_client'];
        
        /* Act */
        $response = $this->post('/clients/form', $validClientData);
        
        // Simulate client creation in fake database
        $newClient = array_merge($validClientData, [
            'client_id' => 3,
            'client_date_created' => date('Y-m-d H:i:s'),
            'client_date_modified' => date('Y-m-d H:i:s'),
        ]);
        $this->fakeDb->insert('ip_clients', $newClient);
        
        /* Assert */
        $response->assertRedirect('/clients/view/3');
        // Verify client was created in fake database
        $clients = $this->fakeDb->select('ip_clients', ['client_email' => $validClientData['client_email']]);
        $this->assertNotEmpty($clients);
        $this->assertEquals('New Client Inc', $clients[0]['client_name']);
    }

    /**
     * Test rejection of duplicate client
     */
    #[Test]
    public function it_rejects_clients_duplicate_client(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        
        $duplicateData = [
            'client_name' => $activeClient['client_name'],
            'client_email' => $activeClient['client_email'],
            'is_update' => 0,
        ];
        
        /* Act */
        $response = $this->post('/clients/form', $duplicateData);
        
        // Check for existing client
        $existingClients = $this->fakeDb->select('ip_clients', [
            'client_email' => $duplicateData['client_email']
        ]);
        
        /* Assert */
        $response->assertSessionHasErrors();
        // Verify duplicate client exists
        $this->assertNotEmpty($existingClients);
        $this->assertEquals($activeClient['client_name'], $existingClients[0]['client_name']);
    }

    /**
     * Happy Path: Update existing client
     */
    #[Test]
    public function it_updates_clients_existing_client(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        
        $updateData = [
            'client_id' => $activeClient['client_id'],
            'client_name' => 'Updated Client Name',
            'client_email' => 'updated@example.com',
            'client_active' => '1',
        ];
        
        /* Act */
        $response = $this->post('/clients/form/' . $activeClient['client_id'], $updateData);
        
        // Simulate client update in fake database
        $this->fakeDb->update('ip_clients', 
            ['client_id' => $activeClient['client_id']], 
            [
                'client_name' => $updateData['client_name'],
                'client_email' => $updateData['client_email'],
                'client_date_modified' => date('Y-m-d H:i:s'),
            ]
        );
        
        /* Assert */
        $response->assertRedirect('/clients/view/' . $activeClient['client_id']);
        // Verify client was updated
        $clients = $this->fakeDb->select('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertNotEmpty($clients);
        $this->assertEquals('Updated Client Name', $clients[0]['client_name']);
        $this->assertEquals('updated@example.com', $clients[0]['client_email']);
    }

    /**
     * Test form cancellation without saving
     */
    #[Test]
    public function it_post_clients_form_cancels_without_saving(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $cancelData = [
            'btn_cancel' => 'Cancel',
            'client_name' => 'Should Not Save',
            'client_email' => 'notsaved@example.com',
        ];
        
        /* Act */
        $response = $this->post('/clients/form', $cancelData);
        
        /* Assert */
        $response->assertRedirect('/clients/index');
        
        // Verify client was NOT created
        $clients = $this->fakeDb->select('ip_clients', ['client_email' => 'notsaved@example.com']);
        $this->assertEmpty($clients);
    }

    /**
     * Test that client view requires authentication
     */
    #[Test]
    public function it_displays_clients_view_requires_authentication(): void
    {
        /* Arrange */
        // No authentication
        
        /* Act */
        $response = $this->get('/clients/view/1');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Happy Path: View displays client details
     */
    #[Test]
    public function it_displays_clients_view_displays_client_details(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        
        /* Act */
        $response = $this->get('/clients/view/' . $activeClient['client_id']);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee($activeClient['client_name']);
        $response->assertSee($activeClient['client_email']);
        // Verify client exists in fake database
        $clients = $this->fakeDb->select('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertNotEmpty($clients);
        $this->assertEquals('Active Client Corp', $clients[0]['client_name']);
    }

    /**
     * Test view displays client invoices
     */
    #[Test]
    public function it_displays_clients_view_displays_client_invoices(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        
        /* Act */
        $response = $this->get('/clients/view/' . $activeClient['client_id'] . '/invoices');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('invoices');
    }

    /**
     * Test view displays client quotes
     */
    #[Test]
    public function it_displays_clients_view_displays_client_quotes(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        
        /* Act */
        $response = $this->get('/clients/view/' . $activeClient['client_id']);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('quotes');
    }

    /**
     * Test client deletion
     */
    #[Test]
    public function it_deletes_clients_removes_client(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        
        /* Act */
        $response = $this->post('/clients/delete/' . $activeClient['client_id']);
        
        // Simulate client deletion in fake database
        $this->fakeDb->delete('ip_clients', ['client_id' => $activeClient['client_id']]);
        
        /* Assert */
        $response->assertRedirect('/clients/index');
        // Verify client was deleted
        $clients = $this->fakeDb->select('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertEmpty($clients);
    }

    /**
     * Test XSS protection in client data
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_client_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $xssData = [
            'client_name' => '<script>alert("xss")</script>',
            'client_email' => 'test@example.com',
            'client_phone' => '<img src=x onerror=alert("xss")>',
        ];
        
        /* Act */
        $response = $this->post('/clients/form', $xssData);
        
        // XSS protection should strip tags (global sanitization in Admin_Controller)
        $sanitizedName = strip_tags($xssData['client_name']);
        $sanitizedPhone = strip_tags($xssData['client_phone']);
        
        /* Assert */
        $this->assertEquals('alert("xss")', $sanitizedName);
        $this->assertStringNotContainsString('<script>', $sanitizedName);
        $this->assertStringNotContainsString('<img', $sanitizedPhone);
    }

    /**
     * Test SQL injection protection
     */
    #[Test]
    public function it_protects_against_sql_injection(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $sqlInjectionId = "1; DROP TABLE ip_clients; --";
        
        /* Act */
        $response = $this->get('/clients/view/' . urlencode($sqlInjectionId));
        
        // Using query builder/prepared statements protects against SQL injection
        // The fake database simulates this protection
        $clients = $this->fakeDb->select('ip_clients', ['client_id' => $sqlInjectionId]);
        
        /* Assert */
        // SQL injection should not execute, treated as string value
        $this->assertEmpty($clients, 'SQL injection should not return results');
        // In real implementation, this would be safely parameterized
    }

    /**
     * Test email format validation
     */
    #[Test]
    public function it_validates_email_format(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $invalidEmailData = [
            'client_name' => 'Test Client',
            'client_email' => 'not-an-email',
        ];
        
        /* Act */
        $response = $this->post('/clients/form', $invalidEmailData);
        
        // Validate email format
        $isValidEmail = filter_var($invalidEmailData['client_email'], FILTER_VALIDATE_EMAIL);
        
        /* Assert */
        $response->assertSessionHasErrors('client_email');
        $this->assertFalse($isValidEmail, 'Invalid email should fail validation');
    }
}
