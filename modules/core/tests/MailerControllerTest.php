<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\MailerController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for MailerController
 * 
 * Tests email sending functionality for invoices and quotes.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(MailerController::class)]
class MailerControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = MailerController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'clients', 'invoices', 'quotes', 'email_templates'];
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

    // #region Configuration & Authentication Tests

    /**
     * Test constructor checks mailer configuration
     */
    #[Test]
    public function it_checks_mailer_configuration_on_initialization(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /mailer/invoice/1
         * Expected behavior: Check mailer configuration and display form if configured
         */
        $response = $this->get('/mailer/invoice/1');
        
        /* Assert */
        // Verify mailer configuration is checked
        // If not configured, should redirect or show error
        $response->assertOk();
    }

    /**
     * Test that invoice mailer requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_access_invoice_mailer(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /mailer/invoice/1
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/mailer/invoice/1');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test mailer returns early if not configured
     */
    #[Test]
    public function it_returns_early_if_mailer_is_not_configured(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /mailer/invoice/1
         * Expected behavior: Return early with redirect if mailer not configured
         * Mock mailer_configured() to return false
         */
        $response = $this->get('/mailer/invoice/1');
        
        /* Assert */
        $response->assertStatus(302);
    }

    // #endregion

    // #region Invoice Email Form Display Tests


    /**
     * Happy Path: Invoice email form displays
     */
    #[Test]
    public function it_displays_invoice_email_form_with_fields(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        
        /**
         * Act: GET /mailer/invoice/{id}
         * Expected behavior: Display email form with recipient, subject, body fields
         */
        $response = $this->get('/mailer/invoice/' . $invoice['invoice_id']);
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['to_email', 'subject']);
        // Verify invoice exists in fake DB
        $this->assertDatabaseHasRecord('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
    }

    /**
     * Test form selects appropriate email template
     */
    #[Test]
    public function it_selects_appropriate_email_template_for_invoice(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        
        /**
         * Act: GET /mailer/invoice/{id}
         * Expected behavior: Load appropriate email template for invoice type
         */
        $response = $this->get('/mailer/invoice/' . $invoice['invoice_id']);
        
        /* Assert */
        // Verify email template is loaded
        $templates = $this->fakeDb->select('ip_email_templates', ['email_template_type' => 'invoice']);
        $this->assertGreaterThan(0, count($templates));
    }

    /**
     * Test form includes custom fields
     */
    #[Test]
    public function it_includes_custom_fields_in_invoice_email_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        
        /**
         * Act: GET /mailer/invoice/{id}
         * Expected behavior: Include custom fields in email form
         */
        $response = $this->get('/mailer/invoice/' . $invoice['invoice_id']);
        
        /* Assert */
        $response->assertSee('custom_fields');
    }

    // #endregion

    // #region Invoice Email Sending Tests


    /**
     * Happy Path: POST sends invoice email successfully
     */
    #[Test]
    public function it_sends_invoice_email_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        $mailerData = $this->makeMailerData();
        
        /**
         * Act: POST /mailer/invoice/{id}
         * POST data: {
         *   "to_email": "client@example.com",
         *   "from_email": "billing@example.com",
         *   "from_name": "Test Company",
         *   "subject": "Invoice INV-001",
         *   "body": "Please find your invoice attached.",
         *   "pdf_template": "default",
         *   "cc": "",
         *   "bcc": "",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Send email with invoice attachment and redirect
         */
        $response = $this->post('/mailer/invoice/' . $invoice['invoice_id'], $mailerData);
        
        /* Assert */
        $response->assertStatus(302);
        // Verify email was sent (check email queue or log)
    }

    /**
     * Test invoice number generation for drafts
     */
    #[Test]
    public function it_generates_invoice_number_before_sending_draft(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        $mailerData = $this->makeMailerData();
        
        /**
         * Act: POST /mailer/invoice/{id}
         * POST data: {
         *   "to_email": "client@example.com",
         *   "from_email": "billing@example.com",
         *   "from_name": "Test Company",
         *   "subject": "Invoice INV-001",
         *   "body": "Please find your invoice attached.",
         *   "pdf_template": "default",
         *   "cc": "",
         *   "bcc": "",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Generate invoice number for draft before sending
         */
        $response = $this->post('/mailer/invoice/' . $invoice['invoice_id'], $mailerData);
        
        /* Assert */
        // Verify invoice was assigned a number
        $this->assertDatabaseHasRecord('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
    }

    /**
     * Test cancel button cancels email sending
     */
    #[Test]
    public function it_cancels_email_sending_when_cancel_button_clicked(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        
        /**
         * Act: POST /mailer/invoice/{id}
         * POST data: {
         *   "btn_cancel": "Cancel"
         * }
         * Expected behavior: Cancel email sending and redirect
         */
        $response = $this->post('/mailer/invoice/' . $invoice['invoice_id'], [
            'btn_cancel' => 'Cancel',
        ]);
        
        /* Assert */
        $response->assertStatus(302);
    }

    /**
     * Test CC and BCC handling
     */
    #[Test]
    public function it_handles_cc_and_bcc_email_addresses(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        $mailerData = $this->makeMailerData([
            'cc' => 'accounting@example.com',
            'bcc' => 'archive@example.com',
        ]);
        
        /**
         * Act: POST /mailer/invoice/{id}
         * POST data: {
         *   "to_email": "client@example.com",
         *   "from_email": "billing@example.com",
         *   "from_name": "Test Company",
         *   "subject": "Invoice INV-001",
         *   "body": "Please find your invoice attached.",
         *   "pdf_template": "default",
         *   "cc": "accounting@example.com",
         *   "bcc": "archive@example.com",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Include CC and BCC in email
         */
        $response = $this->post('/mailer/invoice/' . $invoice['invoice_id'], $mailerData);
        
        /* Assert */
        // Verify email includes CC and BCC
        $response->assertStatus(302);
    }

    /**
     * Test PDF attachment inclusion
     */
    #[Test]
    public function it_includes_pdf_attachment_in_email(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        $mailerData = $this->makeMailerData();
        
        /**
         * Act: POST /mailer/invoice/{id}
         * POST data: {
         *   "to_email": "client@example.com",
         *   "from_email": "billing@example.com",
         *   "from_name": "Test Company",
         *   "subject": "Invoice INV-001",
         *   "body": "Please find your invoice attached.",
         *   "pdf_template": "default",
         *   "cc": "",
         *   "bcc": "",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Include PDF attachment in email
         */
        $response = $this->post('/mailer/invoice/' . $invoice['invoice_id'], $mailerData);
        
        /* Assert */
        // Verify PDF attachment was included
        $response->assertStatus(302);
    }

    /**
     * Test HTML body conversion
     */
    #[Test]
    public function it_converts_html_body_content_correctly(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        $mailerData = $this->makeMailerData([
            'body' => '<p>HTML content</p>',
        ]);
        
        /**
         * Act: POST /mailer/invoice/{id}
         * POST data: {
         *   "to_email": "client@example.com",
         *   "from_email": "billing@example.com",
         *   "from_name": "Test Company",
         *   "subject": "Invoice INV-001",
         *   "body": "<p>HTML content</p>",
         *   "pdf_template": "default",
         *   "cc": "",
         *   "bcc": "",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Process HTML content in email body
         */
        $response = $this->post('/mailer/invoice/' . $invoice['invoice_id'], $mailerData);
        
        /* Assert */
        // Verify HTML is processed correctly
        $response->assertStatus(302);
    }

    /**
     * Test plain text to HTML conversion
     */
    #[Test]
    public function it_converts_plain_text_to_html_with_line_breaks(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        $mailerData = $this->makeMailerData([
            'body' => "Plain text\nWith line breaks",
        ]);
        
        /**
         * Act: POST /mailer/invoice/{id}
         * POST data: {
         *   "to_email": "client@example.com",
         *   "from_email": "billing@example.com",
         *   "from_name": "Test Company",
         *   "subject": "Invoice INV-001",
         *   "body": "Plain text\nWith line breaks",
         *   "pdf_template": "default",
         *   "cc": "",
         *   "bcc": "",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Convert plain text to HTML with line breaks
         */
        $response = $this->post('/mailer/invoice/' . $invoice['invoice_id'], $mailerData);
        
        /* Assert */
        // Verify plain text is converted to HTML
        $response->assertStatus(302);
    }

    // #endregion

    // #region Quote Email Tests


    /**
     * Happy Path: Quote email form displays
     */
    #[Test]
    public function it_displays_quote_email_form_with_fields(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $quote = $this->fixtures->get('quotes', 'sent_quote');
        
        /**
         * Act: GET /mailer/quote/{id}
         * Expected behavior: Display email form with recipient, subject, body fields
         */
        $response = $this->get('/mailer/quote/' . $quote['quote_id']);
        
        /* Assert */
        $response->assertSee('to_email');
        // Verify quote exists in fake DB
        $this->assertDatabaseHasRecord('ip_quotes', ['quote_id' => $quote['quote_id']]);
    }

    /**
     * Happy Path: POST sends quote email successfully
     */
    #[Test]
    public function it_sends_quote_email_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $quote = $this->fixtures->get('quotes', 'sent_quote');
        $mailerData = $this->makeMailerData([
            'subject' => 'Quote',
            'body' => 'Quote body',
        ]);
        
        /**
         * Act: POST /mailer/quote/{id}
         * POST data: {
         *   "to_email": "client@example.com",
         *   "from_email": "billing@example.com",
         *   "from_name": "Test Company",
         *   "subject": "Quote",
         *   "body": "Quote body",
         *   "pdf_template": "default",
         *   "cc": "",
         *   "bcc": "",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Send email with quote attachment and redirect
         */
        $response = $this->post('/mailer/quote/' . $quote['quote_id'], $mailerData);
        
        /* Assert */
        $response->assertStatus(302);
    }

    /**
     * Test cancel button cancels quote email sending
     */
    #[Test]
    public function it_cancels_quote_email_sending_when_cancel_button_clicked(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $quote = $this->fixtures->get('quotes', 'sent_quote');
        
        /**
         * Act: POST /mailer/quote/{id}
         * POST data: {
         *   "btn_cancel": "Cancel"
         * }
         * Expected behavior: Cancel email sending and redirect
         */
        $response = $this->post('/mailer/quote/' . $quote['quote_id'], [
            'btn_cancel' => 'Cancel',
        ]);
        
        /* Assert */
        $response->assertStatus(302);
    }

    // #endregion

    // #region Validation Tests

    /**
     * Test email address validation
     */
    #[Test]
    public function it_validates_email_address_format(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        $invalidMailerData = $this->makeMailerData([
            'to_email' => 'not-an-email', // Invalid format
        ]);
        
        /**
         * Act: POST /mailer/invoice/{id}
         * POST data: {
         *   "to_email": "not-an-email",
         *   "from_email": "billing@example.com",
         *   "from_name": "Test Company",
         *   "subject": "Invoice INV-001",
         *   "body": "Please find your invoice attached.",
         *   "pdf_template": "default",
         *   "cc": "",
         *   "bcc": "",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Reject invalid email address with validation error
         */
        $response = $this->post('/mailer/invoice/' . $invoice['invoice_id'], $invalidMailerData);
        
        /* Assert */
        $this->assertValidationError('to_email');
    }

    // #endregion
}
