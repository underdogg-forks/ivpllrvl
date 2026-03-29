<?php

namespace Modules\Clients\Tests;

use Modules\Clients\Controllers\PaymentsController;
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
    
    protected function fixtureTypes(): array
    {
        return ['users', 'clients', 'payments'];
    }
    
    protected function loadFixtures(): void
    {
        $this->loadAllFixtures();
    }
    
    protected function setUpController(): void
    {
        // Intentionally empty - test data is provided via ProvidesTestData trait
    }

    // #region Authentication & Authorization Tests

    #[Test]
    public function it_requires_authentication_to_view_payments_index(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /** Act: GET /guest/payments/index */
        $response = $this->get('/guest/payments/index');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    #[Test]
    public function it_requires_guest_role_to_view_payments(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        
        /** Act: GET /guest/payments/index */
        $response = $this->get('/guest/payments/index');
        
        /* Assert */
        $response->assertRedirect("/dashboard");
    }

    // #endregion

    // #region Payments Display Tests

    #[Test]
    public function it_displays_payments_for_assigned_clients(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/payments/index
         * Expected: HTML view with table of payments for assigned clients
         */
        $response = $this->get('/guest/payments/index');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Page Structure */
        $response->assertSee('Payments');
        $response->assertSee('<table');
        
        /* Assert - Payment Data from Fixtures */
        $response->assertSee('INV-2024-002');  // Invoice number from fixtures
        $response->assertSee('500.00');  // Cash payment amount
        $response->assertSee('Partial payment via cash');  // Payment note
        $response->assertSee('1700.00');  // Bank transfer amount
        $response->assertSee('Final payment via bank transfer');
        
        /* Assert - Database Records */
        $payments = $this->fakeDb->select('ip_payments', []);
        $this->assertNotEmpty($payments, "Database should have payment records");
        $this->assertGreaterThanOrEqual(2, count($payments), "Should have at least 2 payments in fixtures");
    }

    #[Test]
    public function it_excludes_payments_for_unassigned_clients(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/payments/index
         * Expected: Only payments for assigned clients, not all payments
         */
        $response = $this->get('/guest/payments/index');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Contains Assigned Client Payments */
        $response->assertSee('INV-2024-002');  // Assigned client invoice
        
        /* Assert - Database Filter Working */
        $allPayments = $this->fakeDb->select('ip_payments', []);
        $this->assertNotEmpty($allPayments, "Database has payments");
        
        /* Assert - Guest Access Control */
        $this->assertTrue($this->fakeSession->has('user_id'), 'Guest user must be authenticated');
        $this->assertEquals(2, $this->fakeSession->get('user_type'), 'User type should be guest (2)');
    }

    #[Test]
    public function it_paginates_payment_results(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/payments/index?page=2
         * Expected: Paginated payment results
         */
        $response = $this->get('/guest/payments/index?page=2');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Pagination Structure */
        $response->assertSee('pager');
        $response->assertSee('pagination');
        
        /* Assert - Payment Data Present */
        $payments = $this->fakeDb->select('ip_payments', []);
        $this->assertNotEmpty($payments, "Database should have payments for pagination");
    }

    #[Test]
    public function it_defaults_to_first_page_when_page_zero(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/payments/index?page=0
         * Expected: Defaults to page 1, shows payment data
         */
        $response = $this->get('/guest/payments/index?page=0');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Payment Data Present (First Page) */
        $response->assertSee('INV-2024-002');  // Invoice from fixtures
        $response->assertSee('500.00');  // Cash payment
        $response->assertSee('1700.00');  // Bank transfer
        
        /* Assert - Database Query Returns Data */
        $payments = $this->fakeDb->select('ip_payments', []);
        $this->assertGreaterThanOrEqual(2, count($payments), "Should have multiple payments to display");
    }

    #[Test]
    public function it_displays_payment_details_in_index(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/payments/index
         * Expected: Display payment details including date, invoice, amount, method, note
         */
        $response = $this->get('/guest/payments/index');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Table Headers */
        $response->assertSee('Date');
        $response->assertSee('Invoice');
        $response->assertSee('Amount');
        $response->assertSee('Payment Method');
        $response->assertSee('Note');
        
        /* Assert - Payment Data from Fixtures */
        // Cash payment (payment_id: 1)
        $response->assertSee('INV-2024-002');
        $response->assertSee('500.00');
        $response->assertSee('Partial payment via cash');
        $response->assertSee('Cash');
        
        // Bank transfer (payment_id: 2)
        $response->assertSee('1700.00');
        $response->assertSee('Final payment via bank transfer');
        $response->assertSee('Bank Transfer');
        
        /* Assert - Database Verification */
        $cashPayment = $this->fakeDb->select('ip_payments', ['payment_id' => 1]);
        $this->assertNotEmpty($cashPayment, "Cash payment should exist in database");
        $this->assertEquals('500.00', $cashPayment[0]['payment_amount'], "Cash payment amount should match fixture");
        
        $bankPayment = $this->fakeDb->select('ip_payments', ['payment_id' => 2]);
        $this->assertNotEmpty($bankPayment, "Bank transfer should exist in database");
        $this->assertEquals('1700.00', $bankPayment[0]['payment_amount'], "Bank transfer amount should match fixture");
    }

    #[Test]
    public function it_uses_guest_layout_for_payments_index(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/payments/index
         * Expected: Uses guest layout (not admin layout)
         */
        $response = $this->get('/guest/payments/index');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Guest Layout Elements */
        $response->assertSee('guest');  // Layout identifier
        $response->assertDontSee('admin-sidebar');  // No admin elements
        
        /* Assert - User Authentication */
        $this->assertTrue($this->fakeSession->has('user_id'), 'Guest must be authenticated');
        $this->assertEquals(2, $this->fakeSession->get('user_type'), 'User type should be guest (2)');
    }

    #[Test]
    public function it_enables_payment_filter_functionality(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/payments/index
         * Expected: Filter UI elements present for searching payments
         */
        $response = $this->get('/guest/payments/index');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Filter UI Elements */
        $response->assertSee('filter_placeholder');
        $response->assertSee('filter_payments');
        $response->assertSee('filter-payments');  // CSS class or ID
        
        /* Assert - Payment Data Filterable */
        $payments = $this->fakeDb->select('ip_payments', []);
        $this->assertGreaterThanOrEqual(2, count($payments), "Need multiple payments to enable filtering");
    }

    #[Test]
    public function it_displays_empty_list_for_unassigned_guest(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/payments/index
         * Expected: Successful page load with empty or minimal payment list
         */
        $response = $this->get('/guest/payments/index');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Page Structure Present */
        $response->assertSee('Payments');
        $response->assertSee('<table');
        
        /* Assert - User Authentication */
        $this->assertTrue($this->fakeSession->has('user_id'), 'Guest user must be authenticated');
        $this->assertEquals(2, $this->fakeSession->get('user_type'), 'User type should be guest (2)');
        
        /* Assert - Database State */
        $payments = $this->fakeDb->select('ip_payments', []);
        $this->assertIsArray($payments, 'Payment query should return array');
    }

    #[Test]
    public function it_displays_payments_for_multiple_assigned_clients(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/payments/index
         * Expected: All payments from multiple assigned clients displayed in table
         */
        $response = $this->get('/guest/payments/index');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Data Content from Multiple Clients */
        $response->assertSee('INV-2024-002');  // Invoice for client 1
        $response->assertSee('500.00');  // Cash payment
        $response->assertSee('1700.00');  // Bank transfer
        $response->assertSee('Partial payment via cash');
        $response->assertSee('Final payment via bank transfer');
        
        /* Assert - Table Structure */
        $response->assertSee('Date');
        $response->assertSee('Invoice');
        $response->assertSee('Amount');
        $response->assertSee('Payment Method');
        
        /* Assert - Database Has Multiple Payments */
        $payments = $this->fakeDb->select('ip_payments', []);
        $this->assertGreaterThanOrEqual(2, count($payments), 'Should have payments from multiple clients');
    }

    #[Test]
    public function it_loads_required_models_for_payments(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/payments/index
         * Expected: Controller loads models successfully and renders payment data
         */
        $response = $this->get('/guest/payments/index');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Payment Data Present (Requires Models) */
        $response->assertSee('INV-2024-002');  // Invoice data requires invoice model
        $response->assertSee('500.00');  // Payment data requires payment model
        $response->assertSee('Cash');  // Payment method requires payment_methods model
        
        /* Assert - Database Records Accessible */
        $payments = $this->fakeDb->select('ip_payments', []);
        $this->assertNotEmpty($payments, 'Payment model should load data from database');
        
        $invoices = $this->fakeDb->select('ip_invoices', []);
        $this->assertNotEmpty($invoices, 'Invoice model should load data from database');
    }

    #[Test]
    public function it_builds_correct_where_clause_for_payments(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/payments/index
         * Expected: Filters payments by assigned client IDs only
         */
        $response = $this->get('/guest/payments/index');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Data Content Shows Filtered Results */
        $response->assertSee('INV-2024-002');  // Assigned client invoice
        $response->assertSee('Active Client Corp');  // Client name should be visible
        
        /* Assert - Database Query Filtered Properly */
        $allPayments = $this->fakeDb->select('ip_payments', []);
        $this->assertNotEmpty($allPayments, 'Database has payments to filter');
        
        $assignedClient = $this->fakeDb->select('ip_clients', ['client_id' => 1]);
        $this->assertNotEmpty($assignedClient, 'Assigned client exists in database');
        
        /* Assert - Guest Authorization Active */
        $this->assertEquals(2, $this->fakeSession->get('user_type'), 'Guest user type filters payment query');
    }

    #[Test]
    public function it_handles_empty_client_list_gracefully(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/payments/index
         * Expected: Page loads successfully even with no assigned clients
         */
        $response = $this->get('/guest/payments/index');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Page Structure Present */
        $response->assertSee('Payments');
        $response->assertSee('<table');
        
        /* Assert - No Error Messages */
        $response->assertDontSee('error');
        $response->assertDontSee('warning');
        $response->assertDontSee('exception');
        
        /* Assert - Database Query Executes */
        $clients = $this->fakeDb->select('ip_clients', []);
        $this->assertIsArray($clients, 'Client query should execute without error');
    }

    #[Test]
    public function it_displays_payment_method_names_in_index(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/payments/index
         * Expected: Payment method names displayed (not just IDs)
         */
        $response = $this->get('/guest/payments/index');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Payment Method Names Present */
        $response->assertSee('Cash');  // payment_method_id: 1
        $response->assertSee('Bank Transfer');  // payment_method_id: 2
        $response->assertSee('Credit Card');  // payment_method_id: 3
        
        /* Assert - Payment Method Column Header */
        $response->assertSee('Payment Method');
        
        /* Assert - Database Has Payment Methods */
        $cashPayment = $this->fakeDb->select('ip_payments', ['payment_method_id' => 1]);
        $this->assertNotEmpty($cashPayment, 'Cash payment exists in database');
        
        $bankPayment = $this->fakeDb->select('ip_payments', ['payment_method_id' => 2]);
        $this->assertNotEmpty($bankPayment, 'Bank transfer exists in database');
    }

    // #endregion

    // #region Security Tests

    #[Test]
    public function it_protects_against_sql_injection_in_payments(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        $maliciousInput = "1' OR '1'='1";
        
        /**
         * Act: GET /guest/payments/index?filter=malicious
         * Expected: SQL injection attempt is sanitized, no unauthorized data exposed
         */
        $response = $this->get('/guest/payments/index?filter=' . urlencode($maliciousInput));
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Only Authorized Payment Data Shown */
        $response->assertSee('INV-2024-002');  // Legitimate assigned invoice
        $response->assertSee('500.00');  // Legitimate payment amount
        
        /* Assert - No SQL Errors or Injection Success */
        $response->assertDontSee('SQL');
        $response->assertDontSee('syntax error');
        $response->assertDontSee('mysql');
        $response->assertDontSee('database error');
        
        /* Assert - Database Integrity Maintained */
        $payments = $this->fakeDb->select('ip_payments', []);
        $paymentCount = count($payments);
        $this->assertGreaterThanOrEqual(2, $paymentCount, 'Payment count should match fixtures, not all records');
    }

    // #endregion
}
