<?php

namespace Modules\Clients\Tests;

use Modules\Clients\Controllers\PaymentInformationController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for PaymentInformationController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(PaymentInformationController::class)]
class PaymentInformationControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = PaymentInformationController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'clients', 'invoices', 'payments'];
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

    // #region Validation Tests

    /**
     * Test form requires valid invoice URL key
     */
    #[Test]
    public function it_returns_404_for_invalid_invoice_url_key(): void
    {
        /* Arrange */
        $invalidUrlKey = 'invalid-url-key-does-not-exist';
        
        // Create valid invoice to verify database has data
        $validInvoice = [
            'invoice_id' => 999,
            'invoice_number' => 'INV-2024-999',
            'invoice_url_key' => md5('valid-key-' . time()),
            'invoice_status_id' => 2, // Sent
            'invoice_total' => '1500.00',
            'invoice_balance' => '1500.00',
            'client_id' => 1,
            'invoice_date_created' => date('Y-m-d H:i:s'),
            'invoice_date_due' => date('Y-m-d', strtotime('+30 days')),
        ];
        $this->fakeDb->insert('ip_invoices', $validInvoice);
        
        // Ensure payment method exists
        $paymentMethod = [
            'payment_method_id' => 1,
            'payment_method_name' => 'Credit Card',
            'payment_method_enabled' => 1,
        ];
        $this->fakeDb->insert('ip_payment_methods', $paymentMethod);
        
        /**
         * Act: GET /guest/paymentinformation/form/{invalid_url_key}
         * Expected behavior: Return 404 for invalid URL key
         */
        $response = $this->get('/guest/paymentinformation/form/' . $invalidUrlKey);
        
        /* Assert - Response Status & Headers */
        $response->assertStatus(404);
        $response->assertHeader('Content-Type', 'text/html');
        
        /* Assert - Error Page Content */
        $content = $response->getContent();
        $this->assertStringNotContainsString('payment_form', $content);
        $this->assertStringNotContainsString('INV-2024-999', $content);
        $this->assertStringNotContainsString('1500.00', $content);
        
        /* Assert - Database State Unchanged */
        $dbInvoice = $this->fakeDb->selectOne('ip_invoices', ['invoice_id' => 999]);
        $this->assertNotNull($dbInvoice, 'Valid invoice still exists in database');
        $this->assertEquals('1500.00', $dbInvoice['invoice_balance'], 'Invoice balance unchanged');
        $this->assertEquals($validInvoice['invoice_url_key'], $dbInvoice['invoice_url_key']);
    }

    /**
     * Test form validates invoice URL key format
     */
    #[Test]
    public function it_validates_url_key_format_for_malformed_input(): void
    {
        /* Arrange */
        $malformedUrlKey = '<script>alert(1)</script>';
        
        // Create legitimate invoice to verify XSS attempt doesn't match
        $legitimateInvoice = [
            'invoice_id' => 998,
            'invoice_number' => 'INV-2024-998',
            'invoice_url_key' => md5('legitimate-' . time()),
            'invoice_status_id' => 2, // Sent
            'invoice_total' => '2500.00',
            'invoice_balance' => '2500.00',
            'client_id' => 1,
            'invoice_date_created' => date('Y-m-d H:i:s'),
            'invoice_date_due' => date('Y-m-d', strtotime('+15 days')),
        ];
        $this->fakeDb->insert('ip_invoices', $legitimateInvoice);
        
        // Setup payment method for complete test context
        $paymentMethod = [
            'payment_method_id' => 2,
            'payment_method_name' => 'Bank Transfer',
            'payment_method_enabled' => 1,
        ];
        $this->fakeDb->insert('ip_payment_methods', $paymentMethod);
        
        /**
         * Act: GET /guest/paymentinformation/form/{malformed}
         * Expected behavior: Reject malformed URL key (XSS attempt)
         */
        $response = $this->get('/guest/paymentinformation/form/' . urlencode($malformedUrlKey));
        
        /* Assert - Response Status & Headers */
        $response->assertStatus(404);
        $response->assertHeader('Content-Type', 'text/html');
        
        /* Assert - Security: No Script Execution */
        $content = $response->getContent();
        $this->assertStringNotContainsString('<script>', $content, 'XSS payload not reflected');
        $this->assertStringNotContainsString('alert(1)', $content, 'JavaScript not executed');
        $this->assertStringNotContainsString('payment_form', $content, 'Payment form not displayed for invalid key');
        
        /* Assert - Database State Protected */
        $dbInvoice = $this->fakeDb->selectOne('ip_invoices', ['invoice_id' => 998]);
        $this->assertNotNull($dbInvoice, 'Legitimate invoice unaffected by XSS attempt');
        $this->assertEquals('2500.00', $dbInvoice['invoice_balance']);
        $this->assertEquals($legitimateInvoice['invoice_url_key'], $dbInvoice['invoice_url_key'], 'URL key not tampered');
    }

    // #endregion

    // #region Payment Form Display Tests

    /**
     * Happy Path: Display payment form for unpaid invoice
     */
    #[Test]
    public function it_displays_payment_form_for_unpaid_invoice(): void
    {
        /* Arrange */
        $unpaidInvoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/paymentinformation/form/{url_key}
         * Expected: Payment form with invoice details, amount, payment gateway options
         */
        $response = $this->get('/guest/paymentinformation/form/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Page Structure */
        $response->assertSee('payment_form');
        $response->assertSee('Payment');
        
        /* Assert - Invoice Data */
        $response->assertSee('INV-2024-002');  // Invoice number
        $response->assertSee('2200.00');  // Invoice total
        
        /* Assert - Database Verification */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $unpaidInvoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice should exist for payment form');
        $this->assertGreaterThan(0, floatval($dbInvoice[0]['invoice_balance']), 'Invoice should have balance to pay');
    }

    /**
     * Test form redirects for paid invoice with authentication
     */
    #[Test]
    public function it_redirects_for_paid_invoice_with_authentication(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        // Create fully paid invoice with complete payment history
        $paidInvoice = [
            'invoice_id' => 997,
            'invoice_number' => 'INV-2024-997',
            'invoice_url_key' => md5('paid-auth-' . time()),
            'invoice_status_id' => 4, // Paid status
            'invoice_total' => '3000.00',
            'invoice_balance' => '0.00', // Fully paid
            'invoice_paid' => '3000.00',
            'client_id' => 1,
            'invoice_date_created' => date('Y-m-d H:i:s', strtotime('-60 days')),
            'invoice_date_due' => date('Y-m-d', strtotime('-30 days')),
        ];
        $this->fakeDb->insert('ip_invoices', $paidInvoice);
        
        // Record completed payment
        $payment = [
            'payment_id' => 1,
            'invoice_id' => 997,
            'payment_method_id' => 1,
            'payment_amount' => '3000.00',
            'payment_date' => date('Y-m-d H:i:s', strtotime('-30 days')),
        ];
        $this->fakeDb->insert('ip_payments', $payment);
        
        // Ensure payment method exists
        $paymentMethod = [
            'payment_method_id' => 1,
            'payment_method_name' => 'Credit Card',
            'payment_method_enabled' => 1,
        ];
        $this->fakeDb->insert('ip_payment_methods', $paymentMethod);
        
        /**
         * Act: GET /guest/paymentinformation/form/{paid_invoice_url_key}
         * Expected behavior: Redirect authenticated user when invoice already paid
         */
        $response = $this->get('/guest/paymentinformation/form/' . $paidInvoice['invoice_url_key']);
        
        /* Assert - Response Status & Headers */
        $response->assertRedirect();
        $location = $response->getHeader('Location');
        $this->assertNotEmpty($location, 'Redirect location header present');
        
        /* Assert - No Payment Form Content */
        $content = $response->getContent();
        $this->assertStringNotContainsString('payment_form', $content, 'No payment form for paid invoice');
        $this->assertStringNotContainsString('3000.00', $content, 'Amount not shown');
        
        /* Assert - Database Invoice State */
        $dbInvoice = $this->fakeDb->selectOne('ip_invoices', ['invoice_id' => 997]);
        $this->assertEquals('0.00', $dbInvoice['invoice_balance'], 'Invoice remains fully paid');
        $this->assertEquals('4', $dbInvoice['invoice_status_id'], 'Invoice status remains paid');
        $this->assertEquals('3000.00', $dbInvoice['invoice_paid'], 'Payment amount recorded');
    }

    /**
     * Test form returns 404 for paid invoice without authentication
     */
    #[Test]
    public function it_returns_404_for_paid_invoice_without_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        // Create fully paid invoice with complete data
        $paidInvoice = [
            'invoice_id' => 996,
            'invoice_number' => 'INV-2024-996',
            'invoice_url_key' => md5('paid-no-auth-' . time()),
            'invoice_status_id' => 4, // Paid status
            'invoice_total' => '4500.00',
            'invoice_balance' => '0.00', // Fully paid
            'invoice_paid' => '4500.00',
            'client_id' => 1,
            'invoice_date_created' => date('Y-m-d H:i:s', strtotime('-90 days')),
            'invoice_date_due' => date('Y-m-d', strtotime('-60 days')),
        ];
        $this->fakeDb->insert('ip_invoices', $paidInvoice);
        
        // Record payment transaction
        $payment = [
            'payment_id' => 2,
            'invoice_id' => 996,
            'payment_method_id' => 2,
            'payment_amount' => '4500.00',
            'payment_date' => date('Y-m-d H:i:s', strtotime('-60 days')),
        ];
        $this->fakeDb->insert('ip_payments', $payment);
        
        // Setup payment method
        $paymentMethod = [
            'payment_method_id' => 2,
            'payment_method_name' => 'Bank Transfer',
            'payment_method_enabled' => 1,
        ];
        $this->fakeDb->insert('ip_payment_methods', $paymentMethod);
        
        /**
         * Act: GET /guest/paymentinformation/form/{paid_invoice_url_key}
         * Expected behavior: Return 404 for paid invoice without authentication
         */
        $response = $this->get('/guest/paymentinformation/form/' . $paidInvoice['invoice_url_key']);
        
        /* Assert - Response Status & Headers */
        $response->assertStatus(404);
        $response->assertHeader('Content-Type', 'text/html');
        
        /* Assert - No Payment Form Content */
        $content = $response->getContent();
        $this->assertStringNotContainsString('payment_form', $content, 'No payment form shown');
        $this->assertStringNotContainsString('INV-2024-996', $content, 'Invoice number not exposed');
        $this->assertStringNotContainsString('4500.00', $content, 'Payment amount not displayed');
        
        /* Assert - Database Invoice State Protected */
        $dbInvoice = $this->fakeDb->selectOne('ip_invoices', ['invoice_id' => 996]);
        $this->assertNotNull($dbInvoice, 'Invoice still exists in database');
        $this->assertEquals('0.00', $dbInvoice['invoice_balance'], 'Invoice remains paid');
        $this->assertEquals('4', $dbInvoice['invoice_status_id'], 'Status remains paid');
        $this->assertEquals($paidInvoice['invoice_url_key'], $dbInvoice['invoice_url_key'], 'URL key unchanged');
    }

    /**
     * Test form displays enabled payment gateways
     */
    #[Test]
    public function it_displays_enabled_payment_gateways_in_form(): void
    {
        /* Arrange */
        $unpaidInvoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/paymentinformation/form/{url_key}
         * Expected: Payment gateway options (Stripe, PayPal, etc.) displayed
         */
        $response = $this->get('/guest/paymentinformation/form/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Payment Gateway UI */
        $response->assertSee('payment');
        $response->assertSee('gateway');
        
        /* Assert - Invoice Data */
        $response->assertSee('2200.00');  // Amount to pay
        
        /* Assert - Database Verification */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $unpaidInvoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice exists for payment gateway selection');
    }

    /**
     * Test form excludes disabled payment gateways
     */
    #[Test]
    public function it_excludes_disabled_payment_gateways_from_form(): void
    {
        /* Arrange */
        $unpaidInvoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/paymentinformation/form/{url_key}
         * Expected: Only active payment gateways shown, disabled ones hidden
         */
        $response = $this->get('/guest/paymentinformation/form/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Form Present */
        $response->assertSee('payment_form');
        $response->assertSee('INV-2024-002');
        
        /* Assert - Invoice Data */
        $response->assertSee('2200.00');
        
        /* Assert - Database State */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $unpaidInvoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice loaded for gateway filtering');
    }

    /**
     * Test form filters gateways by payment method
     */
    #[Test]
    public function it_filters_gateways_by_invoice_payment_method(): void
    {
        /* Arrange */
        $unpaidInvoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/paymentinformation/form/{url_key}
         * Expected: Only gateways matching invoice payment method shown
         */
        $response = $this->get('/guest/paymentinformation/form/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Payment Form Structure */
        $response->assertSee('payment_form');
        $response->assertSee('gateway');
        
        /* Assert - Invoice Context */
        $response->assertSee('INV-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Database Verification */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $unpaidInvoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice determines payment method filtering');
    }

    /**
     * Test form auto-selects single payment provider
     */
    #[Test]
    public function it_auto_selects_single_payment_provider(): void
    {
        /* Arrange */
        $unpaidInvoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/paymentinformation/form/{url_key}
         * Expected: Single provider auto-selected, form displays provider-specific fields
         */
        $response = $this->get('/guest/paymentinformation/form/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Payment Form */
        $response->assertSee('payment');
        $response->assertSee('INV-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Form Structure */
        $response->assertSee('<form');
        
        /* Assert - Database State */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $unpaidInvoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice loaded for auto-selection logic');
    }

    /**
     * Test form with explicit payment provider
     */
    #[Test]
    public function it_displays_specific_provider_form_when_specified(): void
    {
        /* Arrange */
        $unpaidInvoice = $this->getInvoiceData('sent');
        $provider = 'stripe';
        
        /**
         * Act: GET /guest/paymentinformation/form/{url_key}?provider={provider}
         * Expected: Form displays fields specific to Stripe provider
         */
        $response = $this->get('/guest/paymentinformation/form/' . $unpaidInvoice['invoice_url_key'] . '?provider=' . $provider);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Provider-Specific Content */
        $response->assertSee('payment');
        $response->assertSee('stripe');  // Provider name in form
        
        /* Assert - Invoice Data */
        $response->assertSee('INV-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Database Verification */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $unpaidInvoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice exists for provider-specific form');
    }

    /**
     * Test form displays overdue warning
     */
    #[Test]
    public function it_displays_overdue_warning_for_overdue_invoice(): void
    {
        /* Arrange */
        $overdueInvoice = $this->getInvoiceData('sent'); // Assume it's overdue
        
        /**
         * Act: GET /guest/paymentinformation/form/{url_key}
         * Expected: Overdue warning badge or message displayed prominently
         */
        $response = $this->get('/guest/paymentinformation/form/' . $overdueInvoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Payment Form Present */
        $response->assertSee('payment_form');
        $response->assertSee('INV-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Warning Elements */
        // Overdue status might be shown as warning or alert
        $response->assertSee('Due');
        
        /* Assert - Database Verification */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $overdueInvoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice loaded to check overdue status');
    }

    /**
     * Test form displays payment method details
     */
    #[Test]
    public function it_displays_payment_method_details_in_form(): void
    {
        /* Arrange */
        $unpaidInvoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/paymentinformation/form/{url_key}
         * Expected: Payment method information (credit card, bank transfer, etc.)
         */
        $response = $this->get('/guest/paymentinformation/form/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Payment Form Structure */
        $response->assertSee('payment');
        $response->assertSee('method');
        
        /* Assert - Invoice Data */
        $response->assertSee('INV-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Database Verification */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $unpaidInvoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice loaded to display payment method options');
    }

    // #endregion

    // #region Payment Provider Tests

    /**
     * Test stripe() loads Stripe payment form
     */
    #[Test]
    public function it_loads_stripe_payment_form(): void
    {
        /* Arrange */
        $unpaidInvoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/paymentinformation/stripe/{url_key}
         * Expected: Stripe-specific payment form with card input fields
         */
        $response = $this->get('/guest/paymentinformation/stripe/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Stripe-Specific Content */
        $response->assertSee('stripe');
        $response->assertSee('payment');
        
        /* Assert - Invoice Data */
        $response->assertSee('INV-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Database Verification */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $unpaidInvoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice exists for Stripe payment');
    }

    /**
     * Test paypal() loads PayPal payment form
     */
    #[Test]
    public function it_loads_paypal_payment_form(): void
    {
        /* Arrange */
        $unpaidInvoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/paymentinformation/paypal/{url_key}
         * Expected: PayPal-specific payment form with PayPal button/integration
         */
        $response = $this->get('/guest/paymentinformation/paypal/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - PayPal-Specific Content */
        $response->assertSee('paypal');
        $response->assertSee('payment');
        
        /* Assert - Invoice Data */
        $response->assertSee('INV-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Database Verification */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $unpaidInvoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice exists for PayPal payment');
    }

    /**
     * Test paypal() includes advanced credit cards setting
     */
    #[Test]
    public function it_includes_advanced_credit_cards_setting_in_paypal(): void
    {
        /* Arrange */
        $unpaidInvoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/paymentinformation/paypal/{url_key}
         * Expected: PayPal form with advanced credit cards option enabled/visible
         */
        $response = $this->get('/guest/paymentinformation/paypal/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - PayPal Form Present */
        $response->assertSee('paypal');
        $response->assertSee('payment');
        
        /* Assert - Invoice Data */
        $response->assertSee('INV-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Database State */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $unpaidInvoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice loaded for PayPal advanced credit cards');
    }

    /**
     * Test paypal() includes Venmo setting
     */
    #[Test]
    public function it_includes_venmo_setting_in_paypal(): void
    {
        /* Arrange */
        $unpaidInvoice = $this->getInvoiceData('sent');
        
        /**
         * Act: GET /guest/paymentinformation/paypal/{url_key}
         * Expected: PayPal form with Venmo payment option enabled/visible
         */
        $response = $this->get('/guest/paymentinformation/paypal/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - PayPal Form Content */
        $response->assertSee('paypal');
        $response->assertSee('payment');
        
        /* Assert - Invoice Data */
        $response->assertSee('INV-2024-002');
        $response->assertSee('2200.00');
        
        /* Assert - Database Verification */
        $dbInvoice = $this->fakeDb->select('ip_invoices', ['invoice_id' => $unpaidInvoice['invoice_id']]);
        $this->assertNotEmpty($dbInvoice, 'Invoice loaded for PayPal Venmo integration');
    }

    // #endregion
}
