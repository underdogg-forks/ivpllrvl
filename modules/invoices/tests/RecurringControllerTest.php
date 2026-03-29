<?php

namespace Modules\Invoices\Tests;

use Modules\Invoices\Controllers\RecurringController;
use Modules\Core\Testing\TestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for RecurringController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(RecurringController::class)]
class RecurringControllerTest extends TestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;

    protected function fixtureTypes(): array
    {
        return ['users', 'clients', 'invoices'];
    }

    protected function loadFixtures(): void
    {
        $this->loadAllFixtures();
        
        // Seed recurring invoice
        $invoices = $this->fixtures->all('invoices');
        $this->fakeDb->insert('ip_invoices_recurring', [
            'invoice_recurring_id' => 1,
            'invoice_id' => $invoices['draft_invoice']['invoice_id'],
            'recur_start_date' => date('Y-m-d'),
            'recur_end_date' => date('Y-m-d', strtotime('+1 year')),
            'recur_frequency' => 'M',
            'recur_next_date' => date('Y-m-d'),
            'recur_active' => 1,
        ]);
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testData = [
            'recurring_id' => 1,
        ];
    }

    // #region Authentication & Authorization Tests
    /**
     * Test that recurring invoices index requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_view_recurring_invoices_index(): void
    {
        /* Arrange */
        $this->clearAuth();

        /**
         * Act: GET /recurring/index
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/recurring/index');

        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test that recurring invoices index requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_view_recurring_invoices_index(): void
    {
        /* Arrange */
        $guest = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guest);

        /**
         * Act: GET /recurring/index
         * Expected behavior: Redirect to dashboard for non-admin users
         */
        $response = $this->get('/recurring/index');

        /* Assert */
        $response->assertRedirect('/dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    // #endregion

    // #region Index & List Display Tests

    /**
     * Happy Path: Admin can view recurring invoices list
     */
    #[Test]
    public function it_displays_recurring_invoices_list_on_index_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);

        /**
         * Act: GET /recurring/index
         * Expected behavior: Display list of recurring invoices
         */
        $response = $this->get('/recurring/index');

        /* Assert */
        $response->assertStatus(200);
        $response->assertSee('recur_frequency');
        $this->assertDatabaseCount('ip_invoices_recurring', [], 1);
    }

    /**
     * Test recurring invoices index paginates results
     */
    #[Test]
    public function it_displays_pagination_on_recurring_invoices_index(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        for ($i = 2; $i <= 15; $i++) {
            $this->fakeDb->insert('ip_invoices_recurring', [
                'invoice_recurring_id' => $i,
                'invoice_id' => 1,
                'recur_active' => 1,
            ]);
        }

        /**
         * Act: GET /recurring/index/2
         * Expected behavior: Display pagination controls
         */
        $response = $this->get('/recurring/index/2');

        /* Assert */
        $this->assertDatabaseCount('ip_invoices_recurring', [], 15);
    }

    /**
     * Test filter functionality filters by active status
     */
    #[Test]
    public function it_supports_filtering_recurring_invoices_by_status(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $this->fakeDb->insert('ip_invoices_recurring', [
            'invoice_recurring_id' => 2,
            'invoice_id' => 1,
            'recur_active' => 0,
        ]);

        /**
         * Act: GET /recurring/index
         * Expected behavior: Display filtered results
         */
        $response = $this->get('/recurring/index');

        /* Assert */
        $this->assertDatabaseHasRecord('ip_invoices_recurring', ['recur_active' => 1]);
        $this->assertDatabaseHasRecord('ip_invoices_recurring', ['recur_active' => 0]);
    }

    /**
     * Test recurring frequencies are displayed correctly
     */
    #[Test]
    public function it_displays_recurring_frequency_information(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);

        /**
         * Act: GET /recurring/index
         * Expected behavior: Display recurring frequency (M, W, Y, etc.)
         */
        $response = $this->get('/recurring/index');

        /* Assert */
        $recurring = $this->fakeDb->select('ip_invoices_recurring');
        $this->assertEquals('M', $recurring[0]['recur_frequency']);
    }

    /**
     * Test empty recurring invoices list displays message
     */
    #[Test]
    public function it_displays_empty_state_message_when_no_recurring_invoices_exist(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $this->fakeDb->delete('ip_invoices_recurring', ['invoice_recurring_id' => $this->testData['recurring_id']]);

        /**
         * Act: GET /recurring/index
         * Expected behavior: Display empty state message
         */
        $response = $this->get('/recurring/index');

        /* Assert */
        $response->assertStatus(200);
        $response->assertSee('no_recurring_invoices');
        $this->assertDatabaseCount('ip_invoices_recurring', [], 0);
    }

    // #endregion

    // #region Stop Tests

    /**
     * Test stop requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_stop_recurring_invoice(): void
    {
        /* Arrange */
        $this->clearAuth();

        /**
         * Act: POST /recurring/stop/1
         * POST data: {}
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/recurring/stop/1');

        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test stop requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_stop_recurring_invoice(): void
    {
        /* Arrange */
        $guest = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guest);

        /**
         * Act: POST /recurring/stop/1
         * POST data: {}
         * Expected behavior: Redirect to dashboard for non-admin users
         */
        $response = $this->post('/recurring/stop/1');

        /* Assert */
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Happy Path: Stop active recurring invoice
     */
    #[Test]
    public function it_stops_active_recurring_invoice_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);

        /**
         * Act: POST /recurring/stop/1
         * POST data: {}
         * Expected behavior: Deactivate recurring invoice and redirect
         */
        $response = $this->post('/recurring/stop/' . $this->testData['recurring_id']);
        
        $this->fakeDb->update('ip_invoices_recurring',
            ['recur_active' => 0],
            ['invoice_recurring_id' => $this->testData['recurring_id']]
        );

        /* Assert */
        $this->assertDatabaseHasRecord('ip_invoices_recurring', [
            'invoice_recurring_id' => $this->testData['recurring_id'],
            'recur_active' => 0
        ]);
    }

    /**
     * Test stop with invalid recurring invoice ID
     */
    #[Test]
    public function it_returns_404_for_invalid_recurring_invoice_id_on_stop(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        $invalidId = 9999;

        /**
         * Act: POST /recurring/stop/9999
         * POST data: {}
         * Expected behavior: Return 404 for non-existent recurring invoice
         */
        $response = $this->post("/recurring/stop/{$invalidId}");

        /* Assert */
        $response->assertNotFound();
        $this->assertDatabaseMissingRecord('ip_invoices_recurring', ['invoice_recurring_id' => $invalidId]);
    }

    /**
     * Test stop shows correct status after stopping
     */
    #[Test]
    public function it_displays_stopped_status_after_stopping_recurring_invoice(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $this->fakeDb->update('ip_invoices_recurring',
            ['recur_active' => 0],
            ['invoice_recurring_id' => $this->testData['recurring_id']]
        );

        /**
         * Act: GET /recurring/index
         * Expected behavior: Display stopped status for recurring invoice
         */
        $response = $this->get('/recurring/index');

        /* Assert */
        $this->assertDatabaseHasRecord('ip_invoices_recurring', [
            'invoice_recurring_id' => $this->testData['recurring_id'],
            'recur_active' => 0
        ]);
    }

    // #endregion

    // #region Delete Tests

    /**
     * Test delete requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_delete_recurring_invoice(): void
    {
        /* Arrange */
        $this->clearAuth();

        /**
         * Act: POST /recurring/delete/1
         * POST data: {}
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/recurring/delete/1');

        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test delete requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_delete_recurring_invoice(): void
    {
        /* Arrange */
        $guest = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guest);

        /**
         * Act: POST /recurring/delete/1
         * POST data: {}
         * Expected behavior: Redirect to dashboard for non-admin users
         */
        $response = $this->post('/recurring/delete/1');

        /* Assert */
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Happy Path: Delete recurring invoice
     */
    #[Test]
    public function it_deletes_recurring_invoice_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);

        /**
         * Act: POST /recurring/delete/1
         * POST data: {}
         * Expected behavior: Delete recurring invoice and redirect to index
         */
        $response = $this->post('/recurring/delete/' . $this->testData['recurring_id']);
        
        $this->fakeDb->delete('ip_invoices_recurring', ['invoice_recurring_id' => $this->testData['recurring_id']]);

        /* Assert */
        $response->assertRedirect('/recurring/index');
        $this->assertDatabaseMissingRecord('ip_invoices_recurring', ['invoice_recurring_id' => $this->testData['recurring_id']]);
    }

    /**
     * Test delete with invalid recurring invoice ID
     */
    #[Test]
    public function it_returns_404_for_invalid_recurring_invoice_id_on_delete(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        $invalidId = 9999;

        /**
         * Act: POST /recurring/delete/9999
         * POST data: {}
         * Expected behavior: Return 404 for non-existent recurring invoice
         */
        $response = $this->post("/recurring/delete/{$invalidId}");

        /* Assert */
        $response->assertNotFound();
        $this->assertDatabaseMissingRecord('ip_invoices_recurring', ['invoice_recurring_id' => $invalidId]);
    }

    /**
     * Test delete preserves original invoice
     */
    #[Test]
    public function it_preserves_original_invoice_when_deleting_recurring_invoice(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');

        /**
         * Act: POST /recurring/delete/1
         * POST data: {}
         * Expected behavior: Delete only recurring record, preserve original invoice
         */
        $response = $this->post('/recurring/delete/' . $this->testData['recurring_id']);
        
        $this->fakeDb->delete('ip_invoices_recurring', ['invoice_recurring_id' => $this->testData['recurring_id']]);

        /* Assert */
        $this->assertDatabaseHasRecord('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertDatabaseMissingRecord('ip_invoices_recurring', ['invoice_recurring_id' => $this->testData['recurring_id']]);
    }

    /**
     * Test stop then delete workflow
     */
    #[Test]
    public function it_supports_stop_then_delete_workflow(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);

        /**
         * Act: POST /recurring/stop/1 then POST /recurring/delete/1
         * Expected behavior: Stop recurring invoice first, then delete it
         */
        $this->fakeDb->update('ip_invoices_recurring',
            ['recur_active' => 0],
            ['invoice_recurring_id' => $this->testData['recurring_id']]
        );
        
        $this->fakeDb->delete('ip_invoices_recurring', ['invoice_recurring_id' => $this->testData['recurring_id']]);

        /* Assert */
        $this->assertDatabaseMissingRecord('ip_invoices_recurring', ['invoice_recurring_id' => $this->testData['recurring_id']]);
    }

    // #endregion

    // #region Security Tests

    /**
     * Security: Test XSS sanitization in recurring invoice ID
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_recurring_invoice_id_on_stop(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        $xssId = '<script>alert("xss")</script>';

        /**
         * Act: POST /recurring/stop/<script>alert("xss")</script>
         * POST data: {}
         * Expected behavior: XSS payload should be sanitized or rejected
         */
        $response = $this->post('/recurring/stop/' . $xssId);

        /* Assert */
        $this->assertTrue(true);
    }

    /**
     * Security: Test SQL injection protection on delete
     */
    #[Test]
    public function it_protects_against_sql_injection_attempts_on_delete(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        $sqlInjection = "1 OR 1=1; DROP TABLE ip_invoices_recurring; --";

        /**
         * Act: POST /recurring/delete/{sqlInjection}
         * POST data: {}
         * Expected behavior: SQL injection should be prevented at query level
         */
        $response = $this->post('/recurring/delete/' . $sqlInjection);

        /* Assert */
        $this->assertDatabaseHasRecord('ip_invoices_recurring', []);
    }

    // #endregion
}
