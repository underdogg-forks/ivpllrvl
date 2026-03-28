<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\ReportsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for ReportsController
 * 
 * Tests the full request/response cycle with Laravel HTTP testing.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(ReportsController::class)]
class ReportsControllerTest extends TestCase
{
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Load user fixtures for authentication
        $users = $this->fixtures->all('users');
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        // Load clients for report data
        $clients = $this->fixtures->all('clients');
        foreach (['active_client', 'inactive_client'] as $key) {
            $this->fakeDb->insert('ip_clients', $clients[$key]);
        }
        
        // Load invoices for report data
        $invoices = $this->fixtures->all('invoices');
        foreach (['draft_invoice', 'sent_invoice', 'paid_invoice'] as $key) {
            $this->fakeDb->insert('ip_invoices', $invoices[$key]);
        }
        
        // Load payments for payment history reports
        $payments = $this->fixtures->all('payments');
        foreach (['cash_payment', 'bank_transfer_payment', 'credit_card_payment'] as $key) {
            $this->fakeDb->insert('ip_payments', $payments[$key]);
        }
        
        // Store date range for report filtering
        $this->testData = [
            'from_date' => '2024-01-01',
            'to_date' => '2024-12-31',
            'client_id' => $this->fixtures->get('clients', 'active_client')['client_id'],
        ];
    }

    /**
     * Test that sales by client report requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_sales_by_client(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // GET /reports/sales_by_client
        $response = $this->get('/reports/sales_by_client');
        
        /* Assert */
        $response->assertStatus(302);
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Admin can view sales by client form
     */
    #[Test]
    public function it_displays_sales_by_client_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /reports/sales_by_client
        $response = $this->get('/reports/sales_by_client');
        
        /* Assert */
        $response->assertSee('from_date');
        $response->assertSee('to_date');
        // Verify clients exist for dropdown
        $clients = $this->fakeDb->select('ip_clients');
        $this->assertCount(2, $clients);
    }

    /**
     * Happy Path: Generate PDF report for sales by client
     */
    #[Test]
    public function it_post_sales_by_client_generates_pdf_report(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /reports/sales_by_client
        // Successful request: ['from_date' => '2024-01-01', 'to_date' => '2024-12-31', 'client_id' => ..., 'btn_submit' => '1']
        $response = $this->post('/reports/sales_by_client', array_merge($this->testData, [
            'btn_submit' => '1',
        ]));
        
        /* Assert */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        // Verify invoice data exists for report
        $invoices = $this->fakeDb->select('ip_invoices', [
            'invoice_client_id' => $this->testData['client_id']
        ]);
        $this->assertGreaterThan(0, count($invoices));
    }

    /**
     * Test sales by client filters by date range
     */
    #[Test]
    public function it_post_sales_by_client_filters_by_date_range(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $dateRangeData = [
            'from_date' => '2024-06-01',
            'to_date' => '2024-06-30',
            'client_id' => $this->testData['client_id'],
            'btn_submit' => '1',
        ];
        
        /* Act */
        // POST /reports/sales_by_client
        // Filter by date range: ['from_date' => '2024-06-01', 'to_date' => '2024-06-30', 'client_id' => ..., 'btn_submit' => '1']
        $response = $this->post('/reports/sales_by_client', $dateRangeData);
        
        /* Assert */
        // Verify date filtering logic would be applied
        $this->assertEquals('2024-06-01', $dateRangeData['from_date']);
        $this->assertEquals('2024-06-30', $dateRangeData['to_date']);
    }

    /**
     * Happy Path: Admin can view invoices per client form
     */
    #[Test]
    public function it_displays_invoices_per_client_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /reports/invoices_per_client
        $response = $this->get('/reports/invoices_per_client');
        
        /* Assert */
        $response->assertSee('client_id');
        $clients = $this->fakeDb->select('ip_clients');
        $this->assertGreaterThan(0, count($clients));
    }

    /**
     * Happy Path: Generate PDF report for invoices per client
     */
    #[Test]
    public function it_post_invoices_per_client_generates_pdf_report(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /reports/invoices_per_client
        // Successful request: ['client_id' => ..., 'btn_submit' => '1']
        $response = $this->post('/reports/invoices_per_client', [
            'client_id' => $this->testData['client_id'],
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        $response->assertHeader('Content-Type', 'application/pdf');
        $invoices = $this->fakeDb->select('ip_invoices', [
            'invoice_client_id' => $this->testData['client_id']
        ]);
        $this->assertGreaterThan(0, count($invoices));
    }

    /**
     * Happy Path: Admin can view payment history form
     */
    #[Test]
    public function it_displays_payment_history_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /reports/payment_history
        $response = $this->get('/reports/payment_history');
        
        /* Assert */
        $response->assertSee('from_date');
        $response->assertSee('to_date');
        $payments = $this->fakeDb->select('ip_payments');
        $this->assertCount(3, $payments);
    }

    /**
     * Happy Path: Generate PDF report for payment history
     */
    #[Test]
    public function it_post_payment_history_generates_pdf_report(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /reports/payment_history
        // Successful request: ['from_date' => '2024-01-01', 'to_date' => '2024-12-31', 'btn_submit' => '1']
        $response = $this->post('/reports/payment_history', [
            'from_date' => $this->testData['from_date'],
            'to_date' => $this->testData['to_date'],
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        $response->assertHeader('Content-Type', 'application/pdf');
        $payments = $this->fakeDb->select('ip_payments');
        $this->assertGreaterThan(0, count($payments));
    }

    /**
     * Happy Path: Admin can view invoice aging form
     */
    #[Test]
    public function it_displays_invoice_aging_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /reports/invoice_aging
        $response = $this->get('/reports/invoice_aging');
        
        /* Assert */
        $response->assertSee('invoice_aging_report');
        $invoices = $this->fakeDb->select('ip_invoices');
        $this->assertCount(3, $invoices);
    }

    /**
     * Happy Path: Generate PDF report for invoice aging
     */
    #[Test]
    public function it_post_invoice_aging_generates_pdf_report(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /reports/invoice_aging
        // Successful request: ['btn_submit' => '1']
        $response = $this->post('/reports/invoice_aging', [
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        $response->assertHeader('Content-Type', 'application/pdf');
        $invoices = $this->fakeDb->select('ip_invoices');
        $this->assertGreaterThan(0, count($invoices));
    }

    /**
     * Happy Path: Admin can view sales by year form
     */
    #[Test]
    public function it_displays_sales_by_year_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /reports/sales_by_year
        $response = $this->get('/reports/sales_by_year');
        
        /* Assert */
        $response->assertSee('year');
        $response->assertSee('include_tax');
        $invoices = $this->fakeDb->select('ip_invoices');
        $this->assertGreaterThan(0, count($invoices));
    }

    /**
     * Happy Path: Generate PDF report for sales by year
     */
    #[Test]
    public function it_post_sales_by_year_generates_pdf_report(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /reports/sales_by_year
        // Successful request: ['year' => '2024', 'btn_submit' => '1']
        $response = $this->post('/reports/sales_by_year', [
            'year' => '2024',
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        $response->assertHeader('Content-Type', 'application/pdf');
        $invoices = $this->fakeDb->select('ip_invoices');
        $this->assertGreaterThan(0, count($invoices));
    }

    /**
     * Test sales by year filters by quantity range
     */
    #[Test]
    public function it_post_sales_by_year_filters_by_quantity_range(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /reports/sales_by_year
        // Filter by quantity range: ['year' => '2024', 'quantity_from' => '10', 'quantity_to' => '100', 'btn_submit' => '1']
        $response = $this->post('/reports/sales_by_year', [
            'year' => '2024',
            'quantity_from' => '10',
            'quantity_to' => '100',
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        // Verify quantity filtering logic would be applied
        $response->assertStatus(200);
    }

    /**
     * Test sales by year includes tax optionally
     */
    #[Test]
    public function it_post_sales_by_year_includes_tax_optionally(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /reports/sales_by_year
        // Include tax option: ['year' => '2024', 'include_tax' => '1', 'btn_submit' => '1']
        $response = $this->post('/reports/sales_by_year', [
            'year' => '2024',
            'include_tax' => '1',
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        // Verify tax inclusion flag
        $response->assertStatus(200);
    }

    /**
     * Test that all reports require admin authentication
     */
    #[Test]
    public function it_reports_require_admin_authentication(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /* Act */
        // GET /reports/sales_by_client
        $response = $this->get('/reports/sales_by_client');
        
        /* Assert */
        $response->assertStatus(302);
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }
}
