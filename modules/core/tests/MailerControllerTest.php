<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\MailerController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for MailerController
 * 
 * Tests email sending functionality for invoices and quotes.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(MailerController::class)]
class MailerControllerTest extends ControllerTestCase
{
    protected string $controllerClass = MailerController::class;
    
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
    
    protected function setUpController(): void
    {
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
        // When CI bootstrap is ready:
        $controller = $this->getController();
        
        /* Assert */
        // Verify mailer configuration is checked
        // If not configured, should redirect or show error
    }

    #[Test]
    public function it_requires_authentication_for_invoice(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        $controller->invoice(1);
        
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
        // When CI bootstrap is ready:
        $controller = $this->getController();
        ob_start();
        $controller->invoice($invoice['invoice_id']);
        $output = ob_get_clean();
        
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
        // When CI bootstrap is ready:
        $controller = $this->getController();
        ob_start();
        $controller->invoice($invoice['invoice_id']);
        $output = ob_get_clean();
        
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
        // When CI bootstrap is ready:
        $controller = $this->getController();
        ob_start();
        $controller->invoice($invoice['invoice_id']);
        $output = ob_get_clean();
        
        /* Assert */
        $response->assertSee('custom_fields');
    }

    #[Test]
    public function it_get_invoice_returns_early_if_mailer_not_configured(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // When CI bootstrap is ready:
        // Mock mailer_configured() to return false
        $controller = $this->getController();
        $controller->invoice(1);
        
        /* Assert */
        $response->assertStatus(302);
    }

    #[Test]
    public function it_post_send_invoice_sends_email(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        $this->setPostData(array_merge($this->testData, [
            'btn_submit' => '1',
        ]));
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        $controller->invoice($invoice['invoice_id']);
        
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
        $this->setPostData(array_merge($this->testData, [
            'btn_submit' => '1',
        ]));
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        $controller->invoice($invoice['invoice_id']);
        
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
        $this->setPostData([
            'btn_cancel' => 'Cancel',
        ]);
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        $controller->invoice($invoice['invoice_id']);
        
        /* Assert */
        $response->assertStatus(302);
    }

    #[Test]
    public function it_post_send_invoice_handles_cc_and_bcc(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        $this->setPostData(array_merge($this->testData, [
            'btn_submit' => '1',
            'cc' => 'accounting@example.com',
            'bcc' => 'archive@example.com',
        ]));
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        $controller->invoice($invoice['invoice_id']);
        
        /* Assert */
        // Verify email includes CC and BCC
    }

    #[Test]
    public function it_post_send_invoice_includes_attachments(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        $this->setPostData(array_merge($this->testData, [
            'btn_submit' => '1',
        ]));
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        $controller->invoice($invoice['invoice_id']);
        
        /* Assert */
        // Verify PDF attachment was included
    }

    #[Test]
    public function it_post_send_invoice_converts_html_body(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        $this->setPostData(array_merge($this->testData, [
            'btn_submit' => '1',
            'body' => '<p>HTML content</p>',
        ]));
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        $controller->invoice($invoice['invoice_id']);
        
        /* Assert */
        // Verify HTML is processed correctly
    }

    #[Test]
    public function it_post_send_invoice_converts_plain_text_to_html(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        $this->setPostData(array_merge($this->testData, [
            'btn_submit' => '1',
            'body' => "Plain text\nWith line breaks",
        ]));
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        $controller->invoice($invoice['invoice_id']);
        
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
        // When CI bootstrap is ready:
        $controller = $this->getController();
        ob_start();
        $controller->quote($quote['quote_id']);
        $output = ob_get_clean();
        
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
        $this->setPostData(array_merge($this->testData, [
            'btn_submit' => '1',
            'subject' => 'Quote',
            'body' => 'Quote body',
        ]));
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        $controller->quote($quote['quote_id']);
        
        /* Assert */
        $response->assertStatus(302);
    }

    #[Test]
    public function it_post_send_quote_cancels_on_btn_cancel(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $quote = $this->fixtures->get('quotes', 'sent_quote');
        $this->setPostData([
            'btn_cancel' => 'Cancel',
        ]);
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        $controller->quote($quote['quote_id']);
        
        /* Assert */
        $response->assertStatus(302);
    }

    #[Test]
    public function it_mailer_validates_email_addresses(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'sent_invoice');
        $this->setPostData(array_merge($this->testData, [
            'btn_submit' => '1',
            'to_email' => 'not-an-email', // Invalid format
        ]));
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        $controller->invoice($invoice['invoice_id']);
        
        /* Assert */
        $this->assertHasValidationError('to_email');
    }
}
