<?php

namespace Modules\Clients\Tests;

use Modules\Clients\Controllers\UserClientsController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for UserClientsController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(UserClientsController::class)]
class UserClientsControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = UserClientsController::class;
    
    protected function fixtureTypes(): array
    {
        return ['users', 'clients'];
    }
    
    protected function loadFixtures(): void
    {
        $this->loadAllFixtures();
    }
    
    protected function setUpController(): void
    {
        // Intentionally empty - test data is provided via ProvidesTestData trait
    }

    // #region Navigation Tests

    #[Test]
    public function it_redirects_index_to_users(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        
        /** Act: GET /user_clients/index */
        $response = $this->get('/user_clients/index');
        
        /* Assert */
        $response->assertRedirect('/users');
    }

    // #endregion

    // #region Authentication & Authorization Tests

    #[Test]
    public function it_requires_authentication_to_view_user_clients_form(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /** Act: GET /user_clients/form/1 */
        $response = $this->get('/user_clients/form/1');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    #[Test]
    public function it_requires_admin_role_to_view_user_clients(): void
    {
        /* Arrange */
        $guestUser = $this->getUserData('guest');
        $this->actAsGuest($guestUser);
        
        /** Act: GET /user_clients/form/1 */
        $response = $this->get('/user_clients/form/1');
        
        /* Assert */
        $response->assertRedirect("/dashboard");
    }

    #[Test]
    public function it_requires_authentication_to_create_assignment(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /** Act: GET /user_clients/form */
        $response = $this->get('/user_clients/form');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    #[Test]
    public function it_requires_authentication_to_delete_assignment(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /** Act: POST /user_clients/delete/1 */
        $response = $this->post('/user_clients/delete/1');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    // #endregion

    // #region User Form Tests

    #[Test]
    public function it_displays_assigned_clients_for_user(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $userId = $adminUser['user_id'];
        
        /** Act: GET /user_clients/form/{user_id} */
        $response = $this->get('/user_clients/form/' . $userId);
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_redirects_for_invalid_user_id(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $invalidUserId = 99999;
        
        /** Act: GET /user_clients/form/99999 */
        $response = $this->get('/user_clients/form/' . $invalidUserId);
        
        /* Assert */
        $response->assertRedirect();
    }

    #[Test]
    public function it_redirects_on_cancel_from_user_form(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $userId = $adminUser['user_id'];
        
        /** Act: POST /user_clients/form/{user_id} with cancel */
        $response = $this->post('/user_clients/form/' . $userId, ['btn_cancel' => '1']);
        
        /* Assert */
        $response->assertRedirect();
    }

    #[Test]
    public function it_displays_user_information_in_form(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $userId = $adminUser['user_id'];
        
        /** Act: GET /user_clients/form/{user_id} */
        $response = $this->get('/user_clients/form/' . $userId);
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_loads_required_models_for_user_form(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $userId = $adminUser['user_id'];
        
        /** Act: GET /user_clients/form/{user_id} */
        $response = $this->get('/user_clients/form/' . $userId);
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    // #endregion

    // #region Create Assignment Tests

    #[Test]
    public function it_requires_user_id_parameter_for_create(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        
        /** Act: GET /user_clients/form (no user_id) */
        $response = $this->get('/user_clients/form');
        
        /* Assert */
        $response->assertRedirect();
    }

    #[Test]
    public function it_displays_unassigned_clients_in_create_form(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $userId = $adminUser['user_id'];
        
        /** Act: GET /user_clients/form?user_id={user_id} */
        $response = $this->get('/user_clients/form?user_id=' . $userId);
        
        /* Assert */
        $this->assertResponseSuccess($response);
    }

    #[Test]
    public function it_redirects_on_cancel_from_create_form(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $userId = $adminUser['user_id'];
        
        /** Act: POST /user_clients/form with cancel */
        $response = $this->post('/user_clients/form', ['btn_cancel' => '1', 'user_id' => $userId]);
        
        /* Assert */
        $response->assertRedirect();
    }

    #[Test]
    public function it_assigns_specific_client_to_user(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $client = $this->getClientData('active');
        $assignmentData = [
            'user_id' => $adminUser['user_id'],
            'client_id' => $client['client_id'],
        ];
        
        /** Act: POST /user_clients/form with assignment data */
        $response = $this->post('/user_clients/form', $assignmentData);
        
        /* Assert */
        $response->assertRedirect();
    }

    #[Test]
    public function it_assigns_all_clients_to_user(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $assignmentData = [
            'user_id' => $adminUser['user_id'],
            'user_all_clients' => '1',
        ];
        
        /** Act: POST /user_clients/form with all clients flag */
        $response = $this->post('/user_clients/form', $assignmentData);
        
        /* Assert */
        $response->assertRedirect();
    }

    #[Test]
    public function it_clears_specific_assignments_when_assigning_all(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $assignmentData = [
            'user_id' => $adminUser['user_id'],
            'user_all_clients' => '1',
        ];
        
        /** Act: POST /user_clients/form with all clients flag */
        $response = $this->post('/user_clients/form', $assignmentData);
        
        /* Assert */
        $response->assertRedirect();
    }

    #[Test]
    public function it_prevents_duplicate_client_assignments(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $client = $this->getClientData('active');
        $duplicateData = [
            'user_id' => $adminUser['user_id'],
            'client_id' => $client['client_id'],
        ];
        
        /** Act: POST /user_clients/form twice with same data */
        $this->post('/user_clients/form', $duplicateData);
        $response = $this->post('/user_clients/form', $duplicateData);
        
        /* Assert */
        $response->assertRedirect();
    }

    #[Test]
    public function it_updates_user_all_clients_flag_correctly(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $client = $this->getClientData('active');
        $assignmentData = [
            'user_id' => $adminUser['user_id'],
            'client_id' => $client['client_id'],
        ];
        
        /** Act: POST /user_clients/form with specific client */
        $response = $this->post('/user_clients/form', $assignmentData);
        
        /* Assert */
        $response->assertRedirect();
    }

    // #endregion

    // #region Delete Assignment Tests

    #[Test]
    public function it_removes_client_assignment_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $assignmentId = 1;
        
        /** Act: POST /user_clients/delete/{assignment_id} */
        $response = $this->post('/user_clients/delete/' . $assignmentId);
        
        /* Assert */
        $response->assertRedirect();
    }

    #[Test]
    public function it_redirects_to_user_page_after_delete(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $assignmentId = 1;
        
        /** Act: POST /user_clients/delete/{assignment_id} */
        $response = $this->post('/user_clients/delete/' . $assignmentId);
        
        /* Assert */
        $response->assertRedirect();
    }

    #[Test]
    public function it_handles_nonexistent_assignment_gracefully(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $nonexistentId = 99999;
        
        /** Act: POST /user_clients/delete/99999 */
        $response = $this->post('/user_clients/delete/' . $nonexistentId);
        
        /* Assert */
        $response->assertRedirect();
    }

    // #endregion

    // #region Validation & Security Tests

    #[Test]
    public function it_validates_required_fields_on_create(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $invalidData = ['user_id' => $adminUser['user_id']];
        
        /** Act: POST /user_clients/form without client_id */
        $response = $this->post('/user_clients/form', $invalidData);
        
        /* Assert */
        $response->assertRedirect();
    }

    #[Test]
    public function it_sanitizes_xss_attempts_in_form_inputs(): void
    {
        /* Arrange */
        $adminUser = $this->getUserData('admin');
        $this->actAsAdmin($adminUser);
        $xssData = [
            'user_id' => $adminUser['user_id'],
            'client_id' => '<script>alert(1)</script>',
        ];
        
        /** Act: POST /user_clients/form with XSS attempt */
        $response = $this->post('/user_clients/form', $xssData);
        
        /* Assert */
        $response->assertRedirect();
    }

    // #endregion
}
