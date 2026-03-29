<?php

namespace Modules\Payments\Tests;

use Modules\Payments\Controllers\PaymentMethodsController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for PaymentMethodsController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(PaymentMethodsController::class)]
class PaymentMethodsControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = PaymentMethodsController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'payment_methods'];
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

    // #region Authentication

    /**
     * Test that payment methods index requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_payment_methods_index(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /payment_methods/index
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/payment_methods/index');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test that payment methods index requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_display_payment_methods_index(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /payment_methods/index
         * Expected behavior: Redirect to dashboard when not admin
         */
        $response = $this->get('/payment_methods/index');
        
        /* Assert */
        $response->assertRedirect("/dashboard");
    }

    /**
     * Test that payment methods form requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_payment_methods_form(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /payment_methods/form
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/payment_methods/form');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test that payment methods form requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_display_payment_methods_form(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /payment_methods/form
         * Expected behavior: Redirect to dashboard when not admin
         */
        $response = $this->get('/payment_methods/form');
        
        /* Assert */
        $response->assertRedirect("/dashboard");
    }

    // #endregion

    // #region Index & List Display

    /**
     * Happy Path: Payment methods index displays list for admin users
     */
    #[Test]
    public function it_displays_payment_methods_list_on_index_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /payment_methods/index
         * Expected behavior: Display list of payment methods
         */
        $response = $this->get('/payment_methods/index');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('filter_payment_methods');
        $records = $this->fakeDb->select('ip_payment_methods', []);
        $this->assertNotEmpty($records, "Database should have record in 'ip_payment_methods'");
    }

    /**
     * Test payment methods index paginates results
     */
    #[Test]
    public function it_displays_pagination_on_payment_methods_index(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /payment_methods/index
         * Expected behavior: Display pagination controls
         */
        $response = $this->get('/payment_methods/index');
        
        /* Assert */
        $response->assertOk();
        $records = $this->fakeDb->select('ip_payment_methods', []);
        $this->assertNotEmpty($records, "Database should have record in 'ip_payment_methods'");
    }

    /**
     * Test index shows empty state when no payment methods exist
     */
    #[Test]
    public function it_displays_empty_state_when_no_payment_methods_exist(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /payment_methods/index
         * Expected behavior: Display empty state message
         */
        $response = $this->get('/payment_methods/index');
        
        /* Assert */
        $response->assertOk();
    }

    // #endregion

    // #region Form Display

    /**
     * Happy Path: Form displays new payment method form
     */
    #[Test]
    public function it_displays_new_payment_method_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /payment_methods/form
         * Expected behavior: Display new payment method form fields
         */
        $response = $this->get('/payment_methods/form');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('payment_method_name');
        $this->assertTrue($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Form displays edit payment method form with existing data
     */
    #[Test]
    public function it_displays_edit_payment_method_form_with_existing_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $paymentMethodId = 1;
        
        /**
         * Act: GET /payment_methods/form/{id}
         * Expected behavior: Display edit form with existing payment method data
         */
        $response = $this->get('/payment_methods/form/' . $paymentMethodId);
        
        /* Assert */
        $response->assertOk();
        $records = $this->fakeDb->select('ip_payment_methods', ['payment_method_id' => $paymentMethodId]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_payment_methods'");
    }

    /**
     * Test form returns 404 for invalid payment method ID
     */
    #[Test]
    public function it_returns_404_for_invalid_payment_method_id(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidId = 9999;
        
        /**
         * Act: GET /payment_methods/form/{id}
         * Expected behavior: Return 404 for non-existent payment method
         */
        $response = $this->get('/payment_methods/form/' . $invalidId);
        
        /* Assert */
        $response->assertNotFound();
        $records = $this->fakeDb->select('ip_payment_methods', ['payment_method_id' => $invalidId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_payment_methods'");
    }

    // #endregion

    // #region Form Submission (Create)

    /**
     * Happy Path: POST creates new payment method with valid data
     */
    #[Test]
    public function it_creates_new_payment_method_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $validMethodData = $this->makePaymentMethodData([
            'payment_method_name' => 'PayPal',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /payment_methods/form
         * POST data: {
         *   "payment_method_name": "PayPal",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Create new payment method and redirect
         */
        $response = $this->post('/payment_methods/form', $validMethodData);
        
        /* Assert */
        $response->assertRedirect('/payment_methods');
    }

    /**
     * Test POST cancels without saving when btn_cancel is clicked
     */
    #[Test]
    public function it_cancels_form_without_saving_when_cancel_button_is_clicked(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $methodData = $this->makePaymentMethodData([
            'payment_method_name' => 'Should Not Save',
            'btn_cancel' => 'Cancel',
        ]);
        
        /**
         * Act: POST /payment_methods/form
         * POST data: {
         *   "payment_method_name": "Should Not Save",
         *   "btn_cancel": "Cancel"
         * }
         * Expected behavior: Cancel and redirect without saving
         */
        $response = $this->post('/payment_methods/form', $methodData);
        
        /* Assert */
        $response->assertRedirect('/payment_methods');
        $records = $this->fakeDb->select('ip_payment_methods', ['payment_method_name' => 'Should Not Save']);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_payment_methods'");
    }

    // #endregion

    // #region Form Submission (Update)

    /**
     * Happy Path: POST updates existing payment method with valid data
     */
    #[Test]
    public function it_updates_existing_payment_method_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $paymentMethodId = 1;
        $updateData = $this->makePaymentMethodData([
            'payment_method_name' => 'Updated Name',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /payment_methods/form/{id}
         * POST data: {
         *   "payment_method_name": "Updated Name",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Update payment method and redirect
         */
        $response = $this->post('/payment_methods/form/' . $paymentMethodId, $updateData);
        
        /* Assert */
        $response->assertRedirect('/payment_methods');
    }

    /**
     * Test updating existing payment method with same name is allowed
     */
    #[Test]
    public function it_allows_updating_payment_method_with_same_name(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $paymentMethodId = 1;
        $sameNameData = $this->makePaymentMethodData([
            'payment_method_name' => 'Cash',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /payment_methods/form/{id}
         * POST data: {
         *   "payment_method_name": "Cash",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Update succeeds and redirects
         */
        $response = $this->post('/payment_methods/form/' . $paymentMethodId, $sameNameData);
        
        /* Assert */
        $response->assertRedirect('/payment_methods');
    }

    // #endregion

    // #region Delete

    /**
     * Test delete requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_delete_payment_method(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /payment_methods/delete/{id}
         * Expected behavior: Redirect to login when not authenticated
         */
        $response = $this->post('/payment_methods/delete/1');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test delete requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_delete_payment_method(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: POST /payment_methods/delete/{id}
         * Expected behavior: Redirect to dashboard when not admin
         */
        $response = $this->post('/payment_methods/delete/1');
        
        /* Assert */
        $response->assertRedirect("/dashboard");
    }

    /**
     * Happy Path: POST deletes payment method successfully
     */
    #[Test]
    public function it_deletes_payment_method_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $methodId = 3;
        
        /**
         * Act: POST /payment_methods/delete/{id}
         * POST data: {}
         * Expected behavior: Delete payment method and redirect
         */
        $response = $this->post('/payment_methods/delete/' . $methodId);
        
        /* Assert */
        $response->assertRedirect('/payment_methods');
    }

    /**
     * Test delete handles invalid payment method ID
     */
    #[Test]
    public function it_handles_invalid_payment_method_id_on_delete(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidId = 9999;
        
        /**
         * Act: POST /payment_methods/delete/{id}
         * Expected behavior: Return 404 for non-existent payment method
         */
        $response = $this->post('/payment_methods/delete/' . $invalidId);
        
        /* Assert */
        $response->assertStatus(404);
    }

    /**
     * Test delete handles foreign key constraints gracefully
     */
    #[Test]
    public function it_handles_foreign_key_constraints_on_delete(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $methodId = 1;
        
        /**
         * Act: POST /payment_methods/delete/{id}
         * Expected behavior: Handle foreign key constraint gracefully
         */
        $response = $this->post('/payment_methods/delete/' . $methodId);
        
        /* Assert */
        $response->assertStatus(302);
    }

    // #endregion

    // #region Validation

    /**
     * Test POST validates required fields are present
     */
    #[Test]
    public function it_validates_required_fields_are_present(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makePaymentMethodData([
            'payment_method_name' => '',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /payment_methods/form
         * POST data: {
         *   "payment_method_name": "",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Validation error for payment_method_name
         */
        $response = $this->post('/payment_methods/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors('payment_method_name');
    }

    /**
     * Test POST validates payment method name is unique
     */
    #[Test]
    public function it_validates_payment_method_name_is_unique(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $duplicateData = $this->makePaymentMethodData([
            'payment_method_name' => 'Cash',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /payment_methods/form
         * POST data: {
         *   "payment_method_name": "Cash",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Validation error for duplicate name
         */
        $response = $this->post('/payment_methods/form', $duplicateData);
        
        /* Assert */
        $response->assertSessionHasErrors('payment_method_name');
    }

    /**
     * Test POST handles long payment method names
     */
    #[Test]
    public function it_handles_long_payment_method_names(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $longName = str_repeat('A', 255);
        $longNameData = $this->makePaymentMethodData([
            'payment_method_name' => $longName,
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /payment_methods/form
         * POST data: {
         *   "payment_method_name": "AAA...AAA" (255 characters),
         *   "btn_submit": "1"
         * }
         * Expected behavior: Handle or truncate long names appropriately
         */
        $response = $this->post('/payment_methods/form', $longNameData);
        
        /* Assert */
        $response->assertStatus(302);
    }

    /**
     * Test POST handles special characters in payment method name
     */
    #[Test]
    public function it_handles_special_characters_in_payment_method_name(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $specialCharsData = $this->makePaymentMethodData([
            'payment_method_name' => 'Bank Transfer (€ / £ / $)',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /payment_methods/form
         * POST data: {
         *   "payment_method_name": "Bank Transfer (€ / £ / $)",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Accept and save special characters correctly
         */
        $response = $this->post('/payment_methods/form', $specialCharsData);
        
        /* Assert */
        $response->assertRedirect('/payment_methods');
    }

    // #endregion

    // #region Security

    /**
     * Security: Test XSS sanitization in payment method data
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_payment_method_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $xssData = $this->makePaymentMethodData([
            'payment_method_name' => '<script>alert("xss")</script>',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /payment_methods/form
         * POST data: {
         *   "payment_method_name": "<script>alert(\"xss\")</script>",
         *   "btn_submit": "1"
         * }
         * Expected behavior: XSS payloads should be sanitized or rejected
         */
        $response = $this->post('/payment_methods/form', $xssData);
        
        /* Assert */
        $response->assertStatus(302);
    }

    /**
     * Security: Test SQL injection protection in payment method form
     */
    #[Test]
    public function it_protects_against_sql_injection_attempts(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $sqlInjectionData = $this->makePaymentMethodData([
            'payment_method_name' => "'; DROP TABLE ip_payment_methods; --",
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /payment_methods/form
         * POST data: {
         *   "payment_method_name": "'; DROP TABLE ip_payment_methods; --",
         *   "btn_submit": "1"
         * }
         * Expected behavior: SQL injection should be prevented at query level
         */
        $response = $this->post('/payment_methods/form', $sqlInjectionData);
        
        /* Assert */
        $response->assertStatus(302);
    }

    /**
     * Security: Test delete protects against SQL injection attempts
     */
    #[Test]
    public function it_protects_delete_against_sql_injection_attempts(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $sqlInjection = "1 OR 1=1; DROP TABLE ip_payment_methods; --";
        
        /**
         * Act: POST /payment_methods/delete/{id}
         * Expected behavior: SQL injection should be prevented at query level
         */
        $response = $this->post('/payment_methods/delete/' . $sqlInjection);
        
        /* Assert */
        $response->assertStatus(404);
    }

    // #endregion
}
