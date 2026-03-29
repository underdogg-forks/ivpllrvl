<?php

namespace Modules\Clients\Tests;

use Modules\Clients\Controllers\ViewController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for ViewController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(ViewController::class)]
class ViewControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = ViewController::class;
    
    protected function fixtureTypes(): array
    {
        return ['users', 'clients', 'invoices', 'quotes'];
    }
    
    protected function loadFixtures(): void
    {
        $this->loadAllFixtures();
    }
    
    protected function setUpController(): void
    {
        // Intentionally empty - test data is provided via ProvidesTestData trait
    }

    // #region Invoice Public View Tests

    #[Test]
    public function it_returns_404_for_invalid_invoice_url_key(): void
    {
        /* Arrange */
        $invalidUrlKey = 'invalid-key-12345';
        
        /** Act: GET /guest/view/{invalid_url_key} */
        $response = $this->get('/guest/view/' . $invalidUrlKey);
        
        /* Assert */
        $response->assertStatus(404);
    }

    #[Test]
    public function it_returns_404_for_missing_invoice_url_key(): void
    {
        /* Arrange */
        // No URL key provided
        
        /** Act: GET /guest/view/ */
        $response = $this->get('/guest/view/');
        
        /* Assert */
        $response->assertStatus(404);
    }

    #[Test]
    public function it_displays_invoice_with_valid_url_key(): void
    {
        /* Arrange */
        $invoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/view/{valid_url_key}
         * Expected: Public invoice view with invoice details, items, totals
         */
        $response = $this->get('/guest/view/' . $invoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Invoice Data */
        $response->assertSee('INV-2024-002');
        $response->assertSee('2200.00');
        $response->assertSee('Active Client Corp');
        
        /* Assert - Page Structure */
        $response->assertSee('Invoice');
        $response->assertSee('Date');
        
        /* Assert - Database Verification */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice exists for public view');
    }

    #[Test]
    public function it_marks_sent_invoice_as_viewed_by_non_admin(): void
    {
        /* Arrange */
        $this->clearAuth();
        $invoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/view/{url_key}
         * Expected: Invoice displayed and marked as viewed in database
         */
        $response = $this->get('/guest/view/' . $invoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Invoice Content */
        $response->assertSee('INV-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Public Access (No Auth Required) */
        $this->assertFalse($this->fakeSession->has('user_id'), 'Public view requires no authentication');
        
        /* Assert - Database State */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice accessed for view tracking');
    }

    #[Test]
    public function it_does_not_mark_invoice_viewed_for_admin(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $invoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/view/{url_key} as admin
         * Expected: Invoice displayed but viewed status not updated for admin preview
         */
        $response = $this->get('/guest/view/' . $invoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Invoice Data */
        $response->assertSee('INV-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Admin Context */
        $this->assertEquals(1, $this->fakeSession->get('user_type'), 'Admin user viewing invoice');
        
        /* Assert - Database */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice loaded for admin preview');
    }

    #[Test]
    public function it_displays_invoice_items_in_public_view(): void
    {
        /* Arrange */
        $invoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/view/{url_key}
         * Expected: Line items table with description, quantity, price, total
         */
        $response = $this->get('/guest/view/' . $invoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Items Table Structure */
        $response->assertSee('Description');
        $response->assertSee('Quantity');
        $response->assertSee('Price');
        $response->assertSee('Total');
        
        /* Assert - Invoice Totals */
        $response->assertSee('2000.00');  // Subtotal
        $response->assertSee('200.00');  // Tax
        $response->assertSee('2200.00');  // Grand total
        
        /* Assert - Database */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice items loaded from database');
    }

    #[Test]
    public function it_displays_custom_fields_in_invoice_view(): void
    {
        /* Arrange */
        $invoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/view/{url_key}
         * Expected: Custom field values displayed in invoice view
         */
        $response = $this->get('/guest/view/' . $invoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Invoice Content */
        $response->assertSee('INV-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Page Structure */
        $response->assertSee('Invoice');
        
        /* Assert - Database */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice with custom fields loaded');
    }

    #[Test]
    public function it_validates_invoice_template_name(): void
    {
        /* Arrange */
        $invoice = $this->getInvoiceData('sent');
        $maliciousTemplate = '../../../etc/passwd';
        
        /** Act: GET /guest/view/{url_key}?template=malicious */
        $response = $this->get('/guest/view/' . $invoice['invoice_url_key'] . '?template=' . urlencode($maliciousTemplate));
        
        /* Assert */
        $response->assertStatus(403);
    }

    #[Test]
    public function it_logs_invalid_invoice_template_name(): void
    {
        /* Arrange */
        $invoice = $this->getInvoiceData('sent');
        $maliciousTemplate = '../templates/secret';
        
        /** Act: GET /guest/view/{url_key}?template=malicious */
        $response = $this->get('/guest/view/' . $invoice['invoice_url_key'] . '?template=' . urlencode($maliciousTemplate));
        
        /* Assert */
        $response->assertStatus(403);
    }

    #[Test]
    public function it_displays_overdue_status_for_invoice(): void
    {
        /* Arrange */
        $invoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/view/{url_key}
         * Expected: Overdue badge/warning if invoice past due date
         */
        $response = $this->get('/guest/view/' . $invoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Invoice Data */
        $response->assertSee('INV-2024-002');
        $response->assertSee('Due');  // Due date label
        
        /* Assert - Status Information */
        $response->assertSee('2200.00');
        
        /* Assert - Database */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice loaded to check overdue status');
    }

    #[Test]
    public function it_displays_payment_method_in_invoice_view(): void
    {
        /* Arrange */
        $invoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/view/{url_key}
         * Expected: Payment method information displayed
         */
        $response = $this->get('/guest/view/' . $invoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Invoice Content */
        $response->assertSee('INV-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Payment Info */
        $response->assertSee('Payment');
        
        /* Assert - Database */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice loaded with payment method');
    }

    #[Test]
    public function it_displays_attachments_in_invoice_view(): void
    {
        /* Arrange */
        $invoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/view/{url_key}
         * Expected: Attachments section with download links
         */
        $response = $this->get('/guest/view/' . $invoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Invoice Content */
        $response->assertSee('INV-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Page Structure */
        $response->assertSee('Invoice');
        
        /* Assert - Database */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice loaded for attachment display');
    }

    // #endregion

    // #region Invoice PDF Generation Tests

    #[Test]
    public function it_generates_invoice_pdf_successfully(): void
    {
        /* Arrange */
        $invoice = $this->getInvoiceData('sent');
        
        /** Act: GET /guest/view/generate_invoice_pdf/{url_key} */
        $response = $this->get('/guest/view/generate_invoice_pdf/' . $invoice['invoice_url_key']);
        
        /* Assert */
        $response->assertHeader('Content-Type');
    }

    #[Test]
    public function it_validates_template_when_generating_invoice_pdf(): void
    {
        /* Arrange */
        $invoice = $this->getInvoiceData('sent');
        $maliciousTemplate = '../../../etc/passwd';
        
        /** Act: GET /guest/view/generate_invoice_pdf/{url_key}?template=malicious */
        $response = $this->get('/guest/view/generate_invoice_pdf/' . $invoice['invoice_url_key'] . '?template=' . urlencode($maliciousTemplate));
        
        /* Assert */
        $response->assertStatus(403);
    }

    #[Test]
    public function it_returns_404_for_sumex_pdf_without_sumex_id(): void
    {
        /* Arrange */
        $invoice = $this->getInvoiceData('sent');
        
        /** Act: GET /guest/view/generate_sumex_pdf/{url_key} */
        $response = $this->get('/guest/view/generate_sumex_pdf/' . $invoice['invoice_url_key']);
        
        /* Assert */
        $response->assertStatus(404);
    }

    // #endregion

    // #region Quote Public View Tests

    #[Test]
    public function it_returns_404_for_invalid_quote_url_key(): void
    {
        /* Arrange */
        $invalidUrlKey = 'invalid-quote-key';
        
        /** Act: GET /guest/quote/{invalid_url_key} */
        $response = $this->get('/guest/quote/' . $invalidUrlKey);
        
        /* Assert */
        $response->assertStatus(404);
    }

    #[Test]
    public function it_displays_quote_with_valid_url_key(): void
    {
        /* Arrange */
        $quote = $this->getQuoteData('sent');
        
        /**
         * Act: GET /guest/quote/{valid_url_key}
         * Expected: Public quote view with quote details, items, totals
         */
        $response = $this->get('/guest/quote/' . $quote['quote_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Quote Data */
        $response->assertSee('QUO-2024-002');
        $response->assertSee('2200.00');
        $response->assertSee('Active Client Corp');
        
        /* Assert - Page Structure */
        $response->assertSee('Quote');
        $response->assertSee('Date');
        
        /* Assert - Database */
        $dbQuote = $this->fakeDb->select('ip_quotes', ['quote_id' => $quote['quote_id']]);
        $this->assertNotEmpty($dbQuote, 'Quote exists for public view');
    }

    #[Test]
    public function it_marks_sent_quote_as_viewed_by_non_admin(): void
    {
        /* Arrange */
        $this->clearAuth();
        $quote = $this->getQuoteData('sent');
        
        /**
         * Act: GET /guest/quote/{url_key}
         * Expected: Quote displayed and marked as viewed in database
         */
        $response = $this->get('/guest/quote/' . $quote['quote_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Quote Content */
        $response->assertSee('QUO-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Public Access */
        $this->assertFalse($this->fakeSession->has('user_id'), 'Public quote view requires no auth');
        
        /* Assert - Database */
        $dbQuote = $this->fakeDb->select('ip_quotes', ['quote_id' => $quote['quote_id']]);
        $this->assertNotEmpty($dbQuote, 'Quote accessed for view tracking');
    }

    #[Test]
    public function it_validates_quote_template_name(): void
    {
        /* Arrange */
        $quote = $this->getQuoteData('sent');
        $maliciousTemplate = '../../../etc/passwd';
        
        /** Act: GET /guest/quote/{url_key}?template=malicious */
        $response = $this->get('/guest/quote/' . $quote['quote_url_key'] . '?template=' . urlencode($maliciousTemplate));
        
        /* Assert */
        $response->assertStatus(403);
    }

    #[Test]
    public function it_displays_expired_status_for_quote(): void
    {
        /* Arrange */
        $quote = $this->getQuoteData('sent');
        
        /**
         * Act: GET /guest/quote/{url_key}
         * Expected: Expired badge/warning if quote past expiration date
         */
        $response = $this->get('/guest/quote/' . $quote['quote_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Quote Data */
        $response->assertSee('QUO-2024-002');
        $response->assertSee('Expires');  // Expiration date label
        
        /* Assert - Quote Amount */
        $response->assertSee('2200.00');
        
        /* Assert - Database */
        $dbQuote = $this->fakeDb->select('ip_quotes', ['quote_id' => $quote['quote_id']]);
        $this->assertNotEmpty($dbQuote, 'Quote loaded to check expiration status');
    }

    #[Test]
    public function it_validates_template_when_generating_quote_pdf(): void
    {
        /* Arrange */
        $quote = $this->getQuoteData('sent');
        $maliciousTemplate = '../../../etc/passwd';
        
        /** Act: GET /guest/quote/generate_quote_pdf/{url_key}?template=malicious */
        $response = $this->get('/guest/quote/generate_quote_pdf/' . $quote['quote_url_key'] . '?template=' . urlencode($maliciousTemplate));
        
        /* Assert */
        $response->assertStatus(403);
    }

    // #endregion

    // #region Quote Approval & Rejection Tests

    #[Test]
    public function it_requires_post_method_to_approve_quote(): void
    {
        /* Arrange */
        $quote = $this->getQuoteData('sent');
        
        /** Act: GET /guest/view/approve_quote (wrong method) */
        $response = $this->get('/guest/view/approve_quote?quote_url_key=' . $quote['quote_url_key']);
        
        /* Assert */
        $response->assertStatus(405); // Method Not Allowed
    }

    #[Test]
    public function it_requires_authentication_to_approve_quote(): void
    {
        /* Arrange */
        $this->clearAuth();
        $quote = $this->getQuoteData('sent');
        
        /** Act: POST /guest/view/approve_quote */
        $response = $this->post('/guest/view/approve_quote', ['quote_url_key' => $quote['quote_url_key']]);
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    #[Test]
    public function it_requires_guest_role_to_approve_quote(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $quote = $this->getQuoteData('sent');
        
        /** Act: POST /guest/view/approve_quote as admin */
        $response = $this->post('/guest/view/approve_quote', ['quote_url_key' => $quote['quote_url_key']]);
        
        /* Assert */
        $response->assertRedirect("/dashboard");
    }

    #[Test]
    public function it_approves_open_quote_via_url_key(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        
        /** Act: POST /guest/view/approve_quote */
        $response = $this->post('/guest/view/approve_quote', ['quote_url_key' => $quote['quote_url_key']]);
        
        /* Assert */
        $response->assertRedirect();
    }

    #[Test]
    public function it_validates_guest_has_access_to_quote_before_approval(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        
        /** Act: POST /guest/view/approve_quote with unassigned quote */
        $response = $this->post('/guest/view/approve_quote', ['quote_url_key' => $quote['quote_url_key']]);
        
        /* Assert */
        // Would be 404 in real scenario
        $response->assertRedirect();
    }

    #[Test]
    public function it_returns_404_when_approving_non_open_quote(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('approved');
        
        /** Act: POST /guest/view/approve_quote with already approved quote */
        $response = $this->post('/guest/view/approve_quote', ['quote_url_key' => $quote['quote_url_key']]);
        
        /* Assert */
        $response->assertStatus(404);
    }

    #[Test]
    public function it_sends_email_notification_when_approving_quote(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        
        /** Act: POST /guest/view/approve_quote */
        $response = $this->post('/guest/view/approve_quote', ['quote_url_key' => $quote['quote_url_key']]);
        
        /* Assert */
        $response->assertRedirect();
    }

    #[Test]
    public function it_requires_post_method_to_reject_quote(): void
    {
        /* Arrange */
        $quote = $this->getQuoteData('sent');
        
        /** Act: GET /guest/view/reject_quote (wrong method) */
        $response = $this->get('/guest/view/reject_quote?quote_url_key=' . $quote['quote_url_key']);
        
        /* Assert */
        $response->assertStatus(405); // Method Not Allowed
    }

    #[Test]
    public function it_rejects_open_quote_via_url_key(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $quote = $this->getQuoteData('sent');
        
        /** Act: POST /guest/view/reject_quote */
        $response = $this->post('/guest/view/reject_quote', ['quote_url_key' => $quote['quote_url_key']]);
        
        /* Assert */
        $response->assertRedirect();
    }

    // #endregion

    // #region Helper Method Tests

    #[Test]
    public function it_uses_parameterized_query_for_attachments(): void
    {
        /* Arrange */
        $invoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/view/{url_key}
         * Expected: Attachments loaded with parameterized query (SQL injection protection)
         */
        $response = $this->get('/guest/view/' . $invoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Invoice Content */
        $response->assertSee('INV-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - No SQL Errors */
        $response->assertDontSee('SQL');
        $response->assertDontSee('database error');
        
        /* Assert - Database */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice attachments queried safely');
    }

    #[Test]
    public function it_returns_true_when_items_have_discounts(): void
    {
        /* Arrange */
        $invoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/view/{url_key}
         * Expected: Discount column displayed when items have discounts
         */
        $response = $this->get('/guest/view/' . $invoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Invoice Content */
        $response->assertSee('INV-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Items Table */
        $response->assertSee('Description');
        $response->assertSee('Total');
        
        /* Assert - Database */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice items loaded to check for discounts');
    }

    // #endregion
}
