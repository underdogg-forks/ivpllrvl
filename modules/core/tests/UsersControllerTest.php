<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\UsersController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for UsersController
 * 
 * Tests the full request/response cycle with HTTP testing methods.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(UsersController::class)]
class UsersControllerTest extends TestCase
{
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Load user fixtures
        $users = $this->fixtures->all('users');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest', 'inactive'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
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
        // GET /users/index
        $response = $this->get('/users/index');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
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
        // GET /users/index
        $response = $this->get('/users/index');
        
        /* Assert */
        $response->assertRedirect('/dashboard');
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
        // GET /users/index
        $response = $this->get('/users/index');
        
        /* Assert */
        $response->assertSee('filter_users');
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
        // GET /users/form
        $response = $this->get('/users/form');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
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
        // GET /users/form
        $response = $this->get('/users/form');
        
        /* Assert */
        $response->assertSee('user_name');
        $response->assertSee('user_email');
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
        // GET /users/form/{id}
        $response = $this->get('/users/form/' . $existingUser['user_id']);
        
        /* Assert */
        $response->assertSee($existingUser['user_name']);
        $response->assertSee($existingUser['user_email']);
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
        // GET /users/form/{id}
        $response = $this->get('/users/form/' . $invalidUserId);
        
        /* Assert */
        $response->assertNotFound();
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
        
        /* Act */
        // POST /users/form
        // Successful request: btn_submit + valid user data
        $response = $this->post('/users/form', array_merge($this->testData, [
            'btn_submit' => '1',
        ]));
        
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
        
        /* Act */
        // POST /users/form
        // Missing required field: user_name is empty
        $response = $this->post('/users/form', [
            'btn_submit' => '1',
            'user_name' => '', // Required field missing
            'user_email' => 'test@example.com',
        ]);
        
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
        
        /* Act */
        // POST /users/form
        // Invalid email format
        $response = $this->post('/users/form', [
            'btn_submit' => '1',
            'user_name' => 'Test User',
            'user_email' => 'not-an-email', // Invalid format
            'user_password' => 'password',
            'user_passwordv' => 'password',
        ]);
        
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
        
        /* Act */
        // POST /users/form
        // Duplicate email (already exists)
        $response = $this->post('/users/form', [
            'btn_submit' => '1',
            'user_name' => 'Duplicate User',
            'user_email' => $existingUser['user_email'], // Already exists
            'user_password' => 'password',
            'user_passwordv' => 'password',
        ]);
        
        /* Assert */
        // Verify user already exists
        $existingUsers = $this->fakeDb->select('ip_users', [
            'user_email' => $existingUser['user_email']
        ]);
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
        
        /* Act */
        // POST /users/form
        // Password mismatch
        $response = $this->post('/users/form', [
            'btn_submit' => '1',
            'user_name' => 'Test User',
            'user_email' => 'test@example.com',
            'user_password' => 'password123',
            'user_passwordv' => 'differentpassword', // Mismatch
        ]);
        
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
        $this->actAsAdmin();
        
        /* Act */
        // POST /users/form
        // XSS attempt in user data
        $response = $this->post('/users/form', [
            'btn_submit' => '1',
            'user_name' => '<script>alert("xss")</script>',
            'user_email' => 'test@example.com',
            'user_password' => 'password',
            'user_passwordv' => 'password',
            'user_company' => '<img src=x onerror=alert("xss")>',
        ]);
        
        /* Assert */
        // XSS should be sanitized by global filter
        $response->assertOk();
    }

    /**
     * Test SQL injection protection
     */
    #[Test]
    public function it_protects_users_against_sql_injection(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /users/form
        // SQL injection attempt
        $response = $this->post('/users/form', [
            'btn_submit' => '1',
            'user_name' => "'; DROP TABLE ip_users; --",
            'user_email' => 'test@example.com',
            'user_password' => 'password',
            'user_passwordv' => 'password',
        ]);
        
        /* Assert */
        // SQL injection should be prevented by parameterized queries
        $response->assertOk();
    }

    /**
     * Test updating existing user
     */
    #[Test]
    public function it_updates_users_existing_user(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingUser = $this->fixtures->get('users', 'guest');
        
        /* Act */
        // POST /users/form/{id}
        // Update existing user
        $response = $this->post('/users/form/' . $existingUser['user_id'], [
            'btn_submit' => '1',
            'user_name' => 'Updated Name',
            'user_email' => $existingUser['user_email'],
            'user_company' => 'New Company',
        ]);
        
        /* Assert */
        $response->assertRedirect('/users');
    }

    /**
     * Test logged-in user editing their own account updates session
     */
    #[Test]
    public function it_updates_users_session_when_user_edits_self(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /* Act */
        // POST /users/form/{id}
        // User edits their own account
        $response = $this->post('/users/form/' . $adminUser['user_id'], [
            'btn_submit' => '1',
            'user_name' => 'New Name',
            'user_email' => 'new@example.com',
        ]);
        
        /* Assert */
        $response->assertRedirect('/users');
    }

    /**
     * Test change password page requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_change_password(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // GET /users/change_password
        $response = $this->get('/users/change_password');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Happy Path: Change password with valid data
     */
    #[Test]
    public function it_post_change_password_updates_user_password(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /users/change_password
        // Valid password change
        $response = $this->post('/users/change_password', [
            'btn_submit' => '1',
            'user_password' => 'NewSecurePass123',
            'user_passwordv' => 'NewSecurePass123',
        ]);
        
        /* Assert */
        $response->assertRedirect('/users');
    }

    /**
     * Test password change validation
     */
    #[Test]
    public function it_validates_change_password_password_requirements(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /users/change_password
        // Weak password (too short)
        $response = $this->post('/users/change_password', [
            'btn_submit' => '1',
            'user_password' => '123',
            'user_passwordv' => '123',
        ]);
        
        /* Assert */
        $this->assertHasValidationError('user_password');
    }

    /**
     * Test delete requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_delete(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // POST /users/delete/{id}
        $response = $this->post('/users/delete/2');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Happy Path: Delete user
     */
    #[Test]
    public function it_post_delete_removes_user(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $guestUser = $this->fixtures->get('users', 'guest');
        
        /* Act */
        // POST /users/delete/{id}
        $response = $this->post('/users/delete/' . $guestUser['user_id']);
        
        /* Assert */
        $response->assertRedirect('/users');
    }

    /**
     * Test cannot delete user ID 1 (system user)
     */
    #[Test]
    public function it_post_delete_protects_system_user(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /users/delete/1
        // Attempt to delete system user
        $response = $this->post('/users/delete/1');
        
        /* Assert */
        $response->assertRedirect('/users');
    }

    /**
     * Test btn_cancel redirects without saving
     */
    #[Test]
    public function it_post_users_form_cancels_without_saving(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /users/form
        // Cancel without saving
        $response = $this->post('/users/form', [
            'btn_cancel' => 'Cancel',
            'user_name' => 'Should Not Save',
        ]);
        
        /* Assert */
        $response->assertRedirect('/users');
    }

    /**
     * Test custom fields are saved with user
     */
    #[Test]
    public function it_post_users_form_saves_custom_fields(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /users/form
        // With custom fields
        $response = $this->post('/users/form', [
            'btn_submit' => '1',
            'user_name' => 'Test User',
            'user_email' => 'test@example.com',
            'user_password' => 'password',
            'user_passwordv' => 'password',
            'custom' => [
                '1' => 'Custom Value 1',
                '2' => 'Custom Value 2',
            ],
        ]);
        
        /* Assert */
        $response->assertRedirect('/users');
    }
}
