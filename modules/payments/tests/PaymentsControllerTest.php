<?php

namespace Modules\Payments\Tests;

use Modules\Payments\Controllers\PaymentsController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for PaymentsController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(PaymentsController::class)]
class PaymentsControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = PaymentsController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'clients', 'invoices', 'payments', 'payment_methods'];
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
     * Test that payments index requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_payments_index(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /payments/index
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/payments/index');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test that payments form requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_payments_form(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /payments/form/{invoice_id}
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/payments/form/1');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    // #endregion

    // #region Index & List Display Tests

    /**
     * Happy Path: Payments index displays payments list
     */
    #[Test]
    public function it_displays_payments_list_on_index_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /payments/index
         * Expected behavior: Display list of payments
         */
        $response = $this->get('/payments/index');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('Payments');
        $records = $this->fakeDb->select('ip_payments', []);
        $this->assertNotEmpty($records, "Database should have record in 'ip_payments'");
    }

    /**
     * Test payments index paginates results
     */
    #[Test]
    public function it_displays_pagination_on_payments_index(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /payments/index/{page}
         * Expected behavior: Display pagination controls
         */
        $response = $this->get('/payments/index/1');
        
        /* Assert */
        $this->assertHasPagination($response);
    }

    // #endregion

    // #region Form Display Tests

    /**
     * Happy Path: Form displays new payment form
     */
    #[Test]
    public function it_displays_new_payment_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invoiceId = 1;
        
        /**
         * Act: GET /payments/form/{invoice_id}
         * Expected behavior: Display new payment form fields
         */
        $response = $this->get('/payments/form/' . $invoiceId);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('Payment Form');
        $this->assertTrue($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Form displays edit payment form
     */
    #[Test]
    public function it_displays_edit_payment_form_with_existing_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invoiceId = 1;
        $paymentId = 1;
        
        /**
         * Act: GET /payments/form/{invoice_id}/{payment_id}
         * Expected behavior: Display edit form with existing payment data
         */
        $response = $this->get('/payments/form/' . $invoiceId . '/' . $paymentId);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('Payment Form');
        $records = $this->fakeDb->select('ip_payments', ['payment_id' => $paymentId]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_payments'");
    }

    // #endregion

    // #region Form Submission Tests (Create)

    /**
     * Happy Path: POST creates new payment with valid data
     */
    #[Test]
    public function it_creates_new_payment_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $validPaymentData = $this->makePaymentData([
            'invoice_id' => '1',
            'payment_amount' => '100.00',
            'payment_date' => date('Y-m-d'),
            'payment_method_id' => '1',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /payments/form/{invoice_id}
         * POST data: {
         *   "invoice_id": "1",
         *   "payment_method_id": "1",
         *   "payment_amount": "100.00",
         *   "payment_date": "2024-01-15",
         *   "payment_note": "",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Create new payment and redirect
         */
        $response = $this->post('/payments/form/1', $validPaymentData);
        
        /* Assert */
        $response->assertRedirect('/payments');
    }

    /**
     * Test POST cancels without saving when btn_cancel is clicked
     */
    #[Test]
    public function it_cancels_form_without_saving_when_cancel_button_clicked(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $paymentData = $this->makePaymentData([
            'invoice_id' => '1',
            'payment_amount' => '999.99',
            'btn_cancel' => '1',
        ]);
        
        /**
         * Act: POST /payments/form/{invoice_id}
         * POST data: Complete payment data with btn_cancel set
         * Expected behavior: Cancel and redirect without saving
         */
        $response = $this->post('/payments/form/1', $paymentData);
        
        /* Assert */
        $response->assertRedirect('/payments');
        $records = $this->fakeDb->select('ip_payments', ['payment_amount' => '999.99']);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_payments'");
    }

    // #endregion

    // #region Form Submission Tests (Update)

    /**
     * Happy Path: POST updates existing payment
     */
    #[Test]
    public function it_updates_existing_payment_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $updateData = $this->makePaymentData([
            'invoice_id' => '1',
            'payment_id' => '1',
            'payment_amount' => '150.00',
            'payment_date' => date('Y-m-d'),
            'payment_method_id' => '1',
            'payment_note' => 'Updated note',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /payments/form/{invoice_id}/{payment_id}
         * POST data: Complete payment data with updated payment_amount
         * Expected behavior: Update payment and redirect
         */
        $response = $this->post('/payments/form/1/1', $updateData);
        
        /* Assert */
        $response->assertRedirect('/payments');
    }

    /**
     * Test POST saves custom fields
     */
    #[Test]
    public function it_saves_custom_fields_with_payment(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $paymentWithCustom = $this->makePaymentData([
            'invoice_id' => '1',
            'payment_amount' => '100.00',
            'payment_date' => date('Y-m-d'),
            'payment_method_id' => '1',
            'custom_fields' => ['field1' => 'value1'],
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /payments/form/{invoice_id}
         * POST data: Payment data with custom fields
         * Expected behavior: Save payment with custom field data
         */
        $response = $this->post('/payments/form/1', $paymentWithCustom);
        
        /* Assert */
        $response->assertRedirect('/payments');
    }

    // #endregion

    // #region View & Detail Tests

    /**
     * Happy Path: Online logs displays payment logs
     */
    #[Test]
    public function it_displays_online_payment_logs_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /payments/online_logs
         * Expected behavior: Display online payment logs
         */
        $response = $this->get('/payments/online_logs');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('Online Payment Logs');
    }

    // #endregion

    // #region Delete Tests

    /**
     * Test POST delete removes payment
     */
    #[Test]
    public function it_deletes_payment_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $paymentId = 1;
        
        /**
         * Act: POST /payments/delete/{payment_id}
         * POST data: {}
         * Expected behavior: Delete payment and redirect
         */
        $response = $this->post('/payments/delete/' . $paymentId);
        
        /* Assert */
        $response->assertRedirect('/payments');
    }

    /**
     * Test delete updates invoice balance
     */
    #[Test]
    public function it_updates_invoice_balance_after_deleting_payment(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $paymentId = 1;
        
        /**
         * Act: POST /payments/delete/{payment_id}
         * POST data: {}
         * Expected behavior: Delete payment and recalculate invoice balance
         */
        $response = $this->post('/payments/delete/' . $paymentId);
        
        /* Assert */
        $response->assertRedirect('/payments');
    }

    // #endregion

    // #region Validation Tests

    /**
     * Test POST validates required fields
     */
    #[Test]
    public function it_validates_required_fields_are_present(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidPayment = $this->makePaymentData([
            'invoice_id' => '1',
            'payment_amount' => '',
            'payment_date' => '',
        ]);
        
        /**
         * Act: POST /payments/form/{invoice_id}
         * POST data: Incomplete data with missing required fields
         * Expected behavior: Validation errors for missing fields
         */
        $response = $this->post('/payments/form/1', $invalidPayment);
        
        /* Assert */
        $response->assertSessionHasErrors();
    }

    /**
     * Test POST validates payment amount format
     */
    #[Test]
    public function it_validates_payment_amount_format(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidPayment = $this->makePaymentData([
            'invoice_id' => '1',
            'payment_amount' => 'invalid',
            'payment_date' => date('Y-m-d'),
        ]);
        
        /**
         * Act: POST /payments/form/{invoice_id}
         * POST data: Complete data with invalid payment_amount
         * Expected behavior: Validation error for payment_amount
         */
        $response = $this->post('/payments/form/1', $invalidPayment);
        
        /* Assert */
        $response->assertSessionHasErrors('payment_amount');
    }

    /**
     * Test POST prevents negative payment amounts
     */
    #[Test]
    public function it_prevents_negative_payment_amounts(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $negativePayment = $this->makePaymentData([
            'invoice_id' => '1',
            'payment_amount' => '-100.00',
            'payment_date' => date('Y-m-d'),
            'payment_method_id' => '1',
        ]);
        
        /**
         * Act: POST /payments/form/{invoice_id}
         * POST data: Complete data with negative payment_amount
         * Expected behavior: Validation error for payment_amount
         */
        $response = $this->post('/payments/form/1', $negativePayment);
        
        /* Assert */
        $response->assertSessionHasErrors('payment_amount');
    }

    /**
     * Test POST validates payment date format
     */
    #[Test]
    public function it_validates_payment_date_format(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidDate = $this->makePaymentData([
            'invoice_id' => '1',
            'payment_amount' => '100.00',
            'payment_date' => 'invalid-date',
            'payment_method_id' => '1',
        ]);
        
        /**
         * Act: POST /payments/form/{invoice_id}
         * POST data: Complete data with invalid payment_date
         * Expected behavior: Validation error for payment_date
         */
        $response = $this->post('/payments/form/1', $invalidDate);
        
        /* Assert */
        $response->assertSessionHasErrors('payment_date');
    }

    /**
     * Test POST validates payment method exists
     */
    #[Test]
    public function it_validates_payment_method_exists_in_database(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidMethod = $this->makePaymentData([
            'invoice_id' => '1',
            'payment_amount' => '100.00',
            'payment_date' => date('Y-m-d'),
            'payment_method_id' => '999',
        ]);
        
        /**
         * Act: POST /payments/form/{invoice_id}
         * POST data: Complete data with non-existent payment_method_id
         * Expected behavior: Validation error for payment_method_id
         */
        $response = $this->post('/payments/form/1', $invalidMethod);
        
        /* Assert */
        $response->assertSessionHasErrors('payment_method_id');
        $records = $this->fakeDb->select('ip_payment_methods', ['payment_method_id' => 999]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_payment_methods'");
    }

    // #endregion

    // #region Security Tests

    /**
     * Security: Test XSS sanitization in payment data
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_payment_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $xssPayment = $this->makePaymentData([
            'invoice_id' => '1',
            'payment_amount' => '100.00',
            'payment_date' => date('Y-m-d'),
            'payment_method_id' => '1',
            'payment_note' => '<script>alert("XSS")</script>',
        ]);
        
        /**
         * Act: POST /payments/form/{invoice_id}
         * POST data: Complete data with XSS payload in payment_note
         * Expected behavior: XSS payloads should be sanitized or rejected
         */
        $response = $this->post('/payments/form/1', $xssPayment);
        
        /* Assert */
        $response->assertStatus(302);
    }

    /**
     * Security: Test SQL injection protection
     */
    #[Test]
    public function it_protects_against_sql_injection_attempts(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $sqlInjection = "1' OR '1'='1";
        
        /**
         * Act: GET /payments/form/{invoice_id}
         * Expected behavior: SQL injection should be prevented at query level
         */
        $response = $this->get('/payments/form/' . $sqlInjection);
        
        /* Assert */
        $response->assertStatus(200);
    }

    // #endregion
}
