<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\SessionsController;
use Modules\Core\Testing\HttpTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for SessionsController
 * 
 * Tests authentication, login, logout functionality with Laravel HTTP testing methods.
 * Uses HTTP requests instead of direct controller instantiation.
 */
#[CoversClass(SessionsController::class)]
class SessionsControllerTest extends HttpTestCase
{
    
    protected function setupDatabase(): void
    {
        parent::setupDatabase();
        
        // Create test users for authentication testing
        $this->testData['admin'] = $this->createUser([
            'user_type' => 1,
            'user_email' => 'admin@example.com',
            'user_name' => 'Admin User',
            'user_active' => 1,
            'password' => 'AdminPass123!',
        ]);
        
        $this->testData['guest'] = $this->createUser([
            'user_type' => 2,
            'user_email' => 'guest@example.com',
            'user_name' => 'Guest User',
            'user_active' => 1,
            'password' => 'GuestPass123!',
        ]);
        
        $this->testData['inactive'] = $this->createUser([
            'user_type' => 1,
            'user_email' => 'inactive@example.com',
            'user_name' => 'Inactive User',
            'user_active' => 0,
            'password' => 'InactivePass123!',
        ]);
    }

    /**
     * Test that index redirects to login
     */
    #[Test]
    public function it_displays_sessions_index_redirects_to_login(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        
        /* Act */
        // GET /sessions/index
        $response = $this->get('/sessions/index');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Test that login page is accessible
     */
    #[Test]
    public function it_displays_login_login_form(): void
    {
        /* Arrange */
        $this->clearAuthentication(); // No authentication needed for login page
        
        /* Act */
        // GET /sessions/login
        $response = $this->get('/sessions/login');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('email');
        $response->assertSee('password');
        $this->assertEquals(null, $this->authenticatedUserId);
    }

    /**
     * Happy Path: Valid login credentials
     */
    #[Test]
    public function it_post_login_authenticates_valid_admin_user(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        
        /* Act */
        // POST /sessions/login
        // Successful admin login: ['btn_login' => '1', 'email' => 'admin@example.com', 'password' => 'AdminPass123!']
        $response = $this->post('/sessions/login', [
            'btn_login' => '1',
            'email' => 'admin@example.com',
            'password' => 'AdminPass123!',
        ]);
        
        /* Assert */
        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('ip_users', [
            'user_id' => $this->testData['admin'],
            'user_email' => 'admin@example.com',
            'user_type' => 1,
        ]);
    }

    /**
     * Happy Path: Guest user login
     */
    #[Test]
    public function it_post_login_authenticates_valid_guest_user(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        
        /* Act */
        // POST /sessions/login
        // Successful guest login: ['btn_login' => '1', 'email' => 'guest@example.com', 'password' => 'GuestPass123!']
        $response = $this->post('/sessions/login', [
            'btn_login' => '1',
            'email' => 'guest@example.com',
            'password' => 'GuestPass123!',
        ]);
        
        /* Assert */
        $this->assertDatabaseHas('ip_users', [
            'user_id' => $this->testData['guest'],
            'user_email' => 'guest@example.com',
            'user_type' => 2,
        ]);
    }

    /**
     * Test login with non-existent user
     */
    #[Test]
    public function it_post_login_rejects_nonexistent_user(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        
        /* Act */
        // POST /sessions/login
        // Failed login attempt: ['btn_login' => '1', 'email' => 'nonexistent@example.com', 'password' => 'password123']
        $response = $this->post('/sessions/login', [
            'btn_login' => '1',
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);
        
        /* Assert */
        // Verify user does not exist
        $this->assertDatabaseMissing('ip_users', [
            'user_email' => 'nonexistent@example.com'
        ]);
        // Should redirect back to login with error
        $response->assertRedirect('/sessions/login');
        $response->assertSessionHasErrors();
    }

    /**
     * Test login with inactive user
     */
    #[Test]
    public function it_post_login_rejects_inactive_user(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        
        /* Act */
        // POST /sessions/login
        // Inactive user login attempt: ['btn_login' => '1', 'email' => 'inactive@example.com', 'password' => 'InactivePass123!']
        $response = $this->post('/sessions/login', [
            'btn_login' => '1',
            'email' => 'inactive@example.com',
            'password' => 'InactivePass123!',
        ]);
        
        /* Assert */
        // Verify inactive user exists but is not active
        $this->assertDatabaseHas('ip_users', [
            'user_email' => 'inactive@example.com',
            'user_active' => 0
        ]);
        // Should redirect back to login with error
        $response->assertRedirect('/sessions/login');
        $response->assertSessionHasErrors();
    }

    /**
     * Test login with incorrect password
     */
    #[Test]
    public function it_post_login_rejects_incorrect_password(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        
        /* Act */
        // POST /sessions/login
        // Wrong password attempt: ['btn_login' => '1', 'email' => 'admin@example.com', 'password' => 'WrongPassword123!']
        $response = $this->post('/sessions/login', [
            'btn_login' => '1',
            'email' => 'admin@example.com',
            'password' => 'WrongPassword123!',
        ]);
        
        /* Assert */
        // Should redirect back to login with error
        $response->assertRedirect('/sessions/login');
        $response->assertSessionHasErrors();
    }

    /**
     * Test brute force protection - account lockout after 10 failures
     */
    #[Test]
    public function it_post_login_locks_account_after_10_failed_attempts(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        
        // Track failed login attempts in database
        $ci = &get_instance();
        for ($i = 0; $i < 10; $i++) {
            $ci->db->insert('ip_login_attempts', [
                'user_email' => 'admin@example.com',
                'ip_address' => '127.0.0.1',
                'attempted_at' => date('Y-m-d H:i:s'),
            ]);
        }
        
        /* Act */
        // POST /sessions/login
        // 11th failed attempt: ['btn_login' => '1', 'email' => 'admin@example.com', 'password' => 'WrongPassword123!']
        $response = $this->post('/sessions/login', [
            'btn_login' => '1',
            'email' => 'admin@example.com',
            'password' => 'WrongPassword123!',
        ]);
        
        /* Assert */
        // Verify 10 failed attempts recorded
        $attemptCount = $this->getDatabaseCount('ip_login_attempts', [
            'user_email' => 'admin@example.com'
        ]);
        $this->assertGreaterThanOrEqual(10, $attemptCount);
        $response->assertRedirect('/sessions/login');
        $response->assertSessionHasErrors();
    }

    /**
     * Test SQL injection protection in login
     */
    #[Test]
    public function it_post_login_protects_against_sql_injection(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        
        /* Act */
        // POST /sessions/login
        // SQL injection attempt: ['btn_login' => '1', 'email' => "admin@example.com' OR '1'='1", 'password' => "' OR '1'='1"]
        $response = $this->post('/sessions/login', [
            'btn_login' => '1',
            'email' => "admin@example.com' OR '1'='1",
            'password' => "' OR '1'='1",
        ]);
        
        /* Assert */
        // Verify SQL injection attempt fails
        $this->assertDatabaseMissing('ip_users', [
            'user_email' => "admin@example.com' OR '1'='1"
        ]);
        $response->assertRedirect('/sessions/login');
        $response->assertSessionHasErrors();
    }

    /**
     * Test logout destroys session
     */
    #[Test]
    public function it_post_logout_destroys_session(): void
    {
        /* Arrange */
        $userId = $this->actingAsAdmin();
        
        // Verify user is logged in
        $this->assertNotNull($this->authenticatedUserId);
        
        /* Act */
        // POST /sessions/logout
        $response = $this->post('/sessions/logout');
        
        /* Assert */
        // Verify session was destroyed and redirected to login
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Test password reset page is accessible
     */
    #[Test]
    public function it_displays_passwordreset_form(): void
    {
        /* Arrange */
        $this->clearAuthentication(); // No authentication required for password reset
        
        /* Act */
        // GET /sessions/passwordreset
        $response = $this->get('/sessions/passwordreset');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('email');
        $response->assertSee('btn_reset');
        $this->assertEquals(null, $this->authenticatedUserId);
    }

    /**
     * Happy Path: Password reset request for existing user
     */
    #[Test]
    public function it_post_passwordreset_sends_email_for_valid_user(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        
        /* Act */
        // POST /sessions/passwordreset
        // Valid password reset request: ['btn_reset' => '1', 'email' => 'admin@example.com']
        $response = $this->post('/sessions/passwordreset', [
            'btn_reset' => '1',
            'email' => 'admin@example.com',
        ]);
        
        /* Assert */
        // Verify user exists
        $this->assertDatabaseHas('ip_users', [
            'user_email' => 'admin@example.com',
            'user_active' => 1
        ]);
        // Verify reset token was created
        $this->assertDatabaseHas('ip_password_resets', [
            'user_id' => $this->testData['admin']
        ]);
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Test password reset for non-existent email (prevents enumeration)
     */
    #[Test]
    public function it_post_passwordreset_shows_success_for_nonexistent_email(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        
        /* Act */
        // POST /sessions/passwordreset
        // Non-existent email: ['btn_reset' => '1', 'email' => 'nonexistent@example.com']
        $response = $this->post('/sessions/passwordreset', [
            'btn_reset' => '1',
            'email' => 'nonexistent@example.com',
        ]);
        
        /* Assert */
        // Verify user doesn't exist
        $this->assertDatabaseMissing('ip_users', [
            'user_email' => 'nonexistent@example.com'
        ]);
        // Should still show success message (prevents email enumeration attack)
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Test password reset with invalid email format
     */
    #[Test]
    public function it_validates_passwordreset_email_format(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        
        /* Act */
        // POST /sessions/passwordreset
        // Invalid email format: ['btn_reset' => '1', 'email' => 'not-an-email']
        $response = $this->post('/sessions/passwordreset', [
            'btn_reset' => '1',
            'email' => 'not-an-email',
        ]);
        
        /* Assert */
        $response->assertSessionHasErrors('email');
    }

    /**
     * Test password reset IP rate limiting
     */
    #[Test]
    public function it_post_passwordreset_enforces_ip_rate_limit(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        
        // Simulate 5 reset attempts from same IP
        $ci = &get_instance();
        for ($i = 0; $i < 5; $i++) {
            $ci->db->insert('ip_password_reset_attempts', [
                'ip_address' => '127.0.0.1',
                'attempted_at' => date('Y-m-d H:i:s'),
            ]);
        }
        
        /* Act */
        // POST /sessions/passwordreset
        // 6th attempt from same IP: ['btn_reset' => '1', 'email' => 'admin@example.com']
        $response = $this->post('/sessions/passwordreset', [
            'btn_reset' => '1',
            'email' => 'admin@example.com',
        ]);
        
        /* Assert */
        // Verify rate limit threshold reached
        $attemptCount = $this->getDatabaseCount('ip_password_reset_attempts', [
            'ip_address' => '127.0.0.1'
        ]);
        $this->assertGreaterThanOrEqual(5, $attemptCount);
        $response->assertStatus(429);
    }

    /**
     * Test password reset email rate limiting
     */
    #[Test]
    public function it_post_passwordreset_enforces_email_rate_limit(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        
        // Simulate recent reset request for this email
        $ci = &get_instance();
        $ci->db->insert('ip_password_resets', [
            'user_id' => $this->testData['admin'],
            'reset_token' => bin2hex(random_bytes(32)),
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);
        
        /* Act */
        // POST /sessions/passwordreset
        // Duplicate reset request: ['btn_reset' => '1', 'email' => 'admin@example.com']
        $response = $this->post('/sessions/passwordreset', [
            'btn_reset' => '1',
            'email' => 'admin@example.com',
        ]);
        
        /* Assert */
        // Verify recent reset exists
        $this->assertDatabaseHas('ip_password_resets', [
            'user_id' => $this->testData['admin']
        ]);
        $response->assertRedirect('/sessions/login');
        $response->assertSessionHasErrors();
    }

    /**
     * Test password reset blocks bot requests
     */
    #[Test]
    public function it_post_passwordreset_blocks_bot_user_agents(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        
        // Simulate bot user agent
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; Googlebot/2.1)';
        
        /* Act */
        // POST /sessions/passwordreset
        // Bot request: ['btn_reset' => '1', 'email' => 'admin@example.com']
        $response = $this->post('/sessions/passwordreset', [
            'btn_reset' => '1',
            'email' => 'admin@example.com',
        ]);
        
        /* Assert */
        $response->assertForbidden();
        
        unset($_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * Test password reset token page with valid token
     */
    #[Test]
    public function it_shows_passwordreset_with_valid_token_form(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        
        $validToken = bin2hex(random_bytes(32));
        $ci = &get_instance();
        $ci->db->insert('ip_password_resets', [
            'user_id' => $this->testData['admin'],
            'reset_token' => $validToken,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);
        
        /* Act */
        // GET /sessions/passwordreset/{token}
        $response = $this->get('/sessions/passwordreset/' . $validToken);
        
        /* Assert */
        // Verify valid token shows reset form
        $this->assertDatabaseHas('ip_password_resets', [
            'reset_token' => $validToken
        ]);
        $response->assertOk();
        $response->assertSee('new_password');
        $response->assertSee('confirm_password');
    }

    /**
     * Test password reset with invalid token
     */
    #[Test]
    public function it_get_passwordreset_with_invalid_token_redirects(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        $invalidToken = 'invalid_token_xyz';
        
        /* Act */
        // GET /sessions/passwordreset/{token}
        // Invalid token request
        $response = $this->get('/sessions/passwordreset/' . $invalidToken);
        
        /* Assert */
        // Verify token doesn't exist
        $this->assertDatabaseMissing('ip_password_resets', [
            'reset_token' => $invalidToken
        ]);
        $response->assertRedirect('/sessions/login');
        $response->assertSessionHasErrors();
    }

    /**
     * Test token brute force protection
     */
    #[Test]
    public function it_get_passwordreset_locks_after_10_invalid_token_attempts(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        $invalidToken = 'invalid_token';
        
        // Simulate 10 failed token attempts
        $ci = &get_instance();
        for ($i = 0; $i < 10; $i++) {
            $ci->db->insert('ip_token_attempts', [
                'ip_address' => '127.0.0.1',
                'attempted_at' => date('Y-m-d H:i:s'),
            ]);
        }
        
        /* Act */
        // GET /sessions/passwordreset/{token}
        // 11th invalid token attempt
        $response = $this->get('/sessions/passwordreset/' . $invalidToken);
        
        /* Assert */
        // Verify 10 failed attempts
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
    public function it_post_passwordreset_updates_password_with_valid_token(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        
        $validToken = bin2hex(random_bytes(32));
        $ci = &get_instance();
        $ci->db->insert('ip_password_resets', [
            'user_id' => $this->testData['admin'],
            'reset_token' => $validToken,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);
        
        /* Act */
        // POST /sessions/passwordreset
        // Update password with valid token: ['btn_new_password' => '1', 'token' => $validToken, 'user_id' => ..., 'new_password' => 'NewSecurePassword123!', 'confirm_password' => 'NewSecurePassword123!']
        $response = $this->post('/sessions/passwordreset', [
            'btn_new_password' => '1',
            'token' => $validToken,
            'user_id' => $this->testData['admin'],
            'new_password' => 'NewSecurePassword123!',
            'confirm_password' => 'NewSecurePassword123!',
        ]);
        
        /* Assert */
        // Verify token was deleted after use
        $this->assertDatabaseMissing('ip_password_resets', [
            'reset_token' => $validToken
        ]);
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Test password reset with mismatched token
     */
    #[Test]
    public function it_post_passwordreset_rejects_mismatched_token(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        
        $validToken = bin2hex(random_bytes(32));
        $ci = &get_instance();
        $ci->db->insert('ip_password_resets', [
            'user_id' => $this->testData['admin'],
            'reset_token' => $validToken,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);
        
        /* Act */
        // POST /sessions/passwordreset
        // Wrong token: ['btn_new_password' => '1', 'token' => 'wrong_token_123', 'user_id' => ..., 'new_password' => 'NewPassword123!', 'confirm_password' => 'NewPassword123!']
        $response = $this->post('/sessions/passwordreset', [
            'btn_new_password' => '1',
            'token' => 'wrong_token_123', // Wrong token
            'user_id' => $this->testData['admin'],
            'new_password' => 'NewPassword123!',
            'confirm_password' => 'NewPassword123!',
        ]);
        
        /* Assert */
        // Verify token mismatch
        $this->assertDatabaseMissing('ip_password_resets', [
            'reset_token' => 'wrong_token_123',
            'user_id' => $this->testData['admin']
        ]);
        $response->assertSessionHasErrors();
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Test password reset validates non-alphanumeric token
     */
    #[Test]
    public function it_get_passwordreset_validates_token_format(): void
    {
        /* Arrange */
        $this->clearAuthentication();
        $maliciousToken = '../../../etc/passwd';
        
        /* Act */
        // GET /sessions/passwordreset/{token}
        // Path traversal attempt in token
        $response = $this->get('/sessions/passwordreset/' . urlencode($maliciousToken));
        
        /* Assert */
        // Verify malicious token doesn't exist
        $this->assertDatabaseMissing('ip_password_resets', [
            'reset_token' => $maliciousToken
        ]);
        $response->assertRedirect('/sessions/login');
        $response->assertSessionHasErrors();
    }
}
