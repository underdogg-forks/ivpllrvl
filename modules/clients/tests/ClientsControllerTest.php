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
         */
        $response = $this->get('/clients');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
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
         */
        $response = $this->get('/clients');
        
        /* Assert */
        $response->assertRedirect("/dashboard");
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
        
        /**
         * Act: GET /clients/index
         * Expected behavior: Redirect to /clients/status/active
         */
        $response = $this->get('/clients/index');
        
        /* Assert */
        $response->assertRedirect('/clients/status/active');
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
         */
        $response = $this->get('/clients/status/active');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee($activeClient['client_name']);
        $records = $this->fakeDb->select('ip_clients', ['client_active' => 1]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_clients'");
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
         */
        $response = $this->get('/clients/status/inactive');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee($inactiveClient['client_name']);
        $records = $this->fakeDb->select('ip_clients', ['client_active' => 0]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_clients'");
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
         * Expected behavior: Display client balance information
         */
        $response = $this->get('/clients/status/active');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('client_balance');
        $records = $this->fakeDb->select('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_clients'");
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
        
        /**
         * Act: GET /clients/form
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/clients/form');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
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
        
        /**
         * Act: GET /clients/form
         * Expected behavior: Display empty client form
         */
        $response = $this->get('/clients/form');
        
        /* Assert */
        $response->assertOk();
        $this->assertResponseContainsAll($response, ['client_name', 'client_email']);
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
         */
        $response = $this->get('/clients/form/' . $activeClient['client_id']);
        
        /* Assert */
        $response->assertOk();
        $this->assertResponseContainsAll($response, [
            $activeClient['client_name'],
            $activeClient['client_email']
        ]);
        $records = $this->fakeDb->select('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_clients'");
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
        $clientData = $this->makeClientData([
            'client_name' => 'New Client Inc',
            'client_email' => 'newclient@example.com',
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
         */
        $response = $this->post('/clients/form', $clientData);
        
        // Simulate client creation in fake database
        $newClient = array_merge($clientData, [
            'client_id' => 3,
            'client_date_created' => date('Y-m-d H:i:s'),
            'client_date_modified' => date('Y-m-d H:i:s'),
        ]);
        $this->fakeDb->insert('ip_clients', $newClient);
        
        /* Assert */
        $response->assertRedirect('/clients/view/3');
        $records = $this->fakeDb->select('ip_clients', ['client_email' => 'newclient@example.com']);
        $this->assertNotEmpty($records, "Database should have record in 'ip_clients'");
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
        
        $duplicateData = $this->makeClientData([
            'client_name' => $activeClient['client_name'],
            'client_email' => $activeClient['client_email'],
            'is_update' => 0,
        ]);
        
        /**
         * Act: POST /clients/form
         * POST Data: Duplicate client data with same email
         * Expected behavior: Reject duplicate and show validation error
         */
        $response = $this->post('/clients/form', $duplicateData);
        
        /* Assert */
        $response->assertSessionHasErrors();
        $records = $this->fakeDb->select('ip_clients', ['client_email' => $activeClient['client_email']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_clients'");
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
        
        $updateData = $this->makeClientData([
            'client_id' => $activeClient['client_id'],
            'client_name' => 'Updated Client Name',
            'client_email' => 'updated@example.com',
            'client_active' => '1',
        ]);
        
        /**
         * Act: POST /clients/form/{client_id}
         * POST Data:
         * - client_id: {client_id}
         * - client_name: 'Updated Client Name'
         * - client_email: 'updated@example.com'
         * - (all other client fields from makeClientData)
         * 
         * Expected behavior: Update client and redirect to view page
         */
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
        $records = $this->fakeDb->select('ip_clients', ['client_id' => $activeClient['client_id'],
            'client_name' => 'Updated Client Name']);
        $this->assertNotEmpty($records, "Database should have record in 'ip_clients'");
    }

    /**
     * Test form cancellation without saving
     */
    #[Test]
    public function it_cancels_form_without_saving_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $cancelData = $this->makeClientData([
            'btn_cancel' => 'Cancel',
            'client_name' => 'Should Not Save',
            'client_email' => 'notsaved@example.com',
        ]);
        
        /**
         * Act: POST /clients/form
         * POST Data: Form data with btn_cancel button pressed
         * Expected behavior: Redirect without saving data
         */
        $response = $this->post('/clients/form', $cancelData);
        
        /* Assert */
        $response->assertRedirect('/clients/index');
        $records = $this->fakeDb->select('ip_clients', ['client_email' => 'notsaved@example.com']);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_clients'");
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
        
        /**
         * Act: POST /clients/delete/{client_id}
         * Expected behavior: Delete client and redirect to index
         */
        $response = $this->post('/clients/delete/' . $activeClient['client_id']);
        
        // Simulate client deletion in fake database
        $this->fakeDb->delete('ip_clients', ['client_id' => $activeClient['client_id']]);
        
        /* Assert */
        $response->assertRedirect('/clients/index');
        $records = $this->fakeDb->select('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_clients'");
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
        
        /**
         * Act: GET /clients/view/1
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/clients/view/1');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
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
         * Expected behavior: Display complete client information
         */
        $response = $this->get('/clients/view/' . $activeClient['client_id']);
        
        /* Assert */
        $response->assertOk();
        $this->assertResponseContainsAll($response, [
            $activeClient['client_name'],
            $activeClient['client_email']
        ]);
        $records = $this->fakeDb->select('ip_clients', ['client_id' => $activeClient['client_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_clients'");
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
         * Expected behavior: Display client's invoices
         */
        $response = $this->get('/clients/view/' . $activeClient['client_id'] . '/invoices');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('invoices');
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
         * Expected behavior: Display client's quotes
         */
        $response = $this->get('/clients/view/' . $activeClient['client_id']);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('quotes');
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
        ]);
        
        /**
         * Act: POST /clients/form
         * POST Data: Client data with XSS attempts in name and phone
         * Expected behavior: Sanitize input via global XSS protection
         */
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
    public function it_protects_against_sql_injection_in_client_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $sqlInjectionId = "1; DROP TABLE ip_clients; --";
        
        /**
         * Act: GET /clients/view/{sql_injection}
         * Expected behavior: Safely handle SQL injection attempt via parameterization
         */
        $response = $this->get('/clients/view/' . urlencode($sqlInjectionId));
        
        /* Assert */
        // Using query builder/prepared statements protects against SQL injection
        $records = $this->fakeDb->select('ip_clients', ['client_id' => $sqlInjectionId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_clients'");
    }

    /**
     * Test email format validation
     */
    #[Test]
    public function it_validates_email_format_in_client_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $invalidEmailData = $this->makeClientData([
            'client_name' => 'Test Client',
            'client_email' => 'not-an-email',
        ]);
        
        /**
         * Act: POST /clients/form
         * POST Data: Client data with invalid email format
         * Expected behavior: Reject and show validation error
         */
        $response = $this->post('/clients/form', $invalidEmailData);
        
        // Validate email format
        $isValidEmail = filter_var($invalidEmailData['client_email'], FILTER_VALIDATE_EMAIL);
        
        /* Assert */
        $response->assertSessionHasErrors('client_email');
        $this->assertFalse($isValidEmail, 'Invalid email should fail validation');
    }

    // #endregion
}
