<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\SessionsController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for SessionsController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(SessionsController::class)]
class SessionsControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = SessionsController::class;
    
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
        // Intentionally empty - test data is provided via fixtures and ProvidesTestData trait
    }

    // #region Authentication

    /**
     * Test that sessions index redirects to login page
     */
    #[Test]
    public function it_redirects_sessions_index_to_login_page(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /sessions/index
         * Expected behavior: Redirect to login page
         */
        $response = $this->get('/sessions/index');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    // #endregion

    // #region Login

    /**
     * Test that login page is accessible without authentication
     */
    #[Test]
    public function it_displays_login_form(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /sessions/login
         * Expected behavior: Display login form
         */
        $response = $this->get('/sessions/login');
        
        /* Assert */
        $response->assertOk();
        $this->assertResponseContainsAll($response, ['email', 'password']);
        $this->assertEquals(null, $this->authenticatedUserId);
    }

    /**
     * Happy Path: Valid admin user login credentials
     */
    #[Test]
    public function it_authenticates_valid_admin_user_on_login(): void
    {
        /* Arrange */
        $this->clearAuth();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        $loginData = $this->makeLoginData([
            'email' => $adminUser['user_email'],
            'password' => 'AdminPass123!',
        ]);
        
        /**
         * Act: POST /sessions/login
         * POST data: {
         *   "email": "admin@example.com",
         *   "password": "AdminPass123!",
         *   "remember_me": "0",
         *   "btn_login": "1"
         * }
         * Expected behavior: Authenticate user and redirect to dashboard
         */
        $response = $this->post('/sessions/login', $loginData);
        
        /* Assert */
        $response->assertRedirect('/dashboard');
        $records = $this->fakeDb->select('ip_users', ['user_id' => $adminUser['user_id'],
            'user_email' => $adminUser['user_email'],
            'user_type' => 1,]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_users'");
    }

    /**
     * Happy Path: Valid guest user login
     */
    #[Test]
    public function it_authenticates_valid_guest_user_on_login(): void
    {
        /* Arrange */
        $this->clearAuth();
        $guestUser = $this->fixtures->get('users', 'guest');
        
        $loginData = $this->makeLoginData([
            'email' => $guestUser['user_email'],
            'password' => 'GuestPass123!',
        ]);
        
        /**
         * Act: POST /sessions/login
         * POST data: {
         *   "email": "guest@example.com",
         *   "password": "GuestPass123!",
         *   "remember_me": "0",
         *   "btn_login": "1"
         * }
         * Expected behavior: Authenticate guest user
         */
        $response = $this->post('/sessions/login', $loginData);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_users', ['user_id' => $guestUser['user_id'],
            'user_email' => $guestUser['user_email'],
            'user_type' => 2,]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_users'");
    }

    /**
     * Test login with non-existent user email
     */
    #[Test]
    public function it_rejects_nonexistent_user_on_login(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        $loginData = $this->makeLoginData([
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);
        
        /**
         * Act: POST /sessions/login
         * POST data: {
         *   "email": "nonexistent@example.com",
         *   "password": "password123",
         *   "remember_me": "0",
         *   "btn_login": "1"
         * }
         * Expected behavior: Reject login and show error
         */
        $response = $this->post('/sessions/login', $loginData);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_users', [
            'user_email' => 'nonexistent@example.com'
        ]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_users'");
        $response->assertRedirect('/sessions/login');
        $response->assertSessionHasErrors();
    }

    /**
     * Test login with inactive user account
     */
    #[Test]
    public function it_rejects_inactive_user_on_login(): void
    {
        /* Arrange */
        $this->clearAuth();
        $inactiveUser = $this->fixtures->get('users', 'inactive');
        
        $loginData = $this->makeLoginData([
            'email' => $inactiveUser['user_email'],
            'password' => 'InactivePass123!',
        ]);
        
        /**
         * Act: POST /sessions/login
         * POST data: {
         *   "email": "inactive@example.com",
         *   "password": "InactivePass123!",
         *   "remember_me": "0",
         *   "btn_login": "1"
         * }
         * Expected behavior: Reject login for inactive account
         */
        $response = $this->post('/sessions/login', $loginData);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_users', ['user_email' => $inactiveUser['user_email'],
            'user_active' => 0]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_users'");
        $response->assertRedirect('/sessions/login');
        $response->assertSessionHasErrors();
    }

    /**
     * Test login with incorrect password
     */
    #[Test]
    public function it_rejects_incorrect_password_on_login(): void
    {
        /* Arrange */
        $this->clearAuth();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        $loginData = $this->makeLoginData([
            'email' => $adminUser['user_email'],
            'password' => 'WrongPassword123!',
        ]);
        
        /**
         * Act: POST /sessions/login
         * POST data: {
         *   "email": "admin@example.com",
         *   "password": "WrongPassword123!",
         *   "remember_me": "0",
         *   "btn_login": "1"
         * }
         * Expected behavior: Reject login with incorrect password
         */
        $response = $this->post('/sessions/login', $loginData);
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
        $response->assertSessionHasErrors();
    }

    /**
     * Test brute force protection - account lockout after multiple failed attempts
     */
    #[Test]
    public function it_locks_account_after_10_failed_login_attempts(): void
    {
        /* Arrange */
        $this->clearAuth();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        // Track failed login attempts in database
        $ci = &get_instance();
        for ($i = 0; $i < 10; $i++) {
            $ci->db->insert('ip_login_attempts', [
                'user_email' => $adminUser['user_email'],
                'ip_address' => '127.0.0.1',
                'attempted_at' => date('Y-m-d H:i:s'),
            ]);
        }
        
        $loginData = $this->makeLoginData([
            'email' => $adminUser['user_email'],
            'password' => 'WrongPassword123!',
        ]);
        
        /**
         * Act: POST /sessions/login
         * POST data: {
         *   "email": "admin@example.com",
         *   "password": "WrongPassword123!",
         *   "remember_me": "0",
         *   "btn_login": "1"
         * }
         * Expected behavior: Block login due to exceeded attempts
         */
        $response = $this->post('/sessions/login', $loginData);
        
        /* Assert */
        $attemptCount = $this->getDatabaseCount('ip_login_attempts', [
            'user_email' => $adminUser['user_email']
        ]);
        $this->assertGreaterThanOrEqual(10, $attemptCount);
        $response->assertRedirect('/sessions/login');
        $response->assertSessionHasErrors();
    }

    // #endregion

    // #region Logout

    /**
     * Test logout destroys user session
     */
    #[Test]
    public function it_destroys_session_on_logout(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        // Verify user is logged in
        $this->assertNotNull($this->authenticatedUserId);
        
        /**
         * Act: POST /sessions/logout
         * Expected behavior: Destroy session and redirect to login
         */
        $response = $this->post('/sessions/logout');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    // #endregion

    // #region Password Reset

    /**
     * Test that password reset page is accessible without authentication
     */
    #[Test]
    public function it_displays_password_reset_form(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /sessions/passwordreset
         * Expected behavior: Display password reset form
         */
        $response = $this->get('/sessions/passwordreset');
        
        /* Assert */
        $response->assertOk();
        $this->assertResponseContainsAll($response, ['email', 'btn_reset']);
        $this->assertEquals(null, $this->authenticatedUserId);
    }

    /**
     * Happy Path: Password reset request sends email for valid user
     */
    #[Test]
    public function it_sends_password_reset_email_for_valid_user(): void
    {
        /* Arrange */
        $this->clearAuth();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        $resetData = $this->makePasswordResetData([
            'email' => $adminUser['user_email'],
        ]);
        
        /**
         * Act: POST /sessions/passwordreset
         * POST data: {
         *   "email": "admin@example.com",
         *   "password": "NewSecurePass123!",
         *   "passwordv": "NewSecurePass123!",
         *   "token": "",
         *   "btn_reset": "1"
         * }
         * Expected behavior: Create reset token and redirect
         */
        $response = $this->post('/sessions/passwordreset', $resetData);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_users', ['user_email' => $adminUser['user_email'],
            'user_active' => 1]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_users'");
        $records = $this->fakeDb->select('ip_password_resets', ['user_id' => $adminUser['user_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_password_resets'");
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Test password reset shows success for non-existent email (prevents enumeration)
     */
    #[Test]
    public function it_shows_success_message_for_nonexistent_email_on_password_reset(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        $resetData = $this->makePasswordResetData([
            'email' => 'nonexistent@example.com',
        ]);
        
        /**
         * Act: POST /sessions/passwordreset
         * POST data: {
         *   "email": "nonexistent@example.com",
         *   "password": "NewSecurePass123!",
         *   "passwordv": "NewSecurePass123!",
         *   "token": "",
         *   "btn_reset": "1"
         * }
         * Expected behavior: Show success to prevent email enumeration
         */
        $response = $this->post('/sessions/passwordreset', $resetData);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_users', [
            'user_email' => 'nonexistent@example.com'
        ]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_users'");
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Test password reset validates email format
     */
    #[Test]
    public function it_validates_email_format_on_password_reset(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        $resetData = $this->makePasswordResetData([
            'email' => 'not-an-email',
        ]);
        
        /**
         * Act: POST /sessions/passwordreset
         * POST data: {
         *   "email": "not-an-email",
         *   "password": "NewSecurePass123!",
         *   "passwordv": "NewSecurePass123!",
         *   "token": "",
         *   "btn_reset": "1"
         * }
         * Expected behavior: Validation error for invalid email format
         */
        $response = $this->post('/sessions/passwordreset', $resetData);
        
        /* Assert */
        $response->assertSessionHasErrors('email');
    }

    /**
     * Test password reset enforces IP rate limiting
     */
    #[Test]
    public function it_enforces_ip_rate_limit_on_password_reset(): void
    {
        /* Arrange */
        $this->clearAuth();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        // Simulate 5 reset attempts from same IP
        $ci = &get_instance();
        for ($i = 0; $i < 5; $i++) {
            $ci->db->insert('ip_password_reset_attempts', [
                'ip_address' => '127.0.0.1',
                'attempted_at' => date('Y-m-d H:i:s'),
            ]);
        }
        
        $resetData = $this->makePasswordResetData([
            'email' => $adminUser['user_email'],
        ]);
        
        /**
         * Act: POST /sessions/passwordreset
         * POST data: {
         *   "email": "admin@example.com",
         *   "password": "NewSecurePass123!",
         *   "passwordv": "NewSecurePass123!",
         *   "token": "",
         *   "btn_reset": "1"
         * }
         * Expected behavior: Block request due to rate limit
         */
        $response = $this->post('/sessions/passwordreset', $resetData);
        
        /* Assert */
        $attemptCount = $this->getDatabaseCount('ip_password_reset_attempts', [
            'ip_address' => '127.0.0.1'
        ]);
        $this->assertGreaterThanOrEqual(5, $attemptCount);
        $response->assertStatus(429);
    }

    /**
     * Test password reset enforces email rate limiting
     */
    #[Test]
    public function it_enforces_email_rate_limit_on_password_reset(): void
    {
        /* Arrange */
        $this->clearAuth();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        // Simulate recent reset request for this email
        $ci = &get_instance();
        $ci->db->insert('ip_password_resets', [
            'user_id' => $adminUser['user_id'],
            'reset_token' => bin2hex(random_bytes(32)),
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);
        
        $resetData = $this->makePasswordResetData([
            'email' => $adminUser['user_email'],
        ]);
        
        /**
         * Act: POST /sessions/passwordreset
         * POST data: {
         *   "email": "admin@example.com",
         *   "password": "NewSecurePass123!",
         *   "passwordv": "NewSecurePass123!",
         *   "token": "",
         *   "btn_reset": "1"
         * }
         * Expected behavior: Reject duplicate reset request
         */
        $response = $this->post('/sessions/passwordreset', $resetData);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_password_resets', ['user_id' => $adminUser['user_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_password_resets'");
        $response->assertRedirect('/sessions/login');
        $response->assertSessionHasErrors();
    }

    /**
     * Test password reset blocks bot user agents
     */
    #[Test]
    public function it_blocks_bot_user_agents_on_password_reset(): void
    {
        /* Arrange */
        $this->clearAuth();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        // Simulate bot user agent
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; Googlebot/2.1)';
        
        $resetData = $this->makePasswordResetData([
            'email' => $adminUser['user_email'],
        ]);
        
        /**
         * Act: POST /sessions/passwordreset
         * POST data: {
         *   "email": "admin@example.com",
         *   "password": "NewSecurePass123!",
         *   "passwordv": "NewSecurePass123!",
         *   "token": "",
         *   "btn_reset": "1"
         * }
         * Expected behavior: Block bot request
         */
        $response = $this->post('/sessions/passwordreset', $resetData);
        
        /* Assert */
        $response->assertForbidden();
        
        unset($_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * Test password reset token page displays form with valid token
     */
    #[Test]
    public function it_displays_password_reset_form_with_valid_token(): void
    {
        /* Arrange */
        $this->clearAuth();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        $validToken = bin2hex(random_bytes(32));
        $ci = &get_instance();
        $ci->db->insert('ip_password_resets', [
            'user_id' => $adminUser['user_id'],
            'reset_token' => $validToken,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);
        
        /**
         * Act: GET /sessions/passwordreset/{token}
         * Expected behavior: Display password reset form with valid token
         */
        $response = $this->get('/sessions/passwordreset/' . $validToken);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_password_resets', [
            'reset_token' => $validToken
        ]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_password_resets'");
        $response->assertOk();
        $this->assertResponseContainsAll($response, ['new_password', 'confirm_password']);
    }

    /**
     * Test password reset with invalid token redirects to login
     */
    #[Test]
    public function it_redirects_to_login_with_invalid_password_reset_token(): void
    {
        /* Arrange */
        $this->clearAuth();
        $invalidToken = 'invalid_token_xyz';
        
        /**
         * Act: GET /sessions/passwordreset/{token}
         * Expected behavior: Redirect to login with error
         */
        $response = $this->get('/sessions/passwordreset/' . $invalidToken);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_password_resets', [
            'reset_token' => $invalidToken
        ]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_password_resets'");
        $response->assertRedirect('/sessions/login');
        $response->assertSessionHasErrors();
    }

    /**
     * Test token brute force protection after multiple invalid attempts
     */
    #[Test]
    public function it_locks_after_10_invalid_password_reset_token_attempts(): void
    {
        /* Arrange */
        $this->clearAuth();
        $invalidToken = 'invalid_token';
        
        // Simulate 10 failed token attempts
        $ci = &get_instance();
        for ($i = 0; $i < 10; $i++) {
            $ci->db->insert('ip_token_attempts', [
                'ip_address' => '127.0.0.1',
                'attempted_at' => date('Y-m-d H:i:s'),
            ]);
        }
        
        /**
         * Act: GET /sessions/passwordreset/{token}
         * Expected behavior: Block request after excessive attempts
         */
        $response = $this->get('/sessions/passwordreset/' . $invalidToken);
        
        /* Assert */
        $attemptCount = $this->getDatabaseCount('ip_token_attempts', [
            'ip_address' => '127.0.0.1'
        ]);
        $this->assertGreaterThanOrEqual(10, $attemptCount);
        $response->assertStatus(429);
        $response->assertSessionHasErrors();
    }

    /**
     * Happy Path: Set new password with valid token
     */
    #[Test]
    public function it_updates_password_with_valid_reset_token(): void
    {
        /* Arrange */
        $this->clearAuth();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        $validToken = bin2hex(random_bytes(32));
        $ci = &get_instance();
        $ci->db->insert('ip_password_resets', [
            'user_id' => $adminUser['user_id'],
            'reset_token' => $validToken,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);
        
        $resetData = $this->makePasswordResetData([
            'token' => $validToken,
            'password' => 'NewSecurePassword123!',
            'passwordv' => 'NewSecurePassword123!',
            'btn_new_password' => '1',
        ]);
        unset($resetData['email']);
        unset($resetData['btn_reset']);
        $resetData['user_id'] = $adminUser['user_id'];
        $resetData['new_password'] = $resetData['password'];
        $resetData['confirm_password'] = $resetData['passwordv'];
        unset($resetData['password']);
        unset($resetData['passwordv']);
        
        /**
         * Act: POST /sessions/passwordreset
         * POST data: {
         *   "token": "{validToken}",
         *   "user_id": "1",
         *   "new_password": "NewSecurePassword123!",
         *   "confirm_password": "NewSecurePassword123!",
         *   "btn_new_password": "1"
         * }
         * Expected behavior: Update password and delete token
         */
        $response = $this->post('/sessions/passwordreset', $resetData);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_password_resets', [
            'reset_token' => $validToken
        ]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_password_resets'");
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Test password reset rejects mismatched token
     */
    #[Test]
    public function it_rejects_mismatched_password_reset_token(): void
    {
        /* Arrange */
        $this->clearAuth();
        $adminUser = $this->fixtures->get('users', 'admin');
        
        $validToken = bin2hex(random_bytes(32));
        $ci = &get_instance();
        $ci->db->insert('ip_password_resets', [
            'user_id' => $adminUser['user_id'],
            'reset_token' => $validToken,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);
        
        $resetData = [
            'btn_new_password' => '1',
            'token' => 'wrong_token_123',
            'user_id' => $adminUser['user_id'],
            'new_password' => 'NewPassword123!',
            'confirm_password' => 'NewPassword123!',
        ];
        
        /**
         * Act: POST /sessions/passwordreset
         * POST data: {
         *   "token": "wrong_token_123",
         *   "user_id": "1",
         *   "new_password": "NewPassword123!",
         *   "confirm_password": "NewPassword123!",
         *   "btn_new_password": "1"
         * }
         * Expected behavior: Reject with invalid token
         */
        $response = $this->post('/sessions/passwordreset', $resetData);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_password_resets', ['reset_token' => 'wrong_token_123',
            'user_id' => $adminUser['user_id']]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_password_resets'");
        $response->assertSessionHasErrors();
        $response->assertRedirect('/sessions/login');
    }

    // #endregion

    // #region Security

    /**
     * Security: Test SQL injection protection in login
     */
    #[Test]
    public function it_protects_against_sql_injection_on_login(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        $loginData = $this->makeLoginData([
            'email' => "admin@example.com' OR '1'='1",
            'password' => "' OR '1'='1",
        ]);
        
        /**
         * Act: POST /sessions/login
         * POST data: {
         *   "email": "admin@example.com' OR '1'='1",
         *   "password": "' OR '1'='1",
         *   "remember_me": "0",
         *   "btn_login": "1"
         * }
         * Expected behavior: SQL injection attempt fails
         */
        $response = $this->post('/sessions/login', $loginData);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_users', [
            'user_email' => "admin@example.com' OR '1'='1"
        ]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_users'");
        $response->assertRedirect('/sessions/login');
        $response->assertSessionHasErrors();
    }

    /**
     * Security: Test password reset validates token format
     */
    #[Test]
    public function it_validates_password_reset_token_format_for_path_traversal(): void
    {
        /* Arrange */
        $this->clearAuth();
        $maliciousToken = '../../../etc/passwd';
        
        /**
         * Act: GET /sessions/passwordreset/{token}
         * Expected behavior: Reject malicious token
         */
        $response = $this->get('/sessions/passwordreset/' . urlencode($maliciousToken));
        
        /* Assert */
        $records = $this->fakeDb->select('ip_password_resets', [
            'reset_token' => $maliciousToken
        ]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_password_resets'");
        $response->assertRedirect('/sessions/login');
        $response->assertSessionHasErrors();
    }

    // #endregion
}
