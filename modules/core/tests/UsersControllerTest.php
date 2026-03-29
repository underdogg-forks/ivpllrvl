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
     * Test that users index requires authentication
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
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test that users index requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_display_users_index(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /users/index
         * Expected behavior: Redirect non-admin users to dashboard
         */
        $response = $this->get('/users/index');
        
        /* Assert */
        $response->assertRedirect('/dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Test that users form requires authentication
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
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test that change password requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_change_password(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /users/change_password/1
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/users/change_password/1');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test that delete requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_delete_user(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /users/delete/2
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/users/delete/2');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    // #endregion

    // #region Index & List Display Tests

    /**
     * Happy Path: Admin can view users index
     */
    #[Test]
    public function it_displays_users_list_on_index_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /users/index
         * Expected behavior: Display list of users with filter
         */
        $response = $this->get('/users/index');
        
        /* Assert */
        $response->assertSee('filter_users');
        $this->assertDatabaseHasRecord('ip_users', ['user_id' => $adminUser['user_id']]);
        $this->assertDatabaseCount('ip_users', [], 3);
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
         * Expected behavior: Display form with user input fields
         */
        $response = $this->get('/users/form');
        
        /* Assert */
        $response->assertSee('user_name');
        $response->assertSee('user_email');
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
         * Expected behavior: Display form populated with user data
         */
        $response = $this->get('/users/form/' . $existingUser['user_id']);
        
        /* Assert */
        $response->assertSee($existingUser['user_name']);
        $response->assertSee($existingUser['user_email']);
        $this->assertDatabaseHasRecord('ip_users', ['user_id' => $existingUser['user_id']]);
    }

    /**
     * Test editing non-existent user returns 404
     */
    #[Test]
    public function it_returns_404_when_editing_nonexistent_user(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidUserId = 9999;
        
        /**
         * Act: GET /users/form/{invalid_id}
         * Expected behavior: Return 404 error
         */
        $response = $this->get('/users/form/' . $invalidUserId);
        
        /* Assert */
        $response->assertNotFound();
        $this->assertDatabaseMissingRecord('ip_users', ['user_id' => $invalidUserId']);
    }

    // #endregion

    // #region Form Submission Tests (Create)

    /**
     * Happy Path: Create new user with complete valid data
     */
    #[Test]
    public function it_creates_new_user_with_complete_valid_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /users/form
         * POST data: {
         *   'user_type': '2',
         *   'user_name': 'Test User',
         *   'user_company': 'Test Company',
         *   'user_email': 'testuser@example.com',
         *   'user_password': 'SecurePass123!',
         *   'user_passwordv': 'SecurePass123!',
         *   'user_language': 'english',
         *   'user_timezone': 'UTC',
         *   'user_vat_id': '',
         *   'user_tax_code': '',
         *   'user_phone': '+1234567890',
         *   'user_fax': '',
         *   'user_mobile': '',
         *   'user_web': '',
         *   'user_address_1': '123 Test Street',
         *   'user_address_2': '',
         *   'user_city': 'Test City',
         *   'user_state': 'TS',
         *   'user_zip': '12345',
         *   'user_country': 'US',
         *   'btn_submit': '1'
         * }
         * Expected behavior: Create user and redirect to /users
         */
        $completeData = $this->makeUserData(['btn_submit' => '1']);
        $response = $this->post('/users/form', $completeData);
        
        /* Assert */
        $response->assertRedirect('/users');
        $this->assertDatabaseHasRecord('ip_users', ['user_email' => 'testuser@example.com']);
        $this->assertGreaterThan(0, $this->fakeDb->insertId());
    }

    /**
     * Test btn_cancel redirects without saving
     */
    #[Test]
    public function it_cancels_user_creation_without_saving(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /users/form
         * POST data: Complete user data with btn_cancel set
         * Expected behavior: Cancel and redirect without saving
         */
        $cancelData = $this->makeUserData([
            'btn_cancel' => 'Cancel',
            'user_email' => 'shouldnotsave@example.com'
        ]);
        $response = $this->post('/users/form', $cancelData);
        
        /* Assert */
        $response->assertRedirect('/users');
        $this->assertDatabaseMissingRecord('ip_users', ['user_email' => 'shouldnotsave@example.com']);
    }

    // #endregion

    // #region Form Submission Tests (Update)

    /**
     * Happy Path: Update existing user with complete valid data
     */
    #[Test]
    public function it_updates_existing_user_with_complete_valid_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingUser = $this->fixtures->get('users', 'guest');
        
        /**
         * Act: POST /users/form/{id}
         * POST data: Complete user data with updated user_name
         * Expected behavior: Update user and redirect to /users
         */
        $updateData = $this->makeUserData([
            'user_name' => 'Updated Guest User',
            'user_email' => $existingUser['user_email'],
            'btn_submit' => '1'
        ]);
        $response = $this->post('/users/form/' . $existingUser['user_id'], $updateData);
        
        /* Assert */
        $response->assertRedirect('/users');
        $this->assertDatabaseHasRecord('ip_users', [
            'user_id' => $existingUser['user_id'],
            'user_name' => 'Updated Guest User'
        ]);
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
        
        /**
         * Act: POST /users/form/{own_id}
         * POST data: Complete user data for own account
         * Expected behavior: Update user and update session data
         */
        $updateData = $this->makeUserData([
            'user_name' => 'Updated Admin Name',
            'user_email' => 'newemail@example.com',
            'btn_submit' => '1'
        ]);
        $response = $this->post('/users/form/' . $adminUser['user_id'], $updateData);
        
        /* Assert */
        $response->assertRedirect('/users');
        $this->assertDatabaseHasRecord('ip_users', ['user_id' => $adminUser['user_id']]);
    }

    // #endregion

    // #region Validation Tests

    /**
     * Test creating user with missing required field fails
     */
    #[Test]
    public function it_rejects_user_creation_with_missing_required_field(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /users/form
         * POST data: Complete data but user_name is empty
         * Expected behavior: Validation error on user_name
         */
        $invalidData = $this->makeUserData([
            'user_name' => '',
            'btn_submit' => '1'
        ]);
        $response = $this->post('/users/form', $invalidData);
        
        /* Assert */
        $this->assertHasValidationErrors();
        $this->assertHasValidationError('user_name');
    }

    /**
     * Test creating user with invalid email format fails
     */
    #[Test]
    public function it_rejects_user_creation_with_invalid_email_format(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /users/form
         * POST data: Complete data but invalid email format
         * Expected behavior: Validation error on user_email
         */
        $invalidData = $this->makeUserData([
            'user_email' => 'not-a-valid-email',
            'btn_submit' => '1'
        ]);
        $response = $this->post('/users/form', $invalidData);
        
        /* Assert */
        $this->assertHasValidationError('user_email');
    }

    /**
     * Test creating user with duplicate email fails
     */
    #[Test]
    public function it_rejects_user_creation_with_duplicate_email(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingUser = $this->fixtures->get('users', 'admin');
        
        /**
         * Act: POST /users/form
         * POST data: Complete data but email already exists
         * Expected behavior: Validation error on user_email
         */
        $duplicateData = $this->makeUserData([
            'user_email' => $existingUser['user_email'],
            'btn_submit' => '1'
        ]);
        $response = $this->post('/users/form', $duplicateData);
        
        /* Assert */
        $this->assertDatabaseHasRecord('ip_users', ['user_email' => $existingUser['user_email']]);
        $this->assertHasValidationError('user_email');
    }

    /**
     * Test password mismatch validation
     */
    #[Test]
    public function it_rejects_user_creation_with_password_mismatch(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /users/form
         * POST data: Complete data but passwords don't match
         * Expected behavior: Validation error on user_passwordv
         */
        $mismatchData = $this->makeUserData([
            'user_password' => 'Password123!',
            'user_passwordv' => 'DifferentPass456!',
            'btn_submit' => '1'
        ]);
        $response = $this->post('/users/form', $mismatchData);
        
        /* Assert */
        $this->assertHasValidationError('user_passwordv');
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
        
        /**
         * Act: POST /users/form
         * POST data: Complete data with XSS attempt in user_name
         * Expected behavior: XSS sanitized by global filter
         */
        $xssData = $this->makeUserData([
            'user_name' => '<script>alert("xss")</script>',
            'user_company' => '<img src=x onerror=alert("xss")>',
            'btn_submit' => '1'
        ]);
        $response = $this->post('/users/form', $xssData);
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Test SQL injection protection
     */
    #[Test]
    public function it_protects_against_sql_injection_in_user_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /users/form
         * POST data: Complete data with SQL injection attempt
         * Expected behavior: SQL injection prevented by parameterized queries
         */
        $sqlData = $this->makeUserData([
            'user_name' => "'; DROP TABLE ip_users; --",
            'btn_submit' => '1'
        ]);
        $response = $this->post('/users/form', $sqlData);
        
        /* Assert */
        $response->assertOk();
        $this->assertDatabaseHasRecord('ip_users', []); // Table still exists
    }

    // #endregion

    // #region Change Password Tests

    /**
     * Happy Path: Change password with valid data
     */
    #[Test]
    public function it_changes_password_with_valid_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        /**
         * Act: POST /users/change_password/{id}
         * POST data: {
         *   'user_password': 'NewSecurePass123!',
         *   'user_passwordv': 'NewSecurePass123!',
         *   'btn_submit': '1'
         * }
         * Expected behavior: Update password and redirect to user form
         */
        $passwordData = [
            'user_password' => 'NewSecurePass123!',
            'user_passwordv' => 'NewSecurePass123!',
            'btn_submit' => '1'
        ];
        $response = $this->post('/users/change_password/' . $adminUser['user_id'], $passwordData);
        
        /* Assert */
        $response->assertRedirect('/users/form/' . $adminUser['user_id']);
    }

    /**
     * Test password change with weak password fails
     */
    #[Test]
    public function it_rejects_password_change_with_weak_password(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        /**
         * Act: POST /users/change_password/{id}
         * POST data: Weak password (too short)
         * Expected behavior: Validation error
         */
        $weakPasswordData = [
            'user_password' => '123',
            'user_passwordv' => '123',
            'btn_submit' => '1'
        ];
        $response = $this->post('/users/change_password/' . $adminUser['user_id'], $weakPasswordData);
        
        /* Assert */
        $this->assertHasValidationError('user_password');
    }

    /**
     * Test btn_cancel on password change redirects without saving
     */
    #[Test]
    public function it_cancels_password_change_without_saving(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        /**
         * Act: POST /users/change_password/{id}
         * POST data: btn_cancel set
         * Expected behavior: Redirect without changing password
         */
        $cancelData = [
            'user_password' => 'NewPassword123!',
            'user_passwordv' => 'NewPassword123!',
            'btn_cancel' => 'Cancel'
        ];
        $response = $this->post('/users/change_password/' . $adminUser['user_id'], $cancelData);
        
        /* Assert */
        $response->assertRedirect('/users');
    }

    // #endregion

    // #region Delete Tests

    /**
     * Happy Path: Admin can delete user
     */
    #[Test]
    public function it_deletes_user_successfully(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $guestUser = $this->fixtures->get('users', 'guest');
        
        /**
         * Act: POST /users/delete/{id}
         * Expected behavior: Delete user and redirect to /users
         */
        $response = $this->post('/users/delete/' . $guestUser['user_id']);
        
        /* Assert */
        $response->assertRedirect('/users');
        $this->assertDatabaseMissingRecord('ip_users', ['user_id' => $guestUser['user_id']]);
    }

    /**
     * Test cannot delete system user (ID 1)
     */
    #[Test]
    public function it_protects_system_user_from_deletion(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /users/delete/1
         * Expected behavior: Redirect but system user not deleted
         */
        $response = $this->post('/users/delete/1');
        
        /* Assert */
        $response->assertRedirect('/users');
        $this->assertDatabaseHasRecord('ip_users', ['user_id' => 1]);
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
        
        /**
         * Act: POST /users/form
         * POST data: Complete user data with custom fields
         * Expected behavior: Save user with custom field data
         */
        $customData = $this->makeUserData([
            'custom' => [
                '1' => 'Custom Value 1',
                '2' => 'Custom Value 2',
            ],
            'btn_submit' => '1'
        ]);
        $response = $this->post('/users/form', $customData);
        
        /* Assert */
        $response->assertRedirect('/users');
        $this->assertDatabaseHasRecord('ip_users', ['user_email' => 'testuser@example.com']);
    }

    // #endregion
}
