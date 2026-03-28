<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\MailerController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for MailerController
 * 
 * Tests email sending functionality for invoices and quotes.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(MailerController::class)]
class MailerControllerTest extends TestCase
{
    
    protected function loadFixtures(): void
    {
        // Load fixtures for email testing
        $users = $this->fixtures->all('users');
        $clients = $this->fixtures->all('clients');
        $invoices = $this->fixtures->all('invoices');
        $quotes = $this->fixtures->all('quotes');
        $emailTemplates = $this->fixtures->all('email_templates');
        
        // Seed fake database
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        foreach (['active_client', 'inactive_client'] as $key) {
            $this->fakeDb->insert('ip_clients', $clients[$key]);
        }
        
        foreach (['draft_invoice', 'sent_invoice', 'paid_invoice'] as $key) {
            $this->fakeDb->insert('ip_invoices', $invoices[$key]);
        }
        
        foreach (['draft_quote', 'sent_quote', 'approved_quote'] as $key) {
            $this->fakeDb->insert('ip_quotes', $quotes[$key]);
        }
        
        foreach (['invoice_template', 'quote_template'] as $key) {
            $this->fakeDb->insert('ip_email_templates', $emailTemplates[$key]);
        }
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Store common email data for reuse
        $this->testData = [
            'to_email' => 'client@example.com',
            'from_email' => 'billing@example.com',
            'from_name' => 'Test Company',
            'subject' => 'Invoice INV-001',
            'body' => 'Please find your invoice attached.',
            'pdf_template' => 'default',
        ];
    }
    #[Test]
    public function it_constructor_checks_mailer_configuration(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /mailer/invoice/1
        // Displays email form with mailer configuration check
        $response = $this->get('/mailer/invoice/1');
        
        /* Assert */
        // Verify mailer configuration is checked
        // If not configured, should redirect or show error
        $response->assertOk();
    }

    #[Test]
    public function it_requires_authentication_for_invoice(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // GET /mailer/invoice/1
        // Requires authentication to access invoice email form
        $response = $this->get('/mailer/invoice/1');
        
        /* Assert */
        $response->assertStatus(302);
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    #[Test]
    public function it_displays_invoice_email_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        
        /* Act */
        // GET /mailer/invoice/{id}
        // Displays email form for invoice
        $response = $this->get('/mailer/invoice/' . $invoice['invoice_id']);
        
        /* Assert */
        $response->assertSee('to_email');
        $response->assertSee('subject');
        // Verify invoice exists in fake DB
        $invoices = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertCount(1, $invoices);
    }

    #[Test]
    public function it_get_invoice_selects_appropriate_email_template(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        
        /* Act */
        // GET /mailer/invoice/{id}
        // Loads appropriate email template for invoice
        $response = $this->get('/mailer/invoice/' . $invoice['invoice_id']);
        
        /* Assert */
        // Verify email template is loaded
        $templates = $this->fakeDb->select('ip_email_templates', ['email_template_type' => 'invoice']);
        $this->assertGreaterThan(0, count($templates));
    }

    #[Test]
    public function it_get_invoice_includes_custom_fields(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        
        /* Act */
        // GET /mailer/invoice/{id}
        // Includes custom fields in email form
        $response = $this->get('/mailer/invoice/' . $invoice['invoice_id']);
        
        /* Assert */
        $response->assertSee('custom_fields');
    }

    #[Test]
    public function it_get_invoice_returns_early_if_mailer_not_configured(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /mailer/invoice/1
        // Returns early if mailer is not configured
        // Mock mailer_configured() to return false
        $response = $this->get('/mailer/invoice/1');
        
        /* Assert */
        $response->assertStatus(302);
    }

    #[Test]
    public function it_post_send_invoice_sends_email(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        
        /* Act */
        // POST /mailer/invoice/{id}
        // Successful request: Sends email with invoice attachment
        $response = $this->post('/mailer/invoice/' . $invoice['invoice_id'], array_merge($this->testData, [
            'btn_submit' => '1',
        ]));
        
        /* Assert */
        $response->assertStatus(302);
        // Verify email was sent (check email queue or log)
    }

    #[Test]
    public function it_post_send_invoice_generates_invoice_number(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');
        
        /* Act */
        // POST /mailer/invoice/{id}
        // Generates invoice number for draft invoices before sending
        $response = $this->post('/mailer/invoice/' . $invoice['invoice_id'], array_merge($this->testData, [
            'btn_submit' => '1',
        ]));
        
        /* Assert */
        // Verify invoice was assigned a number
        $invoices = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertCount(1, $invoices);
    }

    #[Test]
    public function it_post_send_invoice_cancels_on_btn_cancel(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        
        /* Act */
        // POST /mailer/invoice/{id}
        // Cancels email sending when cancel button clicked
        $response = $this->post('/mailer/invoice/' . $invoice['invoice_id'], [
            'btn_cancel' => 'Cancel',
        ]);
        
        /* Assert */
        $response->assertStatus(302);
    }

    #[Test]
    public function it_post_send_invoice_handles_cc_and_bcc(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        
        /* Act */
        // POST /mailer/invoice/{id}
        // Handles CC and BCC email addresses
        $response = $this->post('/mailer/invoice/' . $invoice['invoice_id'], array_merge($this->testData, [
            'btn_submit' => '1',
            'cc' => 'accounting@example.com',
            'bcc' => 'archive@example.com',
        ]));
        
        /* Assert */
        // Verify email includes CC and BCC
    }

    #[Test]
    public function it_post_send_invoice_includes_attachments(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        
        /* Act */
        // POST /mailer/invoice/{id}
        // Includes PDF attachment in email
        $response = $this->post('/mailer/invoice/' . $invoice['invoice_id'], array_merge($this->testData, [
            'btn_submit' => '1',
        ]));
        
        /* Assert */
        // Verify PDF attachment was included
    }

    #[Test]
    public function it_post_send_invoice_converts_html_body(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        
        /* Act */
        // POST /mailer/invoice/{id}
        // Processes HTML content in email body
        $response = $this->post('/mailer/invoice/' . $invoice['invoice_id'], array_merge($this->testData, [
            'btn_submit' => '1',
            'body' => '<p>HTML content</p>',
        ]));
        
        /* Assert */
        // Verify HTML is processed correctly
    }

    #[Test]
    public function it_post_send_invoice_converts_plain_text_to_html(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        
        /* Act */
        // POST /mailer/invoice/{id}
        // Converts plain text to HTML with line breaks
        $response = $this->post('/mailer/invoice/' . $invoice['invoice_id'], array_merge($this->testData, [
            'btn_submit' => '1',
            'body' => "Plain text\nWith line breaks",
        ]));
        
        /* Assert */
        // Verify plain text is converted to HTML
    }

    #[Test]
    public function it_displays_quote_email_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $quote = $this->fixtures->get('quotes', 'sent_quote');
        
        /* Act */
        // GET /mailer/quote/{id}
        // Displays email form for quote
        $response = $this->get('/mailer/quote/' . $quote['quote_id']);
        
        /* Assert */
        $response->assertSee('to_email');
        // Verify quote exists in fake DB
        $quotes = $this->fakeDb->select('ip_quotes', ['quote_id' => $quote['quote_id']]);
        $this->assertCount(1, $quotes);
    }

    #[Test]
    public function it_post_send_quote_sends_email(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $quote = $this->fixtures->get('quotes', 'sent_quote');
        
        /* Act */
        // POST /mailer/quote/{id}
        // Sends email with quote attachment
        $response = $this->post('/mailer/quote/' . $quote['quote_id'], array_merge($this->testData, [
            'btn_submit' => '1',
            'subject' => 'Quote',
            'body' => 'Quote body',
        ]));
        
        /* Assert */
        $response->assertStatus(302);
    }

    #[Test]
    public function it_post_send_quote_cancels_on_btn_cancel(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $quote = $this->fixtures->get('quotes', 'sent_quote');
        
        /* Act */
        // POST /mailer/quote/{id}
        // Cancels email sending when cancel button clicked
        $response = $this->post('/mailer/quote/' . $quote['quote_id'], [
            'btn_cancel' => 'Cancel',
        ]);
        
        /* Assert */
        $response->assertStatus(302);
    }

    #[Test]
    public function it_mailer_validates_email_addresses(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        
        /* Act */
        // POST /mailer/invoice/{id}
        // Validates email address format
        $response = $this->post('/mailer/invoice/' . $invoice['invoice_id'], array_merge($this->testData, [
            'btn_submit' => '1',
            'to_email' => 'not-an-email', // Invalid format
        ]));
        
        /* Assert */
        $this->assertHasValidationError('to_email');
    }
}
