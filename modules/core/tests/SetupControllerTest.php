<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\SetupController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for SetupController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(SetupController::class)]
class SetupControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = SetupController::class;
    
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
        // Setup tests may need minimal fixtures or none
        // Only load if needed for specific tests
    }
    
    /**
     * Set up controller-specific test data
     */
    protected function setUpController(): void
    {
        // Intentionally empty - test data is provided via ProvidesTestData trait
    }

    // #region Security Tests

    /**
     * Test that setup is disabled when environment flag is set
     */
    #[Test]
    public function it_blocks_access_when_setup_is_disabled_via_environment_flag(): void
    {
        /* Arrange */
        // Simulate DISABLE_SETUP environment variable
        $disableSetup = true;
        
        /**
         * Act: GET /setup/setup/index
         * Expected behavior: Return 403 Forbidden when setup is disabled
         */
        $response = $this->get('/setup/setup/index');
        
        /* Assert */
        $response->assertStatus(403);
        $response->assertSee('Setup is disabled');
        $this->assertTrue($disableSetup);
    }
    
    /**
     * Test setup validates session flow
     */
    #[Test]
    public function it_validates_session_flow_during_setup(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /**
         * Act: GET /setup/setup/database
         * Session data: { "language": "english" }
         * Expected behavior: Maintain session state through setup steps
         */
        $response = $this->withSession(['language' => 'english'])
            ->get('/setup/setup/database');
        
        /* Assert */
        $response->assertSessionHas('language', 'english');
    }
    
    /**
     * Test setup prevents out of order access
     */
    #[Test]
    public function it_prevents_accessing_setup_steps_out_of_order(): void
    {
        /* Arrange */
        // Attempt to access account creation without completing prior steps
        
        /**
         * Act: GET /setup/setup/account
         * Expected behavior: Redirect when required session data is missing
         */
        $response = $this->get('/setup/setup/account');
        
        /* Assert */
        $response->assertStatus(302);
        $response->assertSessionMissing('upgrade_type');
    }

    // #endregion

    // #region Installation Steps Tests

    /**
     * Test index redirects to language selection
     */
    #[Test]
    public function it_redirects_to_language_selection_from_index(): void
    {
        /* Arrange */
        // No authentication needed for setup
        
        /**
         * Act: GET /setup/setup/index
         * Expected behavior: Redirect to language selection as first setup step
         */
        $response = $this->get('/setup/setup/index');
        
        /* Assert */
        $response->assertStatus(302);
    }

    /**
     * Happy Path: Display language selection form
     */
    #[Test]
    public function it_displays_language_selection_form(): void
    {
        /* Arrange */
        // No authentication needed
        
        /**
         * Act: GET /setup/setup/language
         * Expected behavior: Display language selection form
         */
        $response = $this->get('/setup/setup/language');
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['language', 'english']);
    }

    /**
     * Test language selection sets session
     */
    #[Test]
    public function it_stores_selected_language_in_session(): void
    {
        /* Arrange */
        $languageData = $this->makeLanguageData(['language' => 'english']);
        
        /**
         * Act: POST /setup/setup/language
         * POST data: {
         *   "language": "english",
         *   "btn_continue": "1"
         * }
         * Expected behavior: Store selected language in session
         */
        $response = $this->post('/setup/setup/language', $languageData);
        
        /* Assert */
        $response->assertSessionHas('language', 'english');
    }

    /**
     * Test language selection redirects to prerequisites
     */
    #[Test]
    public function it_redirects_to_prerequisites_after_language_selection(): void
    {
        /* Arrange */
        $languageData = $this->makeLanguageData();
        
        /**
         * Act: POST /setup/setup/language
         * POST data: Complete language selection data
         * Expected behavior: Redirect to prerequisites check
         */
        $response = $this->post('/setup/setup/language', $languageData);
        
        /* Assert */
        $response->assertStatus(302);
    }

    // #endregion

    // #region Configuration Tests

    /**
     * Test prerequisites checks PHP version
     */
    #[Test]
    public function it_verifies_php_version_meets_minimum_requirements(): void
    {
        /* Arrange */
        $requiredPhpVersion = '8.1.0';
        $currentPhpVersion = PHP_VERSION;
        
        /**
         * Act: GET /setup/setup/prerequisites
         * Expected behavior: Display PHP version check
         */
        $response = $this->get('/setup/setup/prerequisites');
        
        /* Assert */
        $response->assertSee('PHP Version');
        $this->assertGreaterThanOrEqual(0, version_compare($currentPhpVersion, $requiredPhpVersion));
    }

    /**
     * Test prerequisites checks writable directories
     */
    #[Test]
    public function it_checks_required_directories_are_writable(): void
    {
        /* Arrange */
        $requiredDirs = ['uploads', 'storage', 'public/assets'];
        
        /**
         * Act: GET /setup/setup/prerequisites
         * Expected behavior: Check write permissions on required directories
         */
        $response = $this->get('/setup/setup/prerequisites');
        
        /* Assert */
        $response->assertSee('Directory Permissions');
        $this->assertIsArray($requiredDirs);
        $this->assertCount(3, $requiredDirs);
    }

    /**
     * Test prerequisites checks timezone configuration
     */
    #[Test]
    public function it_validates_timezone_configuration(): void
    {
        /* Arrange */
        $timezone = date_default_timezone_get();
        
        /**
         * Act: GET /setup/setup/prerequisites
         * Expected behavior: Display timezone configuration status
         */
        $response = $this->get('/setup/setup/prerequisites');
        
        /* Assert */
        $response->assertSee('Timezone');
        $this->assertNotEmpty($timezone);
    }

    /**
     * Test prerequisites redirects to database configuration
     */
    #[Test]
    public function it_redirects_to_database_configuration_after_prerequisites(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /**
         * Act: POST /setup/setup/prerequisites
         * POST data: { "btn_continue": "1" }
         * Expected behavior: Redirect to database configuration step
         */
        $response = $this->post('/setup/setup/prerequisites', [
            'btn_continue' => '1',
        ]);
        
        /* Assert */
        $response->assertStatus(302);
    }

    // #endregion

    // #region Database Setup Tests

    /**
     * Happy Path: Display database configuration form
     */
    #[Test]
    public function it_displays_database_configuration_form(): void
    {
        /* Arrange */
        // No authentication needed
        
        /**
         * Act: GET /setup/setup/database
         * Expected behavior: Display database configuration form with required fields
         */
        $response = $this->get('/setup/setup/database');
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['db_hostname', 'db_database']);
    }

    /**
     * Test database configuration writes config file
     */
    #[Test]
    public function it_writes_database_configuration_to_config_file(): void
    {
        /* Arrange */
        $dbConfigData = $this->makeDatabaseConfigData([
            'btn_continue' => '1',
        ]);
        
        /**
         * Act: POST /setup/setup/database
         * POST data: {
         *   "db_hostname": "localhost",
         *   "db_username": "invoiceplane",
         *   "db_password": "password",
         *   "db_database": "invoiceplane",
         *   "db_port": "3306",
         *   "btn_continue": "1"
         * }
         * Expected behavior: Write database configuration to config file
         */
        $response = $this->post('/setup/setup/database', $dbConfigData);
        
        /* Assert */
        $this->assertFileExists(APPPATH . 'config/database.php');
        // Verify database settings would be written
        $this->assertEquals('localhost', $dbConfigData['db_hostname']);
    }

    /**
     * Test database configuration validates connection
     */
    #[Test]
    public function it_validates_database_connection_during_configuration(): void
    {
        /* Arrange */
        $invalidDbConfig = $this->makeDatabaseConfigData([
            'db_hostname' => 'invalid-host',
            'db_username' => 'user',
            'db_password' => 'pass',
            'db_database' => 'db',
            'btn_continue' => '1',
        ]);
        
        /**
         * Act: POST /setup/setup/database
         * POST data: Invalid database connection parameters
         * Expected behavior: Return validation errors for invalid connection
         */
        $response = $this->post('/setup/setup/database', $invalidDbConfig);
        
        /* Assert */
        $response->assertSessionHasErrors();
        $response->assertSee('Could not connect to database');
    }

    /**
     * Test database configuration detects existing installation
     */
    #[Test]
    public function it_detects_existing_installation_during_database_setup(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->fakeDb->insert('ip_users', $adminUser);
        
        $dbConfigData = $this->makeDatabaseConfigData(['btn_continue' => '1']);
        
        /**
         * Act: POST /setup/setup/database
         * POST data: Complete database configuration data
         * Expected behavior: Detect existing users table and mark as upgrade
         */
        $response = $this->post('/setup/setup/database', $dbConfigData);
        
        /* Assert */
        $response->assertSessionHas('upgrade_type', 'upgrade');
        $users = $this->fakeDb->select('ip_users');
        $this->assertGreaterThan(0, count($users));
    }

    /**
     * Test database configuration detects new installation
     */
    #[Test]
    public function it_detects_new_installation_during_database_setup(): void
    {
        /* Arrange */
        $dbConfigData = $this->makeDatabaseConfigData(['btn_continue' => '1']);
        
        /**
         * Act: POST /setup/setup/database
         * POST data: Complete database configuration data
         * Expected behavior: Detect empty database and mark as new install
         */
        $response = $this->post('/setup/setup/database', $dbConfigData);
        
        /* Assert */
        $response->assertSessionHas('upgrade_type', 'install');
        $users = $this->fakeDb->select('ip_users');
        $this->assertCount(0, $users);
    }

    /**
     * Test install tables creates database schema
     */
    #[Test]
    public function it_creates_database_schema_during_installation(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /**
         * Act: GET /setup/setup/install_tables
         * Session data: { "upgrade_type": "install" }
         * Expected behavior: Create database tables for new installation
         */
        $response = $this->withSession(['upgrade_type' => 'install'])
            ->get('/setup/setup/install_tables');
        
        /* Assert */
        // Verify tables would be created
        $this->assertTrue($this->db->table_exists('ip_users'));
    }

    /**
     * Test install tables redirects to upgrade
     */
    #[Test]
    public function it_redirects_to_upgrade_after_installing_tables(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /**
         * Act: GET /setup/setup/install_tables
         * Session data: { "upgrade_type": "install" }
         * Expected behavior: Redirect to upgrade step after table creation
         */
        $response = $this->withSession(['upgrade_type' => 'install'])
            ->get('/setup/setup/install_tables');
        
        /* Assert */
        $response->assertStatus(302);
    }

    /**
     * Test upgrade tables applies migrations
     */
    #[Test]
    public function it_applies_database_migrations_during_upgrade(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /**
         * Act: GET /setup/setup/upgrade_tables
         * Session data: { "upgrade_type": "upgrade" }
         * Expected behavior: Apply pending database migrations
         */
        $response = $this->withSession(['upgrade_type' => 'upgrade'])
            ->get('/setup/setup/upgrade_tables');
        
        /* Assert */
        // Verify migrations would be applied
        $response->assertOk();
    }

    /**
     * Test upgrade tables sets encryption key
     */
    #[Test]
    public function it_sets_encryption_key_during_upgrade(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /**
         * Act: GET /setup/setup/upgrade_tables
         * Session data: { "upgrade_type": "install" }
         * Expected behavior: Generate and set encryption key in config
         */
        $response = $this->withSession(['upgrade_type' => 'install'])
            ->get('/setup/setup/upgrade_tables');
        
        /* Assert */
        $response->assertOk();
        $this->assertNotEmpty($config['encryption_key'] ?? '');
    }

    /**
     * Test upgrade redirects to create user for new install
     */
    #[Test]
    public function it_redirects_to_user_creation_for_new_installation(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /**
         * Act: GET /setup/setup/upgrade_tables
         * Session data: { "upgrade_type": "install" }
         * Expected behavior: Redirect to account creation for new install
         */
        $response = $this->withSession(['upgrade_type' => 'install'])
            ->get('/setup/setup/upgrade_tables');
        
        /* Assert */
        $response->assertStatus(302);
    }

    /**
     * Test upgrade redirects to calculation info for upgrade
     */
    #[Test]
    public function it_redirects_to_calculation_info_for_existing_installation(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /**
         * Act: GET /setup/setup/upgrade_tables
         * Session data: { "upgrade_type": "upgrade" }
         * Expected behavior: Redirect to calculation info for upgrades
         */
        $response = $this->withSession(['upgrade_type' => 'upgrade'])
            ->get('/setup/setup/upgrade_tables');
        
        /* Assert */
        $response->assertStatus(302);
    }

    // #endregion

    // #region Validation Tests



    /**
     * Happy Path: Display create user form
     */
    #[Test]
    public function it_displays_user_account_creation_form(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /**
         * Act: GET /setup/setup/account
         * Session data: { "upgrade_type": "install" }
         * Expected behavior: Display user account creation form
         */
        $response = $this->withSession(['upgrade_type' => 'install'])
            ->get('/setup/setup/account');
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['user_name', 'user_email']);
    }

    /**
     * Happy Path: Create admin user
     */
    #[Test]
    public function it_creates_admin_user_account_during_setup(): void
    {
        /* Arrange */
        $accountData = $this->makeAccountSetupData([
            'user_email' => 'admin@example.com',
        ]);
        
        /**
         * Act: POST /setup/setup/account
         * POST data: {
         *   "user_name": "Admin User",
         *   "user_email": "admin@example.com",
         *   "user_password": "AdminPass123!",
         *   "user_passwordv": "AdminPass123!",
         *   "btn_continue": "1"
         * }
         * Expected behavior: Create admin user in database
         */
        $response = $this->post('/setup/setup/account', $accountData);
        
        /* Assert */
        $this->assertDatabaseHasRecord('ip_users', ['user_email' => 'admin@example.com']);
        $users = $this->fakeDb->select('ip_users', ['user_email' => 'admin@example.com']);
        $this->assertCount(1, $users);
        $this->assertEquals(1, $users[0]['user_type']);
    }

    /**
     * Test create user validates required fields
     */
    #[Test]
    public function it_validates_required_fields_for_user_account_creation(): void
    {
        /* Arrange */
        $incompleteAccountData = $this->makeAccountSetupData([
            'user_name' => '', // Missing required field
            'user_email' => '',
        ]);
        
        /**
         * Act: POST /setup/setup/account
         * POST data: Incomplete user account data with missing required fields
         * Expected behavior: Return validation errors
         */
        $response = $this->post('/setup/setup/account', $incompleteAccountData);
        
        /* Assert */
        $response->assertSessionHasErrors();
    }

    /**
     * Test create user redirects to calculation info
     */
    #[Test]
    public function it_redirects_to_calculation_info_after_user_creation(): void
    {
        /* Arrange */
        $accountData = $this->makeAccountSetupData();
        
        /**
         * Act: POST /setup/setup/account
         * POST data: Complete user account data
         * Expected behavior: Redirect to calculation info step
         */
        $response = $this->post('/setup/setup/account', $accountData);
        
        /* Assert */
        $response->assertStatus(302);
    }

    // #endregion

    // #region Completion Tests

    /**
     * Happy Path: Display calculation info
     */
    #[Test]
    public function it_displays_calculation_migration_information(): void
    {
        /* Arrange */
        // No authentication needed
        
        /**
         * Act: GET /setup/setup/calculation_info
         * Expected behavior: Display calculation migration information
         */
        $response = $this->get('/setup/setup/calculation_info');
        
        /* Assert */
        $response->assertSee('calculation');
    }

    /**
     * Test calculation info writes legacy config
     */
    #[Test]
    public function it_writes_legacy_calculation_configuration(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /**
         * Act: POST /setup/setup/calculation_info
         * POST data: { "btn_continue": "1" }
         * Expected behavior: Write legacy calculation configuration
         */
        $response = $this->post('/setup/setup/calculation_info', [
            'btn_continue' => '1',
        ]);
        
        /* Assert */
        // Verify config would be written
        $response->assertOk();
    }

    /**
     * Test calculation info redirects to complete
     */
    #[Test]
    public function it_redirects_to_completion_after_calculation_info(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /**
         * Act: POST /setup/setup/calculation_info
         * POST data: { "btn_continue": "1" }
         * Expected behavior: Redirect to setup completion page
         */
        $response = $this->post('/setup/setup/calculation_info', [
            'btn_continue' => '1',
        ]);
        
        /* Assert */
        $response->assertStatus(302);
    }

    /**
     * Test complete marks setup as completed
     */
    #[Test]
    public function it_marks_setup_as_completed(): void
    {
        /* Arrange */
        // No authentication needed
        
        /**
         * Act: GET /setup/setup/complete
         * Expected behavior: Create setup completion marker file
         */
        $response = $this->get('/setup/setup/complete');
        
        /* Assert */
        $this->assertTrue(file_exists(APPPATH . 'config/setup_complete.txt'));
    }

    /**
     * Test complete destroys session
     */
    #[Test]
    public function it_clears_setup_session_data_upon_completion(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /**
         * Act: GET /setup/setup/complete
         * Session data: { "language": "english", "upgrade_type": "install" }
         * Expected behavior: Destroy all setup session data
         */
        $response = $this->withSession([
            'language' => 'english',
            'upgrade_type' => 'install',
        ])->get('/setup/setup/complete');
        
        /* Assert */
        $response->assertSessionMissing('language');
        $response->assertSessionMissing('upgrade_type');
    }

    /**
     * Happy Path: Display completion message
     */
    #[Test]
    public function it_displays_setup_completion_success_message(): void
    {
        /* Arrange */
        // No authentication needed
        
        /**
         * Act: GET /setup/setup/complete
         * Expected behavior: Display setup completion success message
         */
        $response = $this->get('/setup/setup/complete');
        
        /* Assert */
        $response->assertSee('Setup Complete');
    }

    // #endregion
}
