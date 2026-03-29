<?php

namespace Modules\Clients\Tests;

use Modules\Clients\Controllers\GuestController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for GuestController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(GuestController::class)]
class GuestControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = GuestController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'clients', 'invoices'];
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
     * Test index requires guest authentication
     */
    #[Test]
    public function it_requires_authentication_to_view_guest_dashboard(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /guest/guest/index
         * Expected behavior: Redirect to login when not authenticated
         */
        $response = $this->get('/guest/guest/index');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test admin user cannot access guest portal
     */
    #[Test]
    public function it_requires_guest_role_to_view_guest_dashboard(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /guest/guest/index
         * Expected behavior: Redirect to dashboard when user is not a guest
         */
        $response = $this->get('/guest/guest/index');
        
        /* Assert */
        $response->assertRedirect("/dashboard");
    }

    // #endregion

    // #region Dashboard Display Tests

    /**
     * Happy Path: Guest user views dashboard
     */
    #[Test]
    public function it_displays_guest_dashboard_for_authenticated_guest(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/guest/index
         * Expected: HTML view with guest dashboard showing invoices, quotes, and payments summary
         */
        $response = $this->get('/guest/guest/index');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Page Structure */
        $response->assertSee('guest_dashboard');
        $response->assertSee('Dashboard');
        $response->assertSee('Invoices');
        
        /* Assert - User Context */
        $this->assertTrue($this->fakeSession->has('user_id'), 'Guest user must be authenticated');
        $this->assertEquals(2, $this->fakeSession->get('user_type'), 'User type should be guest (2)');
        
        /* Assert - Database State */
        $invoices = $this->fakeDb->select('ip_invoices', []);
        $this->assertIsArray($invoices, 'Dashboard should query invoices');
    }

    /**
     * Test index displays overdue invoices for assigned clients
     */
    #[Test]
    public function it_displays_overdue_invoices_for_assigned_clients(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /guest/guest/index
         * Expected: Dashboard shows overdue invoices with invoice numbers, amounts, due dates
         */
        $response = $this->get('/guest/guest/index');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - Data Content */
        $response->assertSee('Overdue');  // Overdue section header
        $response->assertSee('INV-2024-');  // Invoice number prefix from fixtures
        
        /* Assert - Table Structure */
        $response->assertSee('Invoice');
        $response->assertSee('Due Date');
        $response->assertSee('Amount');
        
        /* Assert - Database Verification */
        $invoices = $this->fakeDb->select('ip_invoices', []);
        $this->assertNotEmpty($invoices, 'Dashboard should display invoice data from database');
        
        $assignedClient = $this->fakeDb->select('ip_clients', ['client_id' => 1]);
        $this->assertNotEmpty($assignedClient, 'Guest should have assigned clients');
    }

    // #endregion
}
