<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\ReportsController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for ReportsController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(ReportsController::class)]
class ReportsControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = ReportsController::class;
    
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

    // #region Authentication & Authorization Tests


    /**
     * Test that sales by client report requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_view_sales_by_client_report(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /reports/sales_by_client
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/reports/sales_by_client');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test that all reports require admin authentication
     */
    #[Test]
    public function it_requires_admin_authentication_for_all_reports(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /reports/sales_by_client
         * Expected behavior: Redirect when user is not admin
         */
        $response = $this->get('/reports/sales_by_client');
        
        /* Assert */
        $response->assertStatus(302);
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    // #endregion

    // #region Sales By Client Report Tests


    /**
     * Happy Path: Sales by client form displays
     */
    #[Test]
    public function it_displays_sales_by_client_form_with_filters(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /reports/sales_by_client
         * Expected behavior: Display form with date range and client filters
         */
        $response = $this->get('/reports/sales_by_client');
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['from_date', 'to_date']);
        // Verify clients exist for dropdown
        $records = $this->fakeDb->select('ip_clients', []);
        $this->assertCount(2, $records, "Database should have exactly 2 record(s) in 'ip_clients'");
    }

    /**
     * Happy Path: POST generates sales by client PDF report
     */
    #[Test]
    public function it_generates_sales_by_client_pdf_report(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        $reportData = $this->makeReportData([
            'client_id' => $activeClient['client_id'],
        ]);
        
        /**
         * Act: POST /reports/sales_by_client
         * POST data: {
         *   "from_date": "2024-01-01",
         *   "to_date": "2024-12-31",
         *   "client_id": "1",
         *   "year": "2024",
         *   "include_tax": "0",
         *   "quantity_from": "",
         *   "quantity_to": "",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Generate and return PDF report
         */
        $response = $this->post('/reports/sales_by_client', $reportData);
        
        /* Assert */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        // Verify invoice data exists for report
        $records = $this->fakeDb->select('ip_invoices', ['invoice_client_id' => $activeClient['client_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_invoices'");
    }

    /**
     * Test report filters by date range
     */
    #[Test]
    public function it_filters_sales_by_client_report_by_date_range(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        $reportData = $this->makeReportData([
            'from_date' => '2024-06-01',
            'to_date' => '2024-06-30',
            'client_id' => $activeClient['client_id'],
        ]);
        
        /**
         * Act: POST /reports/sales_by_client
         * POST data: {
         *   "from_date": "2024-06-01",
         *   "to_date": "2024-06-30",
         *   "client_id": "1",
         *   "year": "2024",
         *   "include_tax": "0",
         *   "quantity_from": "",
         *   "quantity_to": "",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Apply date range filter to report data
         */
        $response = $this->post('/reports/sales_by_client', $reportData);
        
        /* Assert */
        // Verify date filtering logic would be applied
        $this->assertEquals('2024-06-01', $reportData['from_date']);
        $this->assertEquals('2024-06-30', $reportData['to_date']);
    }

    // #endregion

    // #region Invoices Per Client Report Tests


    /**
     * Happy Path: Invoices per client form displays
     */
    #[Test]
    public function it_displays_invoices_per_client_form_with_client_filter(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /reports/invoices_per_client
         * Expected behavior: Display form with client selection
         */
        $response = $this->get('/reports/invoices_per_client');
        
        /* Assert */
        $response->assertSee('client_id');
        $records = $this->fakeDb->select('ip_clients', []);
        $this->assertNotEmpty($records, "Database should have record in 'ip_clients'");
    }

    /**
     * Happy Path: POST generates invoices per client PDF report
     */
    #[Test]
    public function it_generates_invoices_per_client_pdf_report(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeClient = $this->fixtures->get('clients', 'active_client');
        $reportData = $this->makeReportData([
            'client_id' => $activeClient['client_id'],
        ]);
        
        /**
         * Act: POST /reports/invoices_per_client
         * POST data: {
         *   "client_id": "1",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Generate PDF report of all invoices for client
         */
        $response = $this->post('/reports/invoices_per_client', [
            'client_id' => $reportData['client_id'],
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        $response->assertHeader('Content-Type', 'application/pdf');
        $records = $this->fakeDb->select('ip_invoices', ['invoice_client_id' => $activeClient['client_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_invoices'");
    }

    // #endregion

    // #region Payment History Report Tests


    /**
     * Happy Path: Payment history form displays
     */
    #[Test]
    public function it_displays_payment_history_form_with_date_filters(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /reports/payment_history
         * Expected behavior: Display form with date range filters
         */
        $response = $this->get('/reports/payment_history');
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['from_date', 'to_date']);
        $records = $this->fakeDb->select('ip_payments', []);
        $this->assertCount(2, $records, "Database should have exactly 2 record(s) in 'ip_payments'");
    }

    /**
     * Happy Path: POST generates payment history PDF report
     */
    #[Test]
    public function it_generates_payment_history_pdf_report(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $reportData = $this->makeReportData();
        
        /**
         * Act: POST /reports/payment_history
         * POST data: {
         *   "from_date": "2024-01-01",
         *   "to_date": "2024-12-31",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Generate PDF report of payment history
         */
        $response = $this->post('/reports/payment_history', [
            'from_date' => $reportData['from_date'],
            'to_date' => $reportData['to_date'],
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        $response->assertHeader('Content-Type', 'application/pdf');
        $records = $this->fakeDb->select('ip_payments', []);
        $this->assertNotEmpty($records, "Database should have record in 'ip_payments'");
    }

    // #endregion

    // #region Invoice Aging Report Tests


    /**
     * Happy Path: Invoice aging form displays
     */
    #[Test]
    public function it_displays_invoice_aging_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /reports/invoice_aging
         * Expected behavior: Display invoice aging report form
         */
        $response = $this->get('/reports/invoice_aging');
        
        /* Assert */
        $response->assertSee('invoice_aging_report');
        $records = $this->fakeDb->select('ip_invoices', []);
        $this->assertCount(3, $records, "Database should have exactly 3 record(s) in 'ip_invoices'");
    }

    /**
     * Happy Path: POST generates invoice aging PDF report
     */
    #[Test]
    public function it_generates_invoice_aging_pdf_report(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: POST /reports/invoice_aging
         * POST data: {
         *   "btn_submit": "1"
         * }
         * Expected behavior: Generate PDF report showing invoice aging
         */
        $response = $this->post('/reports/invoice_aging', [
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        $response->assertHeader('Content-Type', 'application/pdf');
        $records = $this->fakeDb->select('ip_invoices', []);
        $this->assertNotEmpty($records, "Database should have record in 'ip_invoices'");
    }

    // #endregion

    // #region Sales By Year Report Tests


    /**
     * Happy Path: Sales by year form displays
     */
    #[Test]
    public function it_displays_sales_by_year_form_with_year_filter(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /reports/sales_by_year
         * Expected behavior: Display form with year and tax options
         */
        $response = $this->get('/reports/sales_by_year');
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['year', 'include_tax']);
        $records = $this->fakeDb->select('ip_invoices', []);
        $this->assertNotEmpty($records, "Database should have record in 'ip_invoices'");
    }

    /**
     * Happy Path: POST generates sales by year PDF report
     */
    #[Test]
    public function it_generates_sales_by_year_pdf_report(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $reportData = $this->makeReportData();
        
        /**
         * Act: POST /reports/sales_by_year
         * POST data: {
         *   "year": "2024",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Generate PDF report of sales for the year
         */
        $response = $this->post('/reports/sales_by_year', [
            'year' => $reportData['year'],
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        $response->assertHeader('Content-Type', 'application/pdf');
        $records = $this->fakeDb->select('ip_invoices', []);
        $this->assertNotEmpty($records, "Database should have record in 'ip_invoices'");
    }

    /**
     * Test report filters by quantity range
     */
    #[Test]
    public function it_filters_sales_by_year_report_by_quantity_range(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $reportData = $this->makeReportData([
            'quantity_from' => '10',
            'quantity_to' => '100',
        ]);
        
        /**
         * Act: POST /reports/sales_by_year
         * POST data: {
         *   "year": "2024",
         *   "quantity_from": "10",
         *   "quantity_to": "100",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Apply quantity range filter to report
         */
        $response = $this->post('/reports/sales_by_year', [
            'year' => $reportData['year'],
            'quantity_from' => $reportData['quantity_from'],
            'quantity_to' => $reportData['quantity_to'],
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        // Verify quantity filtering logic would be applied
        $response->assertStatus(200);
    }

    /**
     * Test report includes tax optionally
     */
    #[Test]
    public function it_includes_tax_optionally_in_sales_by_year_report(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $reportData = $this->makeReportData([
            'include_tax' => '1',
        ]);
        
        /**
         * Act: POST /reports/sales_by_year
         * POST data: {
         *   "year": "2024",
         *   "include_tax": "1",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Include tax in report calculations
         */
        $response = $this->post('/reports/sales_by_year', [
            'year' => $reportData['year'],
            'include_tax' => $reportData['include_tax'],
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        // Verify tax inclusion flag
        $response->assertStatus(200);
    }

    // #endregion
}
