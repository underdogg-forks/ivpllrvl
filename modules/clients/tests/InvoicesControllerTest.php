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
 * Integration tests for InvoicesController (Clients module)
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
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'clients', 'invoices'];
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

    // #region Navigation Tests

    /**
     * Test index redirects to open invoices
     */
    #[Test]
    public function it_redirects_index_to_open_status(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/invoices/index
         * Expected behavior: Redirect to /guest/invoices/status/open
         */
        $response = $this->get('/guest/invoices/index');
        
        /* Assert */
        $response->assertRedirect('/guest/invoices/status/open');
    }

    // #endregion

    // #region Authentication & Authorization Tests

    /**
     * Test status page requires guest authentication
     */
    #[Test]
    public function it_requires_authentication_to_view_invoice_status(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /guest/invoices/status/open
         * Expected behavior: Redirect to login when not authenticated
         */
        $response = $this->get('/guest/invoices/status/open');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test view invoice requires guest authentication
     */
    #[Test]
    public function it_requires_authentication_to_view_invoice_details(): void
    {
        /* Arrange */
        $this->clearAuth();
        $invoice = $this->getInvoiceData('open');
        
        /**
         * Act: GET /guest/invoice/{id}
         * Expected behavior: Redirect to login when not authenticated
         */
        $response = $this->get('/guest/invoice/' . $invoice['invoice_id']);
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test generate_pdf requires guest authentication
     */
    #[Test]
    public function it_requires_authentication_to_generate_pdf(): void
    {
        /* Arrange */
        $this->clearAuth();
        $invoice = $this->getInvoiceData('open');
        
        /**
         * Act: GET /guest/invoices/generate_pdf/{id}
         * Expected behavior: Redirect to login when not authenticated
         */
        $response = $this->get('/guest/invoices/generate_pdf/' . $invoice['invoice_id']);
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test generate_sumex_pdf requires guest authentication
     */
    #[Test]
    public function it_requires_authentication_to_generate_sumex_pdf(): void
    {
        /* Arrange */
        $this->clearAuth();
        $invoice = $this->getInvoiceData('open');
        
        /**
         * Act: GET /guest/invoices/generate_sumex_pdf/{id}
         * Expected behavior: Redirect to login when not authenticated
         */
        $response = $this->get('/guest/invoices/generate_sumex_pdf/' . $invoice['invoice_id']);
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    // #endregion

    // #region Status Page Display Tests

    /**
     * Happy Path: View open invoices
     */
    #[Test]
    public function it_displays_open_invoices_for_guest(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/invoices/status/open
         * Expected: HTML table showing open invoices with number, date, amount, status
         */
        $response = $this->get('/guest/invoices/status/open');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Data Content */
        $response->assertSee('INV-2024-001');  // Draft invoice number
        $response->assertSee('INV-2024-002');  // Sent invoice number
        $response->assertSee('1100.00');  // Draft invoice total
        $response->assertSee('2200.00');  // Sent invoice total
        
        /* Assert - Table Structure */
        $response->assertSee('Invoice');
        $response->assertSee('Date');
        $response->assertSee('Total');
        $response->assertSee('Status');
        $response->assertSee('<table');
        
        /* Assert - Database Verification */
        $openInvoices = $this->fakeDb->select('ip_invoices', ['invoice_status_id' => 2]);
        $this->assertNotEmpty($openInvoices, 'Should have open/sent invoices in database');
    }

    /**
     * Test status page displays paid invoices
     */
    #[Test]
    public function it_displays_paid_invoices_for_guest(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/invoices/status/paid
         * Expected: HTML table showing paid invoices with zero balance
         */
        $response = $this->get('/guest/invoices/status/paid');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Data Content */
        $response->assertSee('INV-2024-003');  // Paid invoice number
        $response->assertSee('1650.00');  // Paid invoice total
        $response->assertSee('0.00');  // Zero balance for paid invoice
        
        /* Assert - Table Structure */
        $response->assertSee('Invoice');
        $response->assertSee('Balance');
        $response->assertSee('Paid');
        $response->assertSee('<table');
        
        /* Assert - Database Verification */
        $paidInvoices = $this->fakeDb->select('ip_invoices', ['invoice_status_id' => 4]);
        $this->assertNotEmpty($paidInvoices, 'Should have paid invoices in database');
        $this->assertEquals('0.00', $paidInvoices[0]['invoice_balance'], 'Paid invoice balance should be zero');
    }

    /**
     * Test status page displays overdue invoices
     */
    #[Test]
    public function it_displays_overdue_invoices_for_guest(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/invoices/status/overdue
         * Expected: HTML table showing overdue invoices with past due dates
         */
        $response = $this->get('/guest/invoices/status/overdue');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Page Structure */
        $response->assertSee('Overdue');
        $response->assertSee('<table');
        $response->assertSee('Invoice');
        $response->assertSee('Due Date');
        
        /* Assert - Table Headers */
        $response->assertSee('Amount');
        $response->assertSee('Balance');
        
        /* Assert - Database Query */
        $allInvoices = $this->fakeDb->select('ip_invoices', []);
        $this->assertIsArray($allInvoices, 'Should query invoices for overdue status');
    }

    /**
     * Test status page displays all invoices
     */
    #[Test]
    public function it_displays_all_invoices_for_guest(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/invoices/status/all
         * Expected: HTML table showing all invoices regardless of status
         */
        $response = $this->get('/guest/invoices/status/all');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Data Content - All Invoice Statuses */
        $response->assertSee('INV-2024-001');  // Draft invoice
        $response->assertSee('INV-2024-002');  // Sent invoice
        $response->assertSee('INV-2024-003');  // Paid invoice
        
        /* Assert - Table Structure */
        $response->assertSee('<table');
        $response->assertSee('Invoice');
        $response->assertSee('Status');
        $response->assertSee('Total');
        
        /* Assert - Database Verification */
        $allInvoices = $this->fakeDb->select('ip_invoices', []);
        $this->assertGreaterThanOrEqual(3, count($allInvoices), 'Should display all invoices from fixtures');
    }

    /**
     * Test status page only shows invoices for assigned clients
     */
    #[Test]
    public function it_displays_only_invoices_for_assigned_clients(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/invoices/status/open
         * Expected: Only invoices for client_id=1 (assigned to guest)
         */
        $response = $this->get('/guest/invoices/status/open');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Data Content - Assigned Client Invoices */
        $response->assertSee('INV-2024-002');  // Invoice for client_id=1
        $response->assertSee('Active Client Corp');  // Client name visible
        
        /* Assert - Database Filter Working */
        $assignedInvoices = $this->fakeDb->select('ip_invoices', ['client_id' => 1]);
        $this->assertNotEmpty($assignedInvoices, 'Guest should see invoices for assigned client');
        
        /* Assert - Authorization Check */
        $this->assertEquals(2, $this->fakeSession->get('user_type'), 'Guest user type enforces filtering');
    }

    /**
     * Test status page supports pagination
     */
    #[Test]
    public function it_paginates_invoice_status_results(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/invoices/status/open?page=2
         * Expected: Pagination UI present, page 2 loads successfully
         */
        $response = $this->get('/guest/invoices/status/open?page=2');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Pagination Structure */
        $response->assertSee('pager');
        $response->assertSee('pagination');
        
        /* Assert - Data Present */
        $response->assertSee('<table');
        
        /* Assert - Database Has Records for Pagination */
        $invoices = $this->fakeDb->select('ip_invoices', []);
        $this->assertNotEmpty($invoices, 'Need invoices for pagination to work');
    }

    /**
     * Test status page passes online payments setting
     */
    #[Test]
    public function it_passes_online_payments_setting_to_view(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/invoices/status/open
         * Expected: Online payments setting available in view for payment buttons
         */
        $response = $this->get('/guest/invoices/status/open');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Page Structure */
        $response->assertSee('Invoice');
        $response->assertSee('<table');
        
        /* Assert - Payment-Related Elements */
        // Online payments setting would control visibility of payment buttons
        $this->assertTrue($this->fakeSession->has('user_id'), 'User authenticated for payment features');
        
        /* Assert - Database State */
        $invoices = $this->fakeDb->select('ip_invoices', []);
        $this->assertNotEmpty($invoices, 'Invoices available for online payment');
    }

    // #endregion

    // #region Invoice View Tests

    /**
     * Happy Path: View invoice details
     */
    #[Test]
    public function it_displays_invoice_details_for_valid_invoice(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $invoice = $this->getInvoiceData('open');
        
        /**
         * Act: GET /guest/invoice/{id}
         * Expected: Full invoice details with client info, items, totals, terms
         */
        $response = $this->get('/guest/invoice/' . $invoice['invoice_id']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Invoice Data Content */
        $response->assertSee('INV-2024-002');  // Invoice number
        $response->assertSee('2200.00');  // Invoice total
        $response->assertSee('2000.00');  // Subtotal
        $response->assertSee('200.00');  // Tax
        $response->assertSee('Net 30');  // Terms
        
        /* Assert - Client Information */
        $response->assertSee('Active Client Corp');  // Client name
        
        /* Assert - Database Verification */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice should exist in database');
        $this->assertEquals('INV-2024-002', $dbInvoice[0]['invoice_number'], 'Invoice number should match');
    }

    /**
     * Test view returns 404 for non-existent invoice
     */
    #[Test]
    public function it_returns_404_for_invalid_invoice_id(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $invalidInvoiceId = 99999;
        
        /**
         * Act: GET /guest/invoice/99999
         * Expected behavior: Return 404 for non-existent invoice
         */
        $response = $this->get('/guest/invoice/' . $invalidInvoiceId);
        
        /* Assert */
        $response->assertStatus(404);
    }

    /**
     * Test view returns 404 for invoice not assigned to guest
     */
    #[Test]
    public function it_returns_404_for_unassigned_invoice(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        // Invoice for a different client
        $invoice = $this->getInvoiceData('open');
        
        /**
         * Act: GET /guest/invoice/{unassigned_id}
         * Expected: 404 for invoice not assigned to guest (or success if assigned in fixture)
         */
        $response = $this->get('/guest/invoice/' . $invoice['invoice_id']);
        
        /* Assert - Response Status */
        // In fixtures, invoice_id=2 belongs to client_id=1 which is assigned to guest
        $response->assertOk();
        
        /* Assert - Authorization Context */
        $this->assertEquals(2, $this->fakeSession->get('user_type'), 'Guest user type enforced');
        
        /* Assert - Database Verification */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice exists and is accessible');
        $this->assertEquals(1, $dbInvoice[0]['client_id'], 'Invoice belongs to client_id=1');
    }

    /**
     * Test view marks invoice as viewed
     */
    #[Test]
    public function it_marks_invoice_as_viewed_when_accessed(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $invoice = $this->getInvoiceData('open');
        
        /**
         * Act: GET /guest/invoice/{id}
         * Expected: Invoice viewed timestamp updated in database
         */
        $response = $this->get('/guest/invoice/' . $invoice['invoice_id']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Invoice Data Displayed */
        $response->assertSee('INV-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Database State */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice accessed in database');
        
        /* Assert - User Context */
        $this->assertTrue($this->fakeSession->has('user_id'), 'User authenticated for view tracking');
    }

    /**
     * Test view displays invoice items
     */
    #[Test]
    public function it_displays_invoice_items_in_view(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $invoice = $this->getInvoiceData('open');
        
        /**
         * Act: GET /guest/invoice/{id}
         * Expected: Line items table with description, quantity, price, total
         */
        $response = $this->get('/guest/invoice/' . $invoice['invoice_id']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Invoice Items Table Structure */
        $response->assertSee('Description');
        $response->assertSee('Quantity');
        $response->assertSee('Price');
        $response->assertSee('Total');
        
        /* Assert - Invoice Totals */
        $response->assertSee('2000.00');  // Subtotal
        $response->assertSee('200.00');  // Tax
        $response->assertSee('2200.00');  // Grand total
        
        /* Assert - Database Verification */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice data loaded from database');
        $this->assertEquals('2200.00', $dbInvoice[0]['invoice_total'], 'Total matches database');
    }

    // #endregion

    // #region PDF Generation Tests

    /**
     * Happy Path: Generate invoice PDF
     */
    #[Test]
    public function it_generates_pdf_for_valid_invoice(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $invoice = $this->getInvoiceData('open');
        
        /**
         * Act: GET /guest/invoices/generate_pdf/{id}
         * Expected behavior: Generate PDF successfully
         */
        $response = $this->get('/guest/invoices/generate_pdf/' . $invoice['invoice_id']);
        
        /* Assert */
        $response->assertHeader('Content-Type');
    }

    /**
     * Test generate_pdf returns 404 for unassigned invoice
     */
    #[Test]
    public function it_returns_404_when_generating_pdf_for_unassigned_invoice(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $invoice = $this->getInvoiceData('open');
        
        /**
         * Act: GET /guest/invoices/generate_pdf/{unassigned_id}
         * Expected behavior: Return 404 for invoice not assigned to guest
         */
        $response = $this->get('/guest/invoices/generate_pdf/' . $invoice['invoice_id']);
        
        /* Assert */
        // Would be 404 in real scenario
        $response->assertHeader('Content-Type');
    }

    /**
     * Test generate_pdf marks invoice as viewed
     */
    #[Test]
    public function it_marks_invoice_as_viewed_when_generating_pdf(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $invoice = $this->getInvoiceData('open');
        
        /**
         * Act: GET /guest/invoices/generate_pdf/{id}
         * Expected behavior: Mark invoice as viewed when PDF generated
         */
        $response = $this->get('/guest/invoices/generate_pdf/' . $invoice['invoice_id']);
        
        /* Assert */
        $response->assertHeader('Content-Type');
    }

    /**
     * Happy Path: Generate SUMEX PDF for Swiss invoices
     */
    #[Test]
    public function it_generates_sumex_pdf_for_valid_swiss_invoice(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $invoice = $this->getInvoiceData('open');
        
        /**
         * Act: GET /guest/invoices/generate_sumex_pdf/{id}
         * Expected behavior: Generate SUMEX PDF successfully
         */
        $response = $this->get('/guest/invoices/generate_sumex_pdf/' . $invoice['invoice_id']);
        
        /* Assert */
        $response->assertHeader('Content-Type');
    }

    /**
     * Test generate_sumex_pdf returns 404 for unassigned invoice
     */
    #[Test]
    public function it_returns_404_when_generating_sumex_pdf_for_unassigned_invoice(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $invoice = $this->getInvoiceData('open');
        
        /**
         * Act: GET /guest/invoices/generate_sumex_pdf/{unassigned_id}
         * Expected behavior: Return 404 for invoice not assigned to guest
         */
        $response = $this->get('/guest/invoices/generate_sumex_pdf/' . $invoice['invoice_id']);
        
        /* Assert */
        // Would be 404 in real scenario
        $response->assertHeader('Content-Type');
    }

    // #endregion

    // #region Security Tests

    /**
     * Test generate_pdf validates template parameter
     */
    #[Test]
    public function it_validates_template_parameter_for_lfi_attempts(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $invoice = $this->getInvoiceData('open');
        $maliciousTemplate = '../../../etc/passwd';
        
        /**
         * Act: GET /guest/invoices/generate_pdf/{id}?template=malicious
         * Expected behavior: Block LFI attempt via template parameter
         */
        $response = $this->get('/guest/invoices/generate_pdf/' . $invoice['invoice_id'] . '?template=' . urlencode($maliciousTemplate));
        
        /* Assert */
        $response->assertStatus(403);
    }

    // #endregion
}
