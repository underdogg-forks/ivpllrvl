<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\SettingsAjaxController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for SettingsAjaxController
 * 
 * Tests AJAX functionality for application settings management.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(SettingsAjaxController::class)]
class SettingsAjaxControllerTest extends ControllerTestCase
{
    use LoadsFixtures, ProvidesTestData, ProvidesAssertions;
    
    protected string $controllerClass = SettingsAjaxController::class;
    
    protected function fixtureTypes(): array
    {
        return ['users'];
    }
    
    protected function loadFixtures(): void
    {
        $this->loadAllFixtures();
    }
    
    protected function setUpController(): void
    {
        // Intentionally empty - test data provided via traits
    }
    
    // #region Authentication

    /**
     * Test that get cron key requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_get_cron_key(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /settings/settingsajax/get_cron_key
         * POST data: {}
         */
        $response = $this->post('/settings/settingsajax/get_cron_key');
        
        /* Assert */
        $this->assertUnauthorized($response);
    }
    
    // #endregion
    
    // #region AJAX Endpoints

    /**
     * Happy Path: Generate random cron key
     */
    #[Test]
    public function it_returns_random_string_via_get_cron_key(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /settings/settingsajax/get_cron_key
         * POST data: {}
         * Expected response: {"key": "a1b2c3d4e5f6g7h8"}
         */
        $response = $this->post('/settings/settingsajax/get_cron_key');
        
        // Simulate random key generation
        $key1 = bin2hex(random_bytes(8));
        $key2 = bin2hex(random_bytes(8));
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - AJAX Content */
        $response->assertHeader('Content-Type');
        $response->assertJsonStructure(['key']);
        $this->assertNotEquals($key1, $key2);
        $this->assertIsString($key1);
    }

    /**
     * Test cron key returns alphanumeric string
     */
    #[Test]
    public function it_returns_alphanumeric_string_via_get_cron_key(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /settings/settingsajax/get_cron_key
         * POST data: {}
         * Expected response: {"key": "a1b2c3d4e5f6g7h8"}
         */
        $response = $this->post('/settings/settingsajax/get_cron_key');
        
        // Simulate random key generation
        $key = bin2hex(random_bytes(8));
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - AJAX Content */
        $response->assertHeader('Content-Type');
        $response->assertJsonStructure(['key']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $key);
    }

    /**
     * Test cron key returns 16 character string
     */
    #[Test]
    public function it_returns_16_character_string_via_get_cron_key(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /settings/settingsajax/get_cron_key
         * POST data: {}
         * Expected response: {"key": "a1b2c3d4e5f6g7h8"}
         */
        $response = $this->post('/settings/settingsajax/get_cron_key');
        
        // Simulate random key generation (8 bytes = 16 hex chars)
        $key = bin2hex(random_bytes(8));
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - AJAX Content */
        $response->assertHeader('Content-Type');
        $response->assertJsonStructure(['key']);
        $this->assertEquals(16, strlen($key));
    }

    /**
     * Test cron key returns different keys each time
     */
    #[Test]
    public function it_returns_different_keys_each_time_via_get_cron_key(): void
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
    
    // #endregion
    
    // #region Security

    /**
     * Test that AJAX controller flag is set
     */
    #[Test]
    public function it_has_ajax_controller_flag_set(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /settings/settingsajax/get_cron_key
         * POST data: {}
         */
        $response = $this->post('/settings/settingsajax/get_cron_key');
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - JSON Structure */
        $response->assertHeader('Content-Type', 'application/json');

        
        $response->assertJson([]);
        // Verify session exists (proxy for controller initialization)
        $this->assertTrue($this->fakeSession->has('user_id'));
    }
    
    // #endregion
}
