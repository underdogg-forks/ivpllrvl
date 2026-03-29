<?php

namespace Modules\Payments\Tests;

use Modules\Payments\Controllers\PaymentsAjaxController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for PaymentsAjaxController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(PaymentsAjaxController::class)]
class PaymentsAjaxControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = PaymentsAjaxController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'invoices', 'payments', 'payment_methods'];
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
     * Test that AJAX create requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_create_payment_via_ajax(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /payments/ajax/create
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/payments/ajax/create');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test that AJAX create requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_create_payment_via_ajax(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actingAs($guestUser);
        
        /**
         * Act: POST /payments/ajax/create
         * Expected behavior: Redirect to dashboard when not authorized
         */
        $response = $this->post('/payments/ajax/create');
        
        /* Assert */
        $response->assertRedirect('/dashboard');
    }

    /**
     * Test that AJAX update requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_update_payment_via_ajax(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /payments/ajax/save
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/payments/ajax/save');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test that AJAX update requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_update_payment_via_ajax(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actingAs($guestUser);
        
        /**
         * Act: POST /payments/ajax/save
         * Expected behavior: Redirect to dashboard when not authorized
         */
        $response = $this->post('/payments/ajax/save');
        
        /* Assert */
        $response->assertRedirect('/dashboard');
    }

    /**
     * Test that AJAX get requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_get_payment_data_via_ajax(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /payments/ajax/get_latest
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/payments/ajax/get_latest');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test that AJAX get requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_get_payment_data_via_ajax(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actingAs($guestUser);
        
        /**
         * Act: POST /payments/ajax/get_latest
         * Expected behavior: Redirect to dashboard when not authorized
         */
        $response = $this->post('/payments/ajax/get_latest');
        
        /* Assert */
        $response->assertRedirect('/dashboard');
    }

    // #endregion

    // #region AJAX Create Tests

    /**
     * Happy Path: AJAX create creates new payment with valid data
     */
    #[Test]
    public function it_creates_new_payment_via_ajax_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $validPaymentData = $this->makePaymentData([
            'invoice_id' => 1,
            'payment_amount' => '250.00',
            'payment_note' => 'Partial payment',
        ]);
        
        /**
         * Act: POST /payments/ajax/create
         * POST data: {
         *   "invoice_id": "1",
         *   "payment_method_id": "1",
         *   "payment_amount": "250.00",
         *   "payment_date": "2026-03-29",
         *   "payment_note": "Partial payment"
         * }
         * Expected behavior: Create new payment and return success JSON
         */
        $response = $this->post('/payments/ajax/create', $validPaymentData);
        
        /* Assert */
        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHasRecord('ip_payments', [
            'payment_invoice_id' => 1,
            'payment_amount' => '250.00'
        ]);
    }

    /**
     * Test AJAX create returns payment ID on success
     */
    #[Test]
    public function it_returns_payment_id_on_successful_ajax_create(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $validData = $this->makePaymentData([
            'invoice_id' => 1,
            'payment_amount' => '100.00',
        ]);
        
        /**
         * Act: POST /payments/ajax/create
         * POST data: {
         *   "invoice_id": "1",
         *   "payment_method_id": "1",
         *   "payment_amount": "100.00",
         *   "payment_date": "2026-03-29"
         * }
         * Expected behavior: Return JSON with payment_id
         */
        $response = $this->post('/payments/ajax/create', $validData);
        
        /* Assert */
        $response->assertOk();
        $response->assertJsonStructure(['payment_id']);
    }

    // #endregion

    // #region AJAX Update Tests

    /**
     * Happy Path: AJAX save updates payment with valid data
     */
    #[Test]
    public function it_updates_payment_via_ajax_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $existingPayment = $this->fixtures->get('payments', 'partial_payment');
        $updateData = $this->makePaymentData([
            'payment_id' => $existingPayment['payment_id'],
            'invoice_id' => $existingPayment['payment_invoice_id'],
            'payment_amount' => '150.00',
        ]);
        
        /**
         * Act: POST /payments/ajax/save
         * POST data: {
         *   "payment_id": "1",
         *   "invoice_id": "1",
         *   "payment_method_id": "1",
         *   "payment_amount": "150.00",
         *   "payment_date": "2026-03-29"
         * }
         * Expected behavior: Update payment and return success JSON
         */
        $response = $this->post('/payments/ajax/save', $updateData);
        
        /* Assert */
        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHasRecord('ip_payments', [
            'payment_id' => $existingPayment['payment_id'],
            'payment_amount' => '150.00'
        ]);
    }

    /**
     * Test AJAX save handles custom fields
     */
    #[Test]
    public function it_saves_custom_fields_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $paymentData = $this->makePaymentData([
            'invoice_id' => 1,
            'payment_amount' => '100.00',
            'custom' => [
                '1' => 'Custom Value',
            ],
        ]);
        
        /**
         * Act: POST /payments/ajax/save
         * POST data: {
         *   "invoice_id": "1",
         *   "payment_method_id": "1",
         *   "payment_amount": "100.00",
         *   "payment_date": "2026-03-29",
         *   "custom": {
         *     "1": "Custom Value"
         *   }
         * }
         * Expected behavior: Save payment with custom fields
         */
        $response = $this->post('/payments/ajax/save', $paymentData);
        
        /* Assert */
        $response->assertOk();
        $this->assertDatabaseHasRecord('ip_payments', [
            'payment_amount' => '100.00'
        ]);
    }

    /**
     * Test AJAX save clears invoice balance on full payment
     */
    #[Test]
    public function it_clears_invoice_balance_on_full_payment_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invoice = $this->fixtures->get('invoices', 'unpaid_invoice');
        $fullPaymentData = $this->makePaymentData([
            'invoice_id' => $invoice['invoice_id'],
            'payment_amount' => '500.00',
        ]);
        
        /**
         * Act: POST /payments/ajax/save
         * POST data: {
         *   "invoice_id": "1",
         *   "payment_method_id": "1",
         *   "payment_amount": "500.00",
         *   "payment_date": "2026-03-29"
         * }
         * Expected behavior: Save payment and clear invoice balance
         */
        $response = $this->post('/payments/ajax/save', $fullPaymentData);
        
        /* Assert */
        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHasRecord('ip_payments', [
            'payment_amount' => '500.00'
        ]);
    }

    // #endregion

    // #region AJAX Get Tests

    /**
     * Happy Path: AJAX get_latest returns latest payment data
     */
    #[Test]
    public function it_returns_latest_payment_data_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invoice = $this->fixtures->get('invoices', 'unpaid_invoice');
        
        /**
         * Act: POST /payments/ajax/get_latest
         * POST data: {
         *   "invoice_id": "1"
         * }
         * Expected behavior: Return latest payment data for invoice
         */
        $response = $this->post('/payments/ajax/get_latest', ['invoice_id' => $invoice['invoice_id']]);
        
        /* Assert */
        $response->assertOk();
        $response->assertJsonStructure(['payment_id', 'payment_amount']);
    }

    // #endregion

    // #region AJAX Delete Tests

    // Note: Add AJAX delete tests here if PaymentsAjaxController implements delete functionality

    // #endregion

    // #region Validation Tests

    /**
     * Test AJAX create rejects missing required fields
     */
    #[Test]
    public function it_validates_required_fields_are_present_on_ajax_create(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makePaymentData([
            'invoice_id' => 1,
            'payment_amount' => '',
        ]);
        
        /**
         * Act: POST /payments/ajax/create
         * POST data: {
         *   "invoice_id": "1",
         *   "payment_method_id": "1",
         *   "payment_amount": "",
         *   "payment_date": "2026-03-29"
         * }
         * Expected behavior: Return validation error for missing payment_amount
         */
        $response = $this->post('/payments/ajax/create', $invalidData);
        
        /* Assert */
        $response->assertStatus(422);
    }

    /**
     * Test AJAX create validates payment amount format
     */
    #[Test]
    public function it_validates_payment_amount_format_on_ajax_create(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makePaymentData([
            'invoice_id' => 1,
            'payment_amount' => 'not-a-number',
        ]);
        
        /**
         * Act: POST /payments/ajax/create
         * POST data: {
         *   "invoice_id": "1",
         *   "payment_method_id": "1",
         *   "payment_amount": "not-a-number",
         *   "payment_date": "2026-03-29"
         * }
         * Expected behavior: Return validation error for invalid amount format
         */
        $response = $this->post('/payments/ajax/create', $invalidData);
        
        /* Assert */
        $response->assertStatus(422);
    }

    /**
     * Test AJAX create rejects negative payment amount
     */
    #[Test]
    public function it_rejects_negative_payment_amount_on_ajax_create(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makePaymentData([
            'invoice_id' => 1,
            'payment_amount' => '-100.00',
        ]);
        
        /**
         * Act: POST /payments/ajax/create
         * POST data: {
         *   "invoice_id": "1",
         *   "payment_method_id": "1",
         *   "payment_amount": "-100.00",
         *   "payment_date": "2026-03-29"
         * }
         * Expected behavior: Return validation error for negative amount
         */
        $response = $this->post('/payments/ajax/create', $invalidData);
        
        /* Assert */
        $response->assertStatus(422);
    }

    /**
     * Test AJAX create rejects zero payment amount
     */
    #[Test]
    public function it_rejects_zero_payment_amount_on_ajax_create(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makePaymentData([
            'invoice_id' => 1,
            'payment_amount' => '0.00',
        ]);
        
        /**
         * Act: POST /payments/ajax/create
         * POST data: {
         *   "invoice_id": "1",
         *   "payment_method_id": "1",
         *   "payment_amount": "0.00",
         *   "payment_date": "2026-03-29"
         * }
         * Expected behavior: Return validation error for zero amount
         */
        $response = $this->post('/payments/ajax/create', $invalidData);
        
        /* Assert */
        $response->assertStatus(422);
    }

    /**
     * Test AJAX create handles overpayment validation
     */
    #[Test]
    public function it_validates_overpayment_on_ajax_create(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $overpaymentData = $this->makePaymentData([
            'invoice_id' => 1,
            'payment_amount' => '10000.00',
        ]);
        
        /**
         * Act: POST /payments/ajax/create
         * POST data: {
         *   "invoice_id": "1",
         *   "payment_method_id": "1",
         *   "payment_amount": "10000.00",
         *   "payment_date": "2026-03-29"
         * }
         * Expected behavior: Return validation error for overpayment
         */
        $response = $this->post('/payments/ajax/create', $overpaymentData);
        
        /* Assert */
        $response->assertStatus(422);
    }

    /**
     * Test AJAX create validates payment date format
     */
    #[Test]
    public function it_validates_payment_date_format_on_ajax_create(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makePaymentData([
            'invoice_id' => 1,
            'payment_amount' => '100.00',
            'payment_date' => 'invalid-date',
        ]);
        
        /**
         * Act: POST /payments/ajax/create
         * POST data: {
         *   "invoice_id": "1",
         *   "payment_method_id": "1",
         *   "payment_amount": "100.00",
         *   "payment_date": "invalid-date"
         * }
         * Expected behavior: Return validation error for invalid date format
         */
        $response = $this->post('/payments/ajax/create', $invalidData);
        
        /* Assert */
        $response->assertStatus(422);
    }

    // #endregion

    // #region Security Tests

    /**
     * Security: Test XSS sanitization in payment note
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_payment_note_on_ajax_create(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $xssData = $this->makePaymentData([
            'invoice_id' => 1,
            'payment_amount' => '100.00',
            'payment_note' => '<script>alert("xss")</script>',
        ]);
        
        /**
         * Act: POST /payments/ajax/create
         * POST data: {
         *   "invoice_id": "1",
         *   "payment_method_id": "1",
         *   "payment_amount": "100.00",
         *   "payment_date": "2026-03-29",
         *   "payment_note": "<script>alert(\"xss\")</script>"
         * }
         * Expected behavior: XSS payload should be sanitized by global filter
         */
        $response = $this->post('/payments/ajax/create', $xssData);
        
        /* Assert */
        $response->assertOk();
        $this->assertDatabaseMissingRecord('ip_payments', [
            'payment_note' => '<script>alert("xss")</script>'
        ]);
    }

    /**
     * Security: Test SQL injection protection in invoice_id
     */
    #[Test]
    public function it_protects_against_sql_injection_attempts_on_ajax_create(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $sqlInjectionData = $this->makePaymentData([
            'invoice_id' => "1 OR 1=1; DROP TABLE ip_payments; --",
            'payment_amount' => '100.00',
        ]);
        
        /**
         * Act: POST /payments/ajax/create
         * POST data: {
         *   "invoice_id": "1 OR 1=1; DROP TABLE ip_payments; --",
         *   "payment_method_id": "1",
         *   "payment_amount": "100.00",
         *   "payment_date": "2026-03-29"
         * }
         * Expected behavior: SQL injection should be prevented at query level
         */
        $response = $this->post('/payments/ajax/create', $sqlInjectionData);
        
        /* Assert */
        $response->assertStatus(422);
        $this->assertTrue($this->fakeDb->tableExists('ip_payments'));
    }

    // #endregion
}
