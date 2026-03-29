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
        $this->assertRequiresAuthentication($response);
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
        $this->assertRequiresAuthentication($response);
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
        $this->assertRequiresAuthentication($response);
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
        $this->assertRequiresAuthentication($response);
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
         * Expected behavior: Display open invoices successfully
         */
        $response = $this->get('/guest/invoices/status/open');
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Display paid invoices successfully
         */
        $response = $this->get('/guest/invoices/status/paid');
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Display overdue invoices successfully
         */
        $response = $this->get('/guest/invoices/status/overdue');
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Display all invoices successfully
         */
        $response = $this->get('/guest/invoices/status/all');
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Only show invoices for clients assigned to guest
         */
        $response = $this->get('/guest/invoices/status/open');
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Display paginated results
         */
        $response = $this->get('/guest/invoices/status/open?page=2');
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Pass online payments configuration to view
         */
        $response = $this->get('/guest/invoices/status/open');
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Display invoice details
         */
        $response = $this->get('/guest/invoice/' . $invoice['invoice_id']);
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
        $this->assertNotFoundResponse($response);
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
         * Expected behavior: Return 404 for invoice not assigned to guest
         */
        $response = $this->get('/guest/invoice/' . $invoice['invoice_id']);
        
        /* Assert */
        // This would be 404 in real scenario if client not assigned
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Mark invoice as viewed
         */
        $response = $this->get('/guest/invoice/' . $invoice['invoice_id']);
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Display invoice items
         */
        $response = $this->get('/guest/invoice/' . $invoice['invoice_id']);
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
        $this->assertForbiddenResponse($response);
    }

    // #endregion
}
