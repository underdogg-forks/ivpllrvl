<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\WelcomeController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for WelcomeController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(WelcomeController::class)]
class WelcomeControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = WelcomeController::class;
    
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
    
    /**
     * Set up controller-specific test data
     */
    protected function setUpController(): void
    {
        $this->testData = [
            'version' => '1.6.0',
            'company_name' => 'InvoicePlane',
            'default_language' => 'en',
        ];
    }

    // #region Display & Operations Tests

    /**
     * Happy Path: Welcome page displays without authentication
     */
    #[Test]
    public function it_displays_welcome_page_without_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /route/index
         * Expected behavior: Display welcome page without requiring authentication
         */
        $response = $this->get('/route/index');
        
        /* Assert */
        $response->assertOk();
        $this->assertResponseContainsAll($response, ['InvoicePlane', 'welcome']);
        $records = $this->fakeDb->select('ip_settings', []);
        $this->assertCount(4, $records, "Database should have exactly 4 record(s) in 'ip_settings'");
    }

    /**
     * Test that settings model is loaded
     */
    #[Test]
    public function it_loads_settings_model(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /import
         * Expected behavior: Load settings model
         */
        $response = $this->get('/import');
        
        /* Assert */
        $this->assertModelLoaded('settings/mdl_settings');
        
        $settings = $this->fakeDb->select('ip_settings');
        $this->assertNotEmpty($settings);
    }

    /**
     * Test that settings helper is loaded
     */
    #[Test]
    public function it_loads_required_helpers(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /import
         * Expected behavior: Load settings, echo, and url helpers
         */
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
    public function it_does_not_require_authentication_for_welcome_page(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /route/index
         * Expected behavior: Display page without authentication
         */
        $response = $this->get('/route/index');
        
        /* Assert */
        $response->assertOk();
        $this->assertNotRedirected();
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Test welcome page displays application information
     */
    #[Test]
    public function it_displays_application_information(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /route/index
         * Expected behavior: Display InvoicePlane name and version
         */
        $response = $this->get('/route/index');
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['InvoicePlane', '1.6.0']);
        
        $versionSetting = $this->fakeDb->select('ip_settings', ['setting_key' => 'version']);
        $this->assertCount(1, $versionSetting);
        $this->assertEquals('1.6.0', $versionSetting[0]['setting_value']);
        
        $companyNameSetting = $this->fakeDb->select('ip_settings', ['setting_key' => 'company_name']);
        $this->assertCount(1, $companyNameSetting);
        $this->assertEquals('InvoicePlane', $companyNameSetting[0]['setting_value']);
    }
    
    // #endregion
    
    // #region Configuration Tests
    
    /**
     * Test welcome page respects default language setting
     */
    #[Test]
    public function it_respects_default_language_setting(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        $this->fakeDb->update('ip_settings', 
            ['setting_value' => 'de'],
            ['setting_key' => 'default_language']
        );
        
        /**
         * Act: GET /route/index
         * Expected behavior: Display page with German language setting
         */
        $response = $this->get('/route/index');
        
        /* Assert */
        $response->assertSee('lang="de"');
        
        $languageSetting = $this->fakeDb->select('ip_settings', ['setting_key' => 'default_language']);
        $this->assertEquals('de', $languageSetting[0]['setting_value']);
    }
    
    /**
     * Test welcome page displays custom company name
     */
    #[Test]
    public function it_displays_custom_company_name(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        $customCompanyName = 'My Custom Company';
        $this->fakeDb->update('ip_settings', 
            ['setting_value' => $customCompanyName],
            ['setting_key' => 'company_name']
        );
        
        /**
         * Act: GET /route/index
         * Expected behavior: Display custom company name
         */
        $response = $this->get('/route/index');
        
        /* Assert */
        $response->assertSee($customCompanyName);
        
        $companyNameSetting = $this->fakeDb->select('ip_settings', ['setting_key' => 'company_name']);
        $this->assertEquals($customCompanyName, $companyNameSetting[0]['setting_value']);
    }
    
    /**
     * Test welcome page is accessible when authenticated
     */
    #[Test]
    public function it_is_accessible_when_authenticated(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /route/index
         * Expected behavior: Display welcome page for authenticated users
         */
        $response = $this->get('/route/index');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('InvoicePlane');
        $this->assertTrue($this->fakeSession->has('user_id'));
        $this->assertEquals(1, $this->fakeSession->get('user_type'));
    }
    
    // #endregion
}
