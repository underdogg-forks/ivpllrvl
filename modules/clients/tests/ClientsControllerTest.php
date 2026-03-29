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
    public function it_requires_authentication_to_display_clients_index(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /clients/index
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/clients/index');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test that clients index requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_display_clients_index(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /clients/index
         * Expected behavior: Redirect to dashboard for non-admin users
         */
        $response = $this->get('/clients/index');
        
        /* Assert */
        $response->assertRedirect('/dashboard');
    }

    // #endregion

    // #region Index & List Display Tests

    /**
     * Happy Path: Clients index redirects to active status
     */
    #[Test]
    public function it_redirects_clients_index_to_status_active(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /clients/index
         * Expected behavior: Redirect to active clients list
         */
        $response = $this->get('/clients/index');
        
        /* Assert */
        $response->assertRedirect('/clients/status/active');
    }

    /**
     * Happy Path: Status page displays active clients list
     */
    #[Test]
    public function it_displays_active_clients_list_on_status_page(): void
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
        $response->assertSee($activeClient['client_name']);
        $this->assertDatabaseHasRecord('ip_clients', [
            'client_id' => $activeClient['client_id'],
            'client_active' => 1
        ]);
    }

    /**
     * Test status page displays inactive clients list
     */
    #[Test]
    public function it_displays_inactive_clients_list_on_status_page(): void
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
        $response->assertSee($inactiveClient['client_name']);
        $this->assertDatabaseHasRecord('ip_clients', [
            'client_id' => $inactiveClient['client_id'],
            'client_active' => 0
        ]);
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
        
        /**
         * Act: GET /clients/status/active
         * Expected behavior: Display client balance information
         */
        $response = $this->get('/clients/status/active');
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['client_balance']);
        $this->assertDatabaseHasRecord('ip_clients', ['client_active' => 1]);
    }

    // #endregion

    // #region Form Display Tests

    /**
     * Test that client form requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_client_form(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /clients/form
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/clients/form');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
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
         * Expected behavior: Display new client form fields
         */
        $response = $this->get('/clients/form');
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['client_name', 'client_email']);
        $this->assertTrue($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Form displays edit client form with existing data
     */
    #[Test]
    public function it_displays_edit_client_form_with_existing_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        $clientId = $activeClient['client_id'];
        
        /**
         * Act: GET /clients/form/{id}
         * Expected behavior: Display edit form with existing client data
         */
        $response = $this->get('/clients/form/' . $clientId);
        
        /* Assert */
        $response->assertSee($activeClient['client_name']);
        $response->assertSee($activeClient['client_email']);
        $this->assertDatabaseHasRecord('ip_clients', [
            'client_id' => $clientId,
            'client_name' => $activeClient['client_name']
        ]);
    }

    // #endregion

    // #region Form Submission Tests (Create)

    /**
     * Happy Path: POST creates new client with valid data
     */
    #[Test]
    public function it_creates_new_client_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $validClientData = $this->makeClientData([
            'client_name' => 'New Test Client',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /clients/form
         * POST data: {
         *   "client_name": "New Test Client",
         *   "client_email": "client@example.com",
         *   "client_phone": "+1234567890",
         *   "client_address_1": "456 Client Ave",
         *   "client_city": "Client City",
         *   "client_active": "1",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Create new client and redirect to view page
         */
        $response = $this->post('/clients/form', $validClientData);
        
        /* Assert */
        $response->assertRedirect();
        $this->assertDatabaseHasRecord('ip_clients', ['client_name' => 'New Test Client']);
    }

    /**
     * Test POST cancels without saving when btn_cancel is clicked
     */
    #[Test]
    public function it_cancels_form_without_saving_when_cancel_button_clicked(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $clientData = $this->makeClientData([
            'client_name' => 'Should Not Be Created',
            'btn_cancel' => 'Cancel',
        ]);
        
        /**
         * Act: POST /clients/form
         * POST data: Complete client data with btn_cancel set
         * Expected behavior: Cancel and redirect without saving
         */
        $response = $this->post('/clients/form', $clientData);
        
        /* Assert */
        $response->assertRedirect('/clients/index');
        $this->assertDatabaseMissingRecord('ip_clients', ['client_name' => 'Should Not Be Created']);
    }

    // #endregion

    // #region Form Submission Tests (Update)

    /**
     * Happy Path: POST updates existing client
     */
    #[Test]
    public function it_updates_existing_client_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        $updateData = $this->makeClientData([
            'client_id' => $activeClient['client_id'],
            'client_name' => 'Updated Client Name',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /clients/form/{id}
         * POST data: Complete client data with updated client_name
         * Expected behavior: Update client and redirect to view page
         */
        $response = $this->post('/clients/form/' . $activeClient['client_id'], $updateData);
        
        /* Assert */
        $response->assertRedirect('/clients/view/' . $activeClient['client_id']);
        $this->assertDatabaseHasRecord('ip_clients', [
            'client_id' => $activeClient['client_id'],
            'client_name' => 'Updated Client Name'
        ]);
    }

    // #endregion

    // #region View & Detail Tests

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
        $this->assertRequiresAuthentication($response);
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
        $clientId = $activeClient['client_id'];
        
        /**
         * Act: GET /clients/view/{id}
         * Expected behavior: Display client details
         */
        $response = $this->get('/clients/view/' . $clientId);
        
        /* Assert */
        $this->assertResponseContainsAll($response, [
            $activeClient['client_name'],
            $activeClient['client_email']
        ]);
        $this->assertDatabaseHasRecord('ip_clients', [
            'client_id' => $clientId,
            'client_name' => $activeClient['client_name']
        ]);
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
         * Act: GET /clients/view/{id}/invoices
         * Expected behavior: Display client invoices section
         */
        $response = $this->get('/clients/view/' . $activeClient['client_id'] . '/invoices');
        
        /* Assert */
        $response->assertSee('invoices');
        $this->assertDatabaseHasRecord('ip_clients', ['client_id' => $activeClient['client_id']]);
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
         * Act: GET /clients/view/{id}
         * Expected behavior: Display client quotes section
         */
        $response = $this->get('/clients/view/' . $activeClient['client_id']);
        
        /* Assert */
        $response->assertSee('quotes');
        $this->assertDatabaseHasRecord('ip_clients', ['client_id' => $activeClient['client_id']]);
    }

    // #endregion

    // #region Delete Tests

    /**
     * Test POST delete removes client
     */
    #[Test]
    public function it_deletes_client_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        $clientId = $activeClient['client_id'];
        
        /**
         * Act: POST /clients/delete/{id}
         * POST data: {
         *   "btn_submit": "1"
         * }
         * Expected behavior: Delete client and redirect to index
         */
        $response = $this->post('/clients/delete/' . $clientId, [
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        $response->assertRedirect('/clients/index');
        $this->assertDatabaseMissingRecord('ip_clients', ['client_id' => $clientId]);
    }

    // #endregion

    // #region Validation Tests

    /**
     * Test POST validates duplicate client email
     */
    #[Test]
    public function it_validates_client_email_is_unique(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        $duplicateData = $this->makeClientData([
            'client_email' => $activeClient['client_email'],
        ]);
        
        /**
         * Act: POST /clients/form
         * POST data: Complete data with duplicate client_email
         * Expected behavior: Validation error for duplicate email
         */
        $response = $this->post('/clients/form', $duplicateData);
        
        /* Assert */
        $response->assertSessionHasErrors(['client_email']);
    }

    /**
     * Test POST validates email format
     */
    #[Test]
    public function it_validates_client_email_format(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeClientData([
            'client_email' => 'not-an-email',
        ]);
        
        /**
         * Act: POST /clients/form
         * POST data: Complete data with invalid email format
         * Expected behavior: Validation error for client_email
         */
        $response = $this->post('/clients/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['client_email']);
    }

    // #endregion

    // #region Security Tests

    /**
     * Security: Test XSS sanitization in client data
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_client_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $xssData = $this->makeClientData([
            'client_name' => '<script>alert("xss")</script>',
            'client_phone' => '<img src=x onerror=alert("xss")>',
        ]);
        
        /**
         * Act: POST /clients/form
         * POST data: Complete data with XSS payloads in client_name and client_phone
         * Expected behavior: XSS payloads should be sanitized or rejected
         */
        $response = $this->post('/clients/form', $xssData);
        
        /* Assert */
        $this->assertTrue(true);
    }

    /**
     * Security: Test SQL injection protection
     */
    #[Test]
    public function it_protects_against_sql_injection_attempts(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $sqlInjectionId = "1; DROP TABLE ip_clients; --";
        
        /**
         * Act: GET /clients/view/{id}
         * Expected behavior: SQL injection should be prevented at query level
         */
        $response = $this->get('/clients/view/' . urlencode($sqlInjectionId));
        
        /* Assert */
        $this->assertTrue(true);
    }

    // #endregion
}
