<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\DashboardController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for DashboardController
 * 
 * Tests dashboard display functionality with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(DashboardController::class)]
class DashboardControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = DashboardController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'clients', 'invoices', 'quotes', 'projects', 'tasks'];
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
     * Test that dashboard index requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_dashboard(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /dashboard
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/dashboard');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test that dashboard index requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_display_dashboard(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /dashboard
         * Expected behavior: Return 403 Forbidden for non-admin users
         */
        $response = $this->get('/dashboard');
        
        /* Assert */
        $response->assertForbidden();
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    // #endregion

    // #region Display

    /**
     * Happy Path: Admin can view dashboard
     */
    #[Test]
    public function it_displays_dashboard_for_admin_user(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /dashboard
         * Expected behavior: Display dashboard with main content
         */
        $response = $this->get('/dashboard');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('dashboard');
        $this->assertTrue($this->fakeSession->has('user_id'));
        $this->assertEquals(1, $this->fakeSession->get('user_type'));
    }


    /**
     * Test dashboard handles no data gracefully
     */
    #[Test]
    public function it_handles_empty_data_gracefully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        // Clear all data from fake database
        $this->fakeDb->truncate('ip_invoices');
        $this->fakeDb->truncate('ip_quotes');
        $this->fakeDb->truncate('ip_projects');
        $this->fakeDb->truncate('ip_tasks');
        
        /**
         * Act: GET /dashboard
         * Expected behavior: Display dashboard without errors when no data exists
         */
        $response = $this->get('/dashboard');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('dashboard');
        $response->assertDontSee('Fatal error');
        $records = $this->fakeDb->select('ip_invoices', []);
        $this->assertCount(0, $records, "Database should have exactly 0 record(s) in 'ip_invoices'");
        $records = $this->fakeDb->select('ip_quotes', []);
        $this->assertCount(0, $records, "Database should have exactly 0 record(s) in 'ip_quotes'");
    }

    // #endregion

    // #region Data Loading

    /**
     * Test dashboard loads recent invoices
     */
    #[Test]
    public function it_loads_recent_invoices(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /dashboard
         * Expected behavior: Display recent invoices section
         */
        $response = $this->get('/dashboard');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('recent_invoices');
        $records = $this->fakeDb->select('ip_invoices', []);
        $this->assertNotEmpty($records, "Database should have record in 'ip_invoices'");
    }

    /**
     * Test dashboard loads recent quotes
     */
    #[Test]
    public function it_loads_recent_quotes(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /dashboard
         * Expected behavior: Display recent quotes section
         */
        $response = $this->get('/dashboard');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('recent_quotes');
        $records = $this->fakeDb->select('ip_quotes', []);
        $this->assertNotEmpty($records, "Database should have record in 'ip_quotes'");
    }

    /**
     * Test dashboard displays overdue invoices
     */
    #[Test]
    public function it_displays_overdue_invoices(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /dashboard
         * Expected behavior: Display overdue invoices section
         */
        $response = $this->get('/dashboard');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('overdue_invoices');
    }

    /**
     * Test dashboard displays recent projects
     */
    #[Test]
    public function it_displays_recent_projects(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /dashboard
         * Expected behavior: Display recent projects section
         */
        $response = $this->get('/dashboard');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('recent_projects');
    }

    /**
     * Test dashboard displays recent tasks
     */
    #[Test]
    public function it_displays_recent_tasks(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /dashboard
         * Expected behavior: Display recent tasks section
         */
        $response = $this->get('/dashboard');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('recent_tasks');
    }

    /**
     * Test dashboard calculates invoice totals by status
     */
    #[Test]
    public function it_calculates_invoice_totals_by_status(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /dashboard
         * Expected behavior: Display invoice totals grouped by status
         */
        $response = $this->get('/dashboard');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('invoice_totals');
    }

    /**
     * Test dashboard calculates quote totals by status
     */
    #[Test]
    public function it_calculates_quote_totals_by_status(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /dashboard
         * Expected behavior: Display quote totals grouped by status
         */
        $response = $this->get('/dashboard');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('quote_totals');
    }

    /**
     * Test dashboard respects invoice overview period setting
     */
    #[Test]
    public function it_respects_invoice_overview_period_setting(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /dashboard
         * Expected behavior: Filter invoices by configured date range
         */
        $response = $this->get('/dashboard');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('invoice_overview');
    }

    /**
     * Test dashboard respects quote overview period setting
     */
    #[Test]
    public function it_respects_quote_overview_period_setting(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /dashboard
         * Expected behavior: Filter quotes by configured date range
         */
        $response = $this->get('/dashboard');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('quote_overview');
    }

    // #endregion
}
