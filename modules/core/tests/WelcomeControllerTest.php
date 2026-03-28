<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\WelcomeController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for WelcomeController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(WelcomeController::class)]
class WelcomeControllerTest extends ControllerTestCase
{
    protected string $controllerClass = WelcomeController::class;
    
    protected function loadFixtures(): void
    {
        // Load application settings
        $this->fakeDb->insert('ip_settings', [
            'setting_id' => 1,
            'setting_key' => 'version',
            'setting_value' => '1.6.0',
        ]);
        
        $this->fakeDb->insert('ip_settings', [
            'setting_id' => 2,
            'setting_key' => 'company_name',
            'setting_value' => 'InvoicePlane',
        ]);
        
        $this->fakeDb->insert('ip_settings', [
            'setting_id' => 3,
            'setting_key' => 'default_language',
            'setting_value' => 'en',
        ]);
        
        $this->fakeDb->insert('ip_settings', [
            'setting_id' => 4,
            'setting_key' => 'default_currency',
            'setting_value' => 'USD',
        ]);
    }
    
    protected function setUpController(): void
    {
        // Store common settings data for reuse
        $this->testData = [
            'version' => '1.6.0',
            'company_name' => 'InvoicePlane',
            'default_language' => 'en',
        ];
    }

    /**
     * Happy Path: Welcome page displays without authentication
     */
    #[Test]
    public function it_displays_welcome_index_welcome_page(): void
    {
        /* Arrange */
        // No authentication required for welcome page
        $this->clearAuth();
        
        /* Act */
        // When CI bootstrap is ready, this will call the controller
        $response = $this->get('/route/index');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('InvoicePlane');
        $response->assertSee('welcome');
        
        // Verify settings are loaded
        $settings = $this->fakeDb->select('ip_settings');
        $this->assertCount(4, $settings);
    }

    /**
     * Test that settings model is loaded
     */
    #[Test]
    public function it_displays_welcome_index_loads_settings_model(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        $response = $this->get('/import');
        
        /* Assert */
        $this->assertModelLoaded('settings/mdl_settings');
        
        // Verify settings are available in fake database
        $settings = $this->fakeDb->select('ip_settings');
        $this->assertNotEmpty($settings);
    }

    /**
     * Test that settings helper is loaded
     */
    #[Test]
    public function it_displays_welcome_index_loads_settings_helper(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        $response = $this->get('/import');
        
        /* Assert */
        $this->assertHelperLoaded('settings');
        $this->assertHelperLoaded('echo');
        $this->assertHelperLoaded('url');
    }

    /**
     * Test that welcome page does not require authentication
     */
    #[Test]
    public function it_displays_welcome_index_does_not_require_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        $response = $this->get('/route/index');
        
        /* Assert */
        $response->assertOk();
        $this->assertNotRedirected();
        
        // Verify no session data is required
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Test welcome page displays application information
     */
    #[Test]
    public function it_welcome_page_displays_application_information(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        $response = $this->get('/route/index');
        
        /* Assert */
        $response->assertSee('InvoicePlane');
        $response->assertSee('1.6.0');
        
        // Verify application settings
        $versionSetting = $this->fakeDb->select('ip_settings', ['setting_key' => 'version']);
        $this->assertCount(1, $versionSetting);
        $this->assertEquals('1.6.0', $versionSetting[0]['setting_value']);
        
        $companyNameSetting = $this->fakeDb->select('ip_settings', ['setting_key' => 'company_name']);
        $this->assertCount(1, $companyNameSetting);
        $this->assertEquals('InvoicePlane', $companyNameSetting[0]['setting_value']);
    }
    
    /**
     * Test welcome page with multiple languages setting
     */
    #[Test]
    public function it_welcome_page_respects_default_language_setting(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        // Update default language setting
        $this->fakeDb->update('ip_settings', 
            ['setting_value' => 'de'],
            ['setting_key' => 'default_language']
        );
        
        /* Act */
        $response = $this->get('/route/index');
        
        /* Assert */
        $response->assertSee('lang="de"');
        
        // Verify language setting was updated
        $languageSetting = $this->fakeDb->select('ip_settings', ['setting_key' => 'default_language']);
        $this->assertEquals('de', $languageSetting[0]['setting_value']);
    }
    
    /**
     * Test welcome page with custom company name
     */
    #[Test]
    public function it_welcome_page_displays_custom_company_name(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        // Update company name setting
        $customCompanyName = 'My Custom Company';
        $this->fakeDb->update('ip_settings', 
            ['setting_value' => $customCompanyName],
            ['setting_key' => 'company_name']
        );
        
        /* Act */
        $response = $this->get('/route/index');
        
        /* Assert */
        $response->assertSee($customCompanyName);
        
        // Verify company name setting was updated
        $companyNameSetting = $this->fakeDb->select('ip_settings', ['setting_key' => 'company_name']);
        $this->assertEquals($customCompanyName, $companyNameSetting[0]['setting_value']);
    }
    
    /**
     * Test welcome page is accessible when authenticated
     */
    #[Test]
    public function it_welcome_page_is_accessible_when_authenticated(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /* Act */
        $response = $this->get('/route/index');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('InvoicePlane');
        
        // Verify admin is authenticated
        $this->assertTrue($this->fakeSession->has('user_id'));
        $this->assertEquals(1, $this->fakeSession->get('user_type'));
    }
}
