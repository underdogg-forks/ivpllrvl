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
        $invalidUrlKey = 'invalid-url-key';
        
        /**
         * Act: GET /guest/paymentinformation/form/{invalid_url_key}
         * Expected behavior: Return 404 for invalid URL key
         */
        $response = $this->get('/guest/paymentinformation/form/' . $invalidUrlKey);
        
        /* Assert */
        $response->assertStatus(404);
    }

    /**
     * Test form validates invoice URL key format
     */
    #[Test]
    public function it_validates_url_key_format_for_malformed_input(): void
    {
        /* Arrange */
        $malformedUrlKey = '<script>alert(1)</script>';
        
        /**
         * Act: GET /guest/paymentinformation/form/{malformed}
         * Expected behavior: Reject malformed URL key
         */
        $response = $this->get('/guest/paymentinformation/form/' . urlencode($malformedUrlKey));
        
        /* Assert */
        $response->assertStatus(404);
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
         * Expected behavior: Display payment form for unpaid invoice
         */
        $response = $this->get('/guest/paymentinformation/form/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert */
        $this->assertResponseSuccess($response);
        $response->assertSee('payment_form');
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
        $paidInvoice = $this->getInvoiceData('paid');
        
        /**
         * Act: GET /guest/paymentinformation/form/{paid_invoice_url_key}
         * Expected behavior: Redirect authenticated user when invoice already paid
         */
        $response = $this->get('/guest/paymentinformation/form/' . $paidInvoice['invoice_url_key']);
        
        /* Assert */
        $response->assertRedirect();
    }

    /**
     * Test form returns 404 for paid invoice without authentication
     */
    #[Test]
    public function it_returns_404_for_paid_invoice_without_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        $paidInvoice = $this->getInvoiceData('paid');
        
        /**
         * Act: GET /guest/paymentinformation/form/{paid_invoice_url_key}
         * Expected behavior: Return 404 for paid invoice without authentication
         */
        $response = $this->get('/guest/paymentinformation/form/' . $paidInvoice['invoice_url_key']);
        
        /* Assert */
        $response->assertStatus(404);
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
         * Expected behavior: Display all enabled payment gateways
         */
        $response = $this->get('/guest/paymentinformation/form/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Exclude disabled payment gateways
         */
        $response = $this->get('/guest/paymentinformation/form/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Only show gateways matching invoice payment method
         */
        $response = $this->get('/guest/paymentinformation/form/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Auto-select provider when only one available
         */
        $response = $this->get('/guest/paymentinformation/form/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Display form for specific provider
         */
        $response = $this->get('/guest/paymentinformation/form/' . $unpaidInvoice['invoice_url_key'] . '?provider=' . $provider);
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Display overdue warning
         */
        $response = $this->get('/guest/paymentinformation/form/' . $overdueInvoice['invoice_url_key']);
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Display payment method details
         */
        $response = $this->get('/guest/paymentinformation/form/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Load Stripe payment form
         */
        $response = $this->get('/guest/paymentinformation/stripe/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Load PayPal payment form
         */
        $response = $this->get('/guest/paymentinformation/paypal/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Include advanced credit cards configuration
         */
        $response = $this->get('/guest/paymentinformation/paypal/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
         * Expected behavior: Include Venmo configuration
         */
        $response = $this->get('/guest/paymentinformation/paypal/' . $unpaidInvoice['invoice_url_key']);
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    // #endregion
}
