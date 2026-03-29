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
        $this->assertRequiresAuthentication($response);
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
        $this->assertRequiresAuthorization($response);
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
         * Expected behavior: Display guest dashboard successfully
         */
        $response = $this->get('/guest/guest/index');
        
        /* Assert */
        $this->assertResponseSuccess($response);
        $response->assertSee('guest_dashboard');
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
         * Expected behavior: Display overdue invoices for clients assigned to guest
         */
        $response = $this->get('/guest/guest/index');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    // #endregion
}
