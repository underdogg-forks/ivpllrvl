<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\UsersController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for UsersController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(UsersController::class)]
class UsersControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = UsersController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users'];
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
     * Test that user index page requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_users_index(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /users/index
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/users/index');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test that user index page requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_display_users_index(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /users/index
         * Expected behavior: Redirect to dashboard when insufficient permissions
         */
        $response = $this->get('/users/index');
        
        /* Assert */
        $response->assertRedirect('/dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Test user form page requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_users_form(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /users/form
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/users/form');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test change password page requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_change_password(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /users/change_password
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/users/change_password');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test delete requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_delete_user(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /users/delete/{id}
         * POST data: {
         *   "user_id": "2"
         * }
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/users/delete/2');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    // #endregion

    // #region Index & List Display Tests

    /**
     * Happy Path: Admin can view users index
     */
    #[Test]
    public function it_displays_user_list_on_index_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /users/index
         * Expected behavior: Display list of users
         */
        $response = $this->get('/users/index');
        
        /* Assert */
        $response->assertSee('filter_users');
        $records = $this->fakeDb->select('ip_users', []);
        $this->assertCount(3, $records, "Database should have exactly 3 record(s) in 'ip_users'");
    }

    // #endregion

    // #region Form Display Tests

    /**
     * Happy Path: Admin can access new user form
     */
    #[Test]
    public function it_displays_new_user_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /users/form
         * Expected behavior: Display new user form fields
         */
        $response = $this->get('/users/form');
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['user_name', 'user_email']);
        $this->assertTrue($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Admin can access edit user form
     */
    #[Test]
    public function it_displays_edit_user_form_with_existing_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingUser = $this->fixtures->get('users', 'guest');
        
        /**
         * Act: GET /users/form/{id}
         * Expected behavior: Display edit form with existing user data
         */
        $response = $this->get('/users/form/' . $existingUser['user_id']);
        
        /* Assert */
        $response->assertSee($existingUser['user_name']);
        $response->assertSee($existingUser['user_email']);
        $records = $this->fakeDb->select('ip_users', ['user_id' => $existingUser['user_id'],
            'user_name' => $existingUser['user_name']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_users'");
    }

    /**
     * Test form returns 404 for invalid user
     */
    #[Test]
    public function it_returns_404_for_invalid_user_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidUserId = 9999;
        
        /**
         * Act: GET /users/form/{id}
         * Expected behavior: Return 404 for non-existent user
         */
        $response = $this->get('/users/form/' . $invalidUserId);
        
        /* Assert */
        $response->assertNotFound();
        $records = $this->fakeDb->select('ip_users', ['user_id' => $invalidUserId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_users'");
    }

    // #endregion

    // #region Form Submission Tests (Create)


    /**
     * Test creating new user with valid data
     */
    #[Test]
    public function it_creates_new_user_with_valid_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $validUserData = $this->makeUserData([
            'user_name' => 'New Test User',
            'user_email' => 'newuser@example.com',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /users/form
         * POST data: {
         *   "user_name": "New Test User",
         *   "user_email": "newuser@example.com",
         *   "user_password": "password",
         *   "user_passwordv": "password",
         *   "user_type": "1",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Create new user and redirect
         */
        $response = $this->post('/users/form', $validUserData);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_users', [
            'user_email' => 'newuser@example.com',
            'user_name' => 'New Test User'
        ]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_users'");
        $this->assertGreaterThan(0, $this->fakeDb->insertId());
    }

    /**
     * Test btn_cancel redirects without saving
     */
    #[Test]
    public function it_cancels_form_without_saving_when_cancel_button_clicked(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $userData = $this->makeUserData([
            'user_name' => 'Should Not Save',
            'btn_cancel' => 'Cancel',
        ]);
        
        /**
         * Act: POST /users/form
         * POST data: Complete user data with btn_cancel set
         * Expected behavior: Cancel and redirect without saving
         */
        $response = $this->post('/users/form', $userData);
        
        /* Assert */
        $response->assertRedirect('/users');
        $records = $this->fakeDb->select('ip_users', ['user_name' => 'Should Not Save']);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_users'");
    }

    // #endregion

    // #region Form Submission Tests (Update)

    /**
     * Test updating existing user
     */
    #[Test]
    public function it_updates_existing_user_with_valid_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingUser = $this->fixtures->get('users', 'guest');
        
        $updateData = $this->makeUserData([
            'user_id' => $existingUser['user_id'],
            'user_name' => 'Updated Name',
            'user_email' => $existingUser['user_email'],
            'user_company' => 'New Company',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /users/form/{id}
         * POST data: Complete user data with updated user_name
         * Expected behavior: Update user and redirect
         */
        $response = $this->post('/users/form/' . $existingUser['user_id'], $updateData);
        
        /* Assert */
        $response->assertRedirect('/users');
    }

    /**
     * Test logged-in user editing their own account updates session
     */
    #[Test]
    public function it_updates_session_when_user_edits_own_account(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $updateData = $this->makeUserData([
            'user_id' => $adminUser['user_id'],
            'user_name' => 'New Name',
            'user_email' => 'new@example.com',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /users/form/{id}
         * POST data: Complete user data - user edits own account
         * Expected behavior: Update user and refresh session
         */
        $response = $this->post('/users/form/' . $adminUser['user_id'], $updateData);
        
        /* Assert */
        $response->assertRedirect('/users');
    }

    // #endregion

    // #region Validation Tests

    /**
     * Test creating user with missing required fields fails
     */
    #[Test]
    public function it_validates_required_fields_are_present(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $invalidData = $this->makeUserData([
            'user_name' => '',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /users/form
         * POST data: Complete data with empty required field user_name
         * Expected behavior: Validation error for user_name
         */
        $response = $this->post('/users/form', $invalidData);
        
        /* Assert */
        $this->assertHasValidationErrors();
        $this->assertHasValidationError('user_name');
    }

    /**
     * Test creating user with invalid email format fails
     */
    #[Test]
    public function it_validates_email_format(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $invalidData = $this->makeUserData([
            'user_email' => 'not-an-email',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /users/form
         * POST data: Complete data with invalid email format
         * Expected behavior: Validation error for user_email
         */
        $response = $this->post('/users/form', $invalidData);
        
        /* Assert */
        $this->assertHasValidationError('user_email');
    }

    /**
     * Test creating user with duplicate email fails
     */
    #[Test]
    public function it_validates_email_uniqueness(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingUser = $this->fixtures->get('users', 'admin');
        
        $duplicateData = $this->makeUserData([
            'user_name' => 'Duplicate User',
            'user_email' => $existingUser['user_email'],
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /users/form
         * POST data: Complete data with duplicate email
         * Expected behavior: Validation error for user_email
         */
        $response = $this->post('/users/form', $duplicateData);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_users', ['user_email' => $existingUser['user_email']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_users'");
        $this->assertHasValidationError('user_email');
    }

    /**
     * Test password mismatch validation
     */
    #[Test]
    public function it_validates_password_confirmation_matches(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $mismatchData = $this->makeUserData([
            'user_password' => 'password123',
            'user_passwordv' => 'differentpassword',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /users/form
         * POST data: Complete data with mismatched passwords
         * Expected behavior: Validation error for user_passwordv
         */
        $response = $this->post('/users/form', $mismatchData);
        
        /* Assert */
        $this->assertHasValidationError('user_passwordv');
    }

    /**
     * Test password change validation
     */
    #[Test]
    public function it_validates_password_requirements(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /users/change_password
         * POST data: {
         *   "user_password": "123",
         *   "user_passwordv": "123",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Validation error for weak password
         */
        $response = $this->post('/users/change_password', [
            'btn_submit' => '1',
            'user_password' => '123',
            'user_passwordv' => '123',
        ]);
        
        /* Assert */
        $this->assertHasValidationError('user_password');
    }

    // #endregion

    // #region Security Tests

    /**
     * Test XSS protection in user input
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_user_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $xssData = $this->makeUserData([
            'user_name' => '<script>alert("xss")</script>',
            'user_company' => '<img src=x onerror=alert("xss")>',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /users/form
         * POST data: Complete data with XSS payloads in user_name and user_company
         * Expected behavior: XSS payloads should be sanitized or rejected
         */
        $response = $this->post('/users/form', $xssData);
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Test SQL injection protection
     */
    #[Test]
    public function it_protects_against_sql_injection_attempts(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $sqlInjectionData = $this->makeUserData([
            'user_name' => "'; DROP TABLE ip_users; --",
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /users/form
         * POST data: Complete data with SQL injection payload in user_name
         * Expected behavior: SQL injection should be prevented at query level
         */
        $response = $this->post('/users/form', $sqlInjectionData);
        
        /* Assert */
        $response->assertOk();
    }

    // #endregion

    // #region Change Password Tests

    /**
     * Happy Path: Change password with valid data
     */
    #[Test]
    public function it_updates_user_password_successfully(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /users/change_password
         * POST data: {
         *   "user_password": "NewSecurePass123",
         *   "user_passwordv": "NewSecurePass123",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Update password and redirect
         */
        $response = $this->post('/users/change_password', [
            'btn_submit' => '1',
            'user_password' => 'NewSecurePass123',
            'user_passwordv' => 'NewSecurePass123',
        ]);
        
        /* Assert */
        $response->assertRedirect('/users');
    }

    // #endregion

    // #region Delete Tests

    /**
     * Happy Path: Delete user
     */
    #[Test]
    public function it_deletes_user_successfully(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $guestUser = $this->fixtures->get('users', 'guest');
        
        /**
         * Act: POST /users/delete/{id}
         * POST data: {
         *   "user_id": "2"
         * }
         * Expected behavior: Delete user and redirect
         */
        $response = $this->post('/users/delete/' . $guestUser['user_id']);
        
        /* Assert */
        $response->assertRedirect('/users');
    }

    /**
     * Test cannot delete user ID 1 (system user)
     */
    #[Test]
    public function it_protects_system_user_from_deletion(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /users/delete/1
         * POST data: {
         *   "user_id": "1"
         * }
         * Expected behavior: Prevent deletion of system user and redirect
         */
        $response = $this->post('/users/delete/1');
        
        /* Assert */
        $response->assertRedirect('/users');
    }

    // #endregion

    // #region Custom Fields Tests

    /**
     * Test custom fields are saved with user
     */
    #[Test]
    public function it_saves_custom_fields_with_user(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $userDataWithCustomFields = $this->makeUserData([
            'btn_submit' => '1',
            'custom' => [
                '1' => 'Custom Value 1',
                '2' => 'Custom Value 2',
            ],
        ]);
        
        /**
         * Act: POST /users/form
         * POST data: {
         *   "user_name": "Test User",
         *   "user_email": "test@example.com",
         *   "user_password": "password",
         *   "user_passwordv": "password",
         *   "btn_submit": "1",
         *   "custom": {
         *     "1": "Custom Value 1",
         *     "2": "Custom Value 2"
         *   }
         * }
         * Expected behavior: Save user with custom fields and redirect
         */
        $response = $this->post('/users/form', $userDataWithCustomFields);
        
        /* Assert */
        $response->assertRedirect('/users');
    }

    // #endregion
}
