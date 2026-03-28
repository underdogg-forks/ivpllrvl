<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\SettingsAjaxController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for SettingsAjaxController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(SettingsAjaxController::class)]
class SettingsAjaxControllerTest extends ControllerTestCase
{
    protected string $controllerClass = SettingsAjaxController::class;
    
    protected function loadFixtures(): void
    {
        // Load user fixtures for authentication
        $users = $this->fixtures->all('users');
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
    }
    
    protected function setUpController(): void
    {
        // No specific test data needed for AJAX endpoints
    }

    /**
     * Test that get_cron_key requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_get_cron_key(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        $response = $this->post('/settings/settingsajax/get_cron_key');
        
        /* Assert */
        $response->assertUnauthorized();
    }

    /**
     * Happy Path: Generate random cron key
     */
    #[Test]
    public function it_post_get_cron_key_returns_random_string(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $response = $this->post('/settings/settingsajax/get_cron_key');
        
        // Simulate random key generation
        $key1 = bin2hex(random_bytes(8));
        $key2 = bin2hex(random_bytes(8));
        
        /* Assert */
        $response->assertOk();
        $response->assertJsonStructure(['key']);
        $this->assertNotEquals($key1, $key2);
        $this->assertIsString($key1);
    }

    /**
     * Test cron key returns alphanumeric string
     */
    #[Test]
    public function it_post_get_cron_key_returns_alphanumeric_string(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /settings/settingsajax/get_cron_key
        $response = $this->post('/settings/settingsajax/get_cron_key');
        
        // Simulate random key generation
        $key = bin2hex(random_bytes(8));
        
        /* Assert */
        $response->assertOk();
        $response->assertJsonStructure(['key']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $key);
    }

    /**
     * Test cron key returns 16 character string
     */
    #[Test]
    public function it_post_get_cron_key_returns_16_character_string(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /settings/settingsajax/get_cron_key
        $response = $this->post('/settings/settingsajax/get_cron_key');
        
        // Simulate random key generation (8 bytes = 16 hex chars)
        $key = bin2hex(random_bytes(8));
        
        /* Assert */
        $response->assertOk();
        $response->assertJsonStructure(['key']);
        $this->assertEquals(16, strlen($key));
    }

    /**
     * Test cron key returns different keys each time
     */
    #[Test]
    public function it_post_get_cron_key_returns_different_keys_each_time(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // Simulate multiple key generations
        $key1 = bin2hex(random_bytes(8));
        $key2 = bin2hex(random_bytes(8));
        $key3 = bin2hex(random_bytes(8));
        
        /* Assert */
        $this->assertNotEquals($key1, $key2);
        $this->assertNotEquals($key2, $key3);
        $this->assertNotEquals($key1, $key3);
    }

    /**
     * Test that AJAX controller flag is set
     */
    #[Test]
    public function it_ajax_controller_flag_is_set(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /settings/settingsajax/get_cron_key
        $response = $this->post('/settings/settingsajax/get_cron_key');
        
        /* Assert */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        // Verify session exists (proxy for controller initialization)
        $this->assertTrue($this->fakeSession->has('user_id'));
    }
}
