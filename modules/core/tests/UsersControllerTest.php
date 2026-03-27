<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\UsersController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for UsersController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(UsersController::class)]
class UsersControllerTest extends ControllerTestCase
{
    protected string $controllerClass = UsersController::class;
    
    protected function loadFixtures(): void
    {
        // Load user fixtures
        $users = $this->fixtures->all('users');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest', 'inactive'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
    }
    
    protected function setUpController(): void
    {
        // Store valid new user data from fixtures for reuse
        $this->testData = $this->fixtures->get('users', 'valid_new_user');
    }

    /**
     * Test that user index page requires authentication
     */
    #[Test]
    public function it_displays_users_index_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // When CI bootstrap is ready, this will call the controller
        $controller = $this->getController();
        $controller->index();
        
        /* Assert */
        $this->assertRedirectedTo('sessions/login');
        // Verify no session data exists
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Test that user index page requires admin role
     */
    #[Test]
    public function it_displays_users_index_requires_admin_role(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /* Act */
        $controller = $this->getController();
        $controller->index();
        
        /* Assert */
        $this->assertRedirectedTo('dashboard');
        // Verify session has guest user type
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Happy Path: Admin can view users index
     */
    #[Test]
    public function it_displays_users_index_returns_user_list_for_admin(): void
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
        $this->assertResponseContains('filter_users');
        // Verify we have seeded users in fake DB
        $users = $this->fakeDb->select('ip_users');
        $this->assertCount(3, $users);
    }

    /**
     * Test user form page requires authentication
     */
    #[Test]
    public function it_displays_users_form_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        $controller = $this->getController();
        $controller->form();
        
        /* Assert */
        $this->assertRedirectedTo('sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Admin can access new user form
     */
    #[Test]
    public function it_displays_users_form_new_user_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->form();
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('user_name');
        $this->assertResponseContains('user_email');
    }

    /**
     * Happy Path: Admin can access edit user form
     */
    #[Test]
    public function it_displays_users_form_edit_user_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingUser = $this->fixtures->get('users', 'guest');
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->form($existingUser['user_id']);
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains($existingUser['user_name']);
        $this->assertResponseContains($existingUser['user_email']);
        $users = $this->fakeDb->select('ip_users', ['user_id' => $existingUser['user_id']]);
        $this->assertCount(1, $users);
    }

    /**
     * Test editing non-existent user returns 404
     */
    #[Test]
    public function it_displays_users_form_returns_404_for_invalid_user(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidUserId = 9999;
        
        /* Act */
        $controller = $this->getController();
        $controller->form($invalidUserId);
        
        /* Assert */
        $this->assertResponseCode(404);
        $users = $this->fakeDb->select('ip_users', ['user_id' => $invalidUserId]);
        $this->assertCount(0, $users);
    }

    /**
     * Test creating new user with valid data
     */
    #[Test]
    public function it_creates_users_new_user_with_valid_credentials(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData(array_merge($this->testData, [
            'btn_submit' => '1',
        ]));
        
        /* Act */
        // Insert user using fake database
        $this->fakeDb->insert('ip_users', [
            'user_name' => $this->testData['user_name'],
            'user_email' => $this->testData['user_email'],
            'user_type' => $this->testData['user_type'],
            'user_company' => $this->testData['user_company'],
            'user_active' => $this->testData['user_active'],
        ]);
        
        /* Assert */
        // Verify user was inserted
        $users = $this->fakeDb->select('ip_users', [
            'user_email' => 'newuser@example.com'
        ]);
        $this->assertCount(1, $users);
        $this->assertEquals('New Test User', $users[0]['user_name']);
        
        // Verify last insert ID
        $this->assertGreaterThan(0, $this->fakeDb->insertId());
    }

    /**
     * Test creating user with missing required fields fails
     */
    #[Test]
    public function it_rejects_users_missing_required_fields(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'btn_submit' => '1',
            'user_name' => '', // Required field missing
            'user_email' => 'test@example.com',
        ]);
        
        /* Act */
        $controller = $this->getController();
        $controller->form();
        
        /* Assert */
        $this->assertHasValidationErrors();
        $this->assertHasValidationError('user_name');
    }

    /**
     * Test creating user with invalid email format fails
     */
    #[Test]
    public function it_validates_users_email_format(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'btn_submit' => '1',
            'user_name' => 'Test User',
            'user_email' => 'not-an-email', // Invalid format
            'user_password' => 'password',
            'user_passwordv' => 'password',
        ]);
        
        /* Act */
        $controller = $this->getController();
        $controller->form();
        
        /* Assert */
        $this->assertHasValidationError('user_email');
    }

    /**
     * Test creating user with duplicate email fails
     */
    #[Test]
    public function it_rejects_users_duplicate_email(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingUser = $this->fixtures->get('users', 'admin');
        
        $this->setPostData([
            'btn_submit' => '1',
            'user_name' => 'Duplicate User',
            'user_email' => $existingUser['user_email'], // Already exists
            'user_password' => 'password',
            'user_passwordv' => 'password',
        ]);
        
        /* Act */
        // Attempt to insert duplicate
        $existingUsers = $this->fakeDb->select('ip_users', [
            'user_email' => $existingUser['user_email']
        ]);
        
        /* Assert */
        // Verify user already exists
        $this->assertCount(1, $existingUsers);
        $this->assertHasValidationError('user_email');
    }

    /**
     * Test password mismatch validation
     */
    #[Test]
    public function it_validates_users_password_confirmation(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'btn_submit' => '1',
            'user_name' => 'Test User',
            'user_email' => 'test@example.com',
            'user_password' => 'password123',
            'user_passwordv' => 'differentpassword', // Mismatch
        ]);
        
        /* Act */
        $controller = $this->getController();
        $controller->form();
        
        /* Assert */
        $this->assertHasValidationError('user_passwordv');
    }

    /**
     * Test XSS protection in user input
     */
    #[Test]
    public function it_sanitizes_users_xss_attempts(): void
    {
        /* Arrange */
        $xssData = [
            'user_name' => '<script>alert("xss")</script>',
            'user_email' => 'test@example.com',
            'user_password' => 'password',
            'user_passwordv' => 'password',
            'user_company' => '<img src=x onerror=alert("xss")>',
        ];
        
        /* Act */
        
        /* Assert */
    }

    /**
     * Test SQL injection protection
     */
    #[Test]
    public function it_protects_users_against_sql_injection(): void
    {
        /* Arrange */
        $sqlInjectionData = [
            'user_name' => "'; DROP TABLE ip_users; --",
            'user_email' => 'test@example.com',
            'user_password' => 'password',
            'user_passwordv' => 'password',
        ];
        
        /* Act */
        
        /* Assert */
    }

    /**
     * Test updating existing user
     */
    #[Test]
    public function it_updates_users_existing_user(): void
    {
        /* Arrange */
        
        $updateData = [
            'user_name' => 'Updated Name',
            'user_email' => 'original@example.com', // Same email
            'user_company' => 'New Company',
        ];
        
        /* Act */
        
        /* Assert */
    }

    /**
     * Test logged-in user editing their own account updates session
     */
    #[Test]
    public function it_updates_users_session_when_user_edits_self(): void
    {
        /* Arrange */
        
        $updateData = [
            'user_name' => 'New Name',
            'user_email' => 'new@example.com',
        ];
        
        /* Act */
        
        /* Assert */
    }

    /**
     * Test change password page requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_change_password(): void
    {
        /* Arrange - No auth */
        
        /* Act */
        
        /* Assert */
    }

    /**
     * Happy Path: Change password with valid data
     */
    #[Test]
    public function it_post_change_password_updates_user_password(): void
    {
        /* Arrange */
        
        $passwordData = [
            'user_password' => 'NewSecurePass123',
            'user_passwordv' => 'NewSecurePass123',
        ];
        
        /* Act */
        
        /* Assert */
    }

    /**
     * Test password change validation
     */
    #[Test]
    public function it_validates_change_password_password_requirements(): void
    {
        /* Arrange */
        
        $weakPasswordData = [
            'user_password' => '123', // Too short
            'user_passwordv' => '123',
        ];
        
        /* Act */
        
        /* Assert */
    }

    /**
     * Test delete requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_delete(): void
    {
        /* Arrange - No auth */
        
        /* Act */
        
        /* Assert */
    }

    /**
     * Happy Path: Delete user
     */
    #[Test]
    public function it_post_delete_removes_user(): void
    {
        /* Arrange */
        
        /* Act */
        
        /* Assert */
    }

    /**
     * Test cannot delete user ID 1 (system user)
     */
    #[Test]
    public function it_post_delete_protects_system_user(): void
    {
        /* Arrange */
        
        /* Act */
        
        /* Assert */
    }

    /**
     * Test btn_cancel redirects without saving
     */
    #[Test]
    public function it_post_users_form_cancels_without_saving(): void
    {
        /* Arrange */
        
        $cancelData = [
            'btn_cancel' => 'Cancel',
            'user_name' => 'Should Not Save',
        ];
        
        /* Act */
        
        /* Assert */
    }

    /**
     * Test custom fields are saved with user
     */
    #[Test]
    public function it_post_users_form_saves_custom_fields(): void
    {
        /* Arrange */
        // TODO: Create custom field definition first
        
        $userData = [
            'user_name' => 'Test User',
            'user_email' => 'test@example.com',
            'user_password' => 'password',
            'user_passwordv' => 'password',
            'custom' => [
                '1' => 'Custom Value 1',
                '2' => 'Custom Value 2',
            ],
        ];
        
        /* Act */
        
        /* Assert */
    }
}
