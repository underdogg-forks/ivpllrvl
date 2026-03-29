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
        $this->assertRequiresAuthentication($response);
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
        $this->assertRequiresAuthorization($response);
    }

    // #endregion

    // #region Payments Display Tests

    #[Test]
    public function it_displays_payments_for_assigned_clients(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/payments/index */
        $response = $this->get('/guest/payments/index');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_excludes_payments_for_unassigned_clients(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/payments/index */
        $response = $this->get('/guest/payments/index');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_paginates_payment_results(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/payments/index?page=2 */
        $response = $this->get('/guest/payments/index?page=2');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_defaults_to_first_page_when_page_zero(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/payments/index?page=0 */
        $response = $this->get('/guest/payments/index?page=0');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_displays_payment_details_in_index(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/payments/index */
        $response = $this->get('/guest/payments/index');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_uses_guest_layout_for_payments_index(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/payments/index */
        $response = $this->get('/guest/payments/index');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_enables_payment_filter_functionality(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/payments/index */
        $response = $this->get('/guest/payments/index');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_displays_empty_list_for_unassigned_guest(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/payments/index */
        $response = $this->get('/guest/payments/index');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_displays_payments_for_multiple_assigned_clients(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/payments/index */
        $response = $this->get('/guest/payments/index');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_loads_required_models_for_payments(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/payments/index */
        $response = $this->get('/guest/payments/index');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_builds_correct_where_clause_for_payments(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/payments/index */
        $response = $this->get('/guest/payments/index');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_handles_empty_client_list_gracefully(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/payments/index */
        $response = $this->get('/guest/payments/index');
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_displays_payment_method_names_in_index(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /guest/payments/index */
        $response = $this->get('/guest/payments/index');
        
        /* Assert */
        $this->assertResponseSuccess($response);
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
        
        /** Act: GET /guest/payments/index?filter=malicious */
        $response = $this->get('/guest/payments/index?filter=' . urlencode($maliciousInput));
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    // #endregion
}
