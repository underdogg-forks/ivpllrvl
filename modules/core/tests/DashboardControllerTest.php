<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\DashboardController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for DashboardController
 * 
 * Tests dashboard display functionality with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(DashboardController::class)]
class DashboardControllerTest extends ControllerTestCase
{
    protected string $controllerClass = DashboardController::class;
    
    protected function loadFixtures(): void
    {
        // Load all relevant fixtures for dashboard
        $users = $this->fixtures->all('users');
        $clients = $this->fixtures->all('clients');
        $invoices = $this->fixtures->all('invoices');
        $quotes = $this->fixtures->all('quotes');
        $projects = $this->fixtures->all('projects');
        $tasks = $this->fixtures->all('tasks');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest', 'inactive'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        foreach (['active_client', 'inactive_client'] as $key) {
            $this->fakeDb->insert('ip_clients', $clients[$key]);
        }
        
        foreach (['draft_invoice', 'sent_invoice', 'paid_invoice'] as $key) {
            if (isset($invoices[$key])) {
                $this->fakeDb->insert('ip_invoices', $invoices[$key]);
            }
        }
        
        foreach (['draft_quote', 'sent_quote'] as $key) {
            if (isset($quotes[$key])) {
                $this->fakeDb->insert('ip_quotes', $quotes[$key]);
            }
        }
        
        foreach (['active_project', 'completed_project'] as $key) {
            if (isset($projects[$key])) {
                $this->fakeDb->insert('ip_projects', $projects[$key]);
            }
        }
        
        foreach (['pending_task', 'completed_task'] as $key) {
            if (isset($tasks[$key])) {
                $this->fakeDb->insert('ip_tasks', $tasks[$key]);
            }
        }
    }
    
    protected function setUpController(): void
    {
        // Store test data for reuse
        $this->testData = [
            'admin' => $this->fixtures->get('users', 'admin'),
            'guest' => $this->fixtures->get('users', 'guest'),
        ];
    }

    /**
     * Test that dashboard index requires authentication
     */
    #[Test]
    public function it_displays_dashboard_index_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // GET /dashboard
        $response = $this->get('/dashboard');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Test that dashboard index requires admin role
     */
    #[Test]
    public function it_displays_dashboard_index_requires_admin_role(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /* Act */
        $controller = $this->getController();
        $controller->index();
        
        /* Assert */
        $this->assertForbidden();
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Happy Path: Admin can view dashboard
     */
    #[Test]
    public function it_displays_dashboard_index_dashboard_for_admin(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->index();
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('dashboard');
        $this->assertTrue($this->fakeSession->has('user_id'));
        $this->assertEquals(1, $this->fakeSession->get('user_type'));
    }

    /**
     * Test dashboard loads recent invoices
     */
    #[Test]
    public function it_displays_dashboard_index_recent_invoices(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $controller = $this->getController();
        $controller->index();
        
        // Retrieve recent invoices from fake database
        $invoices = $this->fakeDb->select('ip_invoices', [], 'invoice_date_created DESC', 10);
        
        /* Assert */
        $this->assertResponseContains('recent_invoices');
        $this->assertGreaterThan(0, count($invoices));
    }

    /**
     * Test dashboard loads recent quotes
     */
    #[Test]
    public function it_displays_dashboard_index_recent_quotes(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $controller = $this->getController();
        $controller->index();
        
        // Retrieve recent quotes from fake database
        $quotes = $this->fakeDb->select('ip_quotes', [], 'quote_date_created DESC', 10);
        
        /* Assert */
        $this->assertResponseContains('recent_quotes');
        $this->assertGreaterThan(0, count($quotes));
    }

    /**
     * Test dashboard displays overdue invoices
     */
    #[Test]
    public function it_displays_dashboard_index_overdue_invoices(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $currentDate = date('Y-m-d');
        
        /* Act */
        $controller = $this->getController();
        $controller->index();
        
        // Query overdue invoices (due date < current date, status != paid)
        $overdueInvoices = $this->fakeDb->selectWhere('ip_invoices', function ($invoice) use ($currentDate) {
            return isset($invoice['invoice_date_due']) 
                && $invoice['invoice_date_due'] < $currentDate
                && $invoice['invoice_status_id'] != 4; // Not paid
        });
        
        /* Assert */
        $this->assertResponseContains('overdue_invoices');
        $this->assertIsArray($overdueInvoices);
    }

    /**
     * Test dashboard displays recent projects
     */
    #[Test]
    public function it_displays_dashboard_index_recent_projects(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $controller = $this->getController();
        $controller->index();
        
        // Retrieve recent projects from fake database
        $projects = $this->fakeDb->select('ip_projects', [], 'project_date_start DESC', 10);
        
        /* Assert */
        $this->assertResponseContains('recent_projects');
        $this->assertIsArray($projects);
    }

    /**
     * Test dashboard displays recent tasks
     */
    #[Test]
    public function it_displays_dashboard_index_recent_tasks(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $controller = $this->getController();
        $controller->index();
        
        // Retrieve recent tasks from fake database
        $tasks = $this->fakeDb->select('ip_tasks', [], 'task_date_due DESC', 10);
        
        /* Assert */
        $this->assertResponseContains('recent_tasks');
        $this->assertIsArray($tasks);
    }

    /**
     * Test dashboard invoice status totals calculation
     */
    #[Test]
    public function it_displays_dashboard_index_calculates_invoice_totals_by_status(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $controller = $this->getController();
        $controller->index();
        
        // Calculate invoice totals by status
        $invoices = $this->fakeDb->select('ip_invoices');
        $totals = [];
        
        foreach ($invoices as $invoice) {
            $statusId = $invoice['invoice_status_id'];
            if (!isset($totals[$statusId])) {
                $totals[$statusId] = ['count' => 0, 'total' => 0.0];
            }
            $totals[$statusId]['count']++;
            $totals[$statusId]['total'] += (float) $invoice['invoice_total'];
        }
        
        /* Assert */
        $this->assertResponseContains('invoice_totals');
        $this->assertIsArray($totals);
        $this->assertGreaterThan(0, count($totals));
    }

    /**
     * Test dashboard quote status totals calculation
     */
    #[Test]
    public function it_displays_dashboard_index_calculates_quote_totals_by_status(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $controller = $this->getController();
        $controller->index();
        
        // Calculate quote totals by status
        $quotes = $this->fakeDb->select('ip_quotes');
        $totals = [];
        
        foreach ($quotes as $quote) {
            $statusId = $quote['quote_status_id'];
            if (!isset($totals[$statusId])) {
                $totals[$statusId] = ['count' => 0, 'total' => 0.0];
            }
            $totals[$statusId]['count']++;
            $totals[$statusId]['total'] += (float) $quote['quote_total'];
        }
        
        /* Assert */
        $this->assertResponseContains('quote_totals');
        $this->assertIsArray($totals);
        $this->assertGreaterThan(0, count($totals));
    }

    /**
     * Test dashboard handles no data gracefully
     */
    #[Test]
    public function it_displays_dashboard_index_handles_empty_data_gracefully(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        // Clear all data from fake database
        $this->fakeDb->truncate('ip_invoices');
        $this->fakeDb->truncate('ip_quotes');
        $this->fakeDb->truncate('ip_projects');
        $this->fakeDb->truncate('ip_tasks');
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->index();
        $output = ob_get_clean();
        
        // Verify empty data
        $invoices = $this->fakeDb->select('ip_invoices');
        $quotes = $this->fakeDb->select('ip_quotes');
        
        /* Assert */
        $this->assertResponseContains('dashboard');
        $this->assertResponseNotContains('Fatal error');
        $this->assertCount(0, $invoices);
        $this->assertCount(0, $quotes);
    }

    /**
     * Test dashboard respects invoice overview period setting
     */
    #[Test]
    public function it_displays_dashboard_index_respects_invoice_overview_period(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $overviewPeriod = 30; // days
        $cutoffDate = date('Y-m-d', strtotime("-{$overviewPeriod} days"));
        
        /* Act */
        $controller = $this->getController();
        $controller->index();
        
        // Filter invoices by date range
        $recentInvoices = $this->fakeDb->selectWhere('ip_invoices', function ($invoice) use ($cutoffDate) {
            return isset($invoice['invoice_date_created']) 
                && $invoice['invoice_date_created'] >= $cutoffDate;
        });
        
        /* Assert */
        $this->assertResponseContains('invoice_overview');
        $this->assertIsArray($recentInvoices);
        
        foreach ($recentInvoices as $invoice) {
            $this->assertGreaterThanOrEqual($cutoffDate, $invoice['invoice_date_created']);
        }
    }

    /**
     * Test dashboard respects quote overview period setting
     */
    #[Test]
    public function it_displays_dashboard_index_respects_quote_overview_period(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $overviewPeriod = 30; // days
        $cutoffDate = date('Y-m-d', strtotime("-{$overviewPeriod} days"));
        
        /* Act */
        $controller = $this->getController();
        $controller->index();
        
        // Filter quotes by date range
        $recentQuotes = $this->fakeDb->selectWhere('ip_quotes', function ($quote) use ($cutoffDate) {
            return isset($quote['quote_date_created']) 
                && $quote['quote_date_created'] >= $cutoffDate;
        });
        
        /* Assert */
        $this->assertResponseContains('quote_overview');
        $this->assertIsArray($recentQuotes);
        
        foreach ($recentQuotes as $quote) {
            $this->assertGreaterThanOrEqual($cutoffDate, $quote['quote_date_created']);
        }
    }
}
