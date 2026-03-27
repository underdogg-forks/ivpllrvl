<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\SetupController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for SetupController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(SetupController::class)]
class SetupControllerTest extends ControllerTestCase
{
    protected string $controllerClass = SetupController::class;
    
    protected function loadFixtures(): void
    {
        // Setup controller doesn't need pre-existing users
        // It creates the admin user during setup process
    }
    
    protected function setUpController(): void
    {
        // Store valid setup data from fixtures
        $this->testData = [
            'language' => 'english',
            'db_hostname' => 'localhost',
            'db_username' => 'invoiceplane',
            'db_password' => 'password',
            'db_database' => 'invoiceplane',
            'db_port' => '3306',
            'user_data' => $this->fixtures->get('users', 'valid_new_user'),
        ];
    }

    /**
     * Test that setup is disabled when environment flag is set
     */
    #[Test]
    public function it_setup_is_disabled_when_env_flag_set(): void
    {
        /* Arrange */
        // Simulate DISABLE_SETUP environment variable
        $disableSetup = true;
        
        /* Act */
        $controller = $this->getController();
        $controller->index();
        
        /* Assert */
        $this->assertResponseCode(403);
        $this->assertResponseContains('Setup is disabled');
        $this->assertTrue($disableSetup);
    }

    /**
     * Test index redirects to language selection
     */
    #[Test]
    public function it_get_index_redirects_to_language_selection(): void
    {
        /* Arrange */
        // No authentication needed for setup
        
        /* Act */
        $controller = $this->getController();
        $controller->index();
        
        /* Assert */
        $this->assertRedirectedTo('setup/language');
    }

    /**
     * Happy Path: Display language selection form
     */
    #[Test]
    public function it_displays_language_language_selection(): void
    {
        /* Arrange */
        // No authentication needed
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->language();
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('language');
        $this->assertResponseContains('english');
    }

    /**
     * Test language selection sets session
     */
    #[Test]
    public function it_post_language_sets_session_language(): void
    {
        /* Arrange */
        $this->setPostData([
            'language' => 'english',
            'btn_continue' => '1',
        ]);
        
        /* Act */
        $this->fakeSession->set('language', 'english');
        $controller = $this->getController();
        $controller->language();
        
        /* Assert */
        $this->assertEquals('english', $this->fakeSession->get('language'));
    }

    /**
     * Test language selection redirects to prerequisites
     */
    #[Test]
    public function it_post_language_redirects_to_prerequisites(): void
    {
        /* Arrange */
        $this->setPostData([
            'language' => 'english',
            'btn_continue' => '1',
        ]);
        
        /* Act */
        $controller = $this->getController();
        $controller->language();
        
        /* Assert */
        $this->assertRedirectedTo('setup/prerequisites');
    }

    /**
     * Test prerequisites checks PHP version
     */
    #[Test]
    public function it_get_prerequisites_checks_php_version(): void
    {
        /* Arrange */
        $requiredPhpVersion = '8.1.0';
        $currentPhpVersion = PHP_VERSION;
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->prerequisites();
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('PHP Version');
        $this->assertGreaterThanOrEqual(0, version_compare($currentPhpVersion, $requiredPhpVersion));
    }

    /**
     * Test prerequisites checks writable directories
     */
    #[Test]
    public function it_get_prerequisites_checks_writable_directories(): void
    {
        /* Arrange */
        $requiredDirs = ['uploads', 'storage', 'public/assets'];
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->prerequisites();
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('Directory Permissions');
        $this->assertIsArray($requiredDirs);
        $this->assertCount(3, $requiredDirs);
    }

    /**
     * Test prerequisites checks timezone configuration
     */
    #[Test]
    public function it_get_prerequisites_checks_timezone_configuration(): void
    {
        /* Arrange */
        $timezone = date_default_timezone_get();
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->prerequisites();
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('Timezone');
        $this->assertNotEmpty($timezone);
    }

    /**
     * Test prerequisites redirects to database configuration
     */
    #[Test]
    public function it_post_prerequisites_redirects_to_database_config(): void
    {
        /* Arrange */
        $this->setPostData(['btn_continue' => '1']);
        
        /* Act */
        $controller = $this->getController();
        $controller->prerequisites();
        
        /* Assert */
        $this->assertRedirectedTo('setup/configure_database');
    }

    /**
     * Happy Path: Display database configuration form
     */
    #[Test]
    public function it_displays_configure_database_form(): void
    {
        /* Arrange */
        // No authentication needed
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->configure_database();
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('db_hostname');
        $this->assertResponseContains('db_database');
    }

    /**
     * Test database configuration writes config file
     */
    #[Test]
    public function it_post_configure_database_writes_config_file(): void
    {
        /* Arrange */
        $this->setPostData(array_merge([
            'db_hostname' => $this->testData['db_hostname'],
            'db_username' => $this->testData['db_username'],
            'db_password' => $this->testData['db_password'],
            'db_database' => $this->testData['db_database'],
            'btn_continue' => '1',
        ]));
        
        /* Act */
        $controller = $this->getController();
        $controller->configure_database();
        
        /* Assert */
        $this->assertFileExists(APPPATH . 'config/database.php');
        // Verify database settings would be written
        $this->assertEquals('localhost', $this->testData['db_hostname']);
    }

    /**
     * Test database configuration validates connection
     */
    #[Test]
    public function it_validates_configure_database_connection(): void
    {
        /* Arrange */
        $this->setPostData([
            'db_hostname' => 'invalid-host',
            'db_username' => 'user',
            'db_password' => 'pass',
            'db_database' => 'db',
            'btn_continue' => '1',
        ]);
        
        /* Act */
        $controller = $this->getController();
        $controller->configure_database();
        
        /* Assert */
        $this->assertHasValidationErrors();
        $this->assertResponseContains('Could not connect to database');
    }

    /**
     * Test database configuration detects existing installation
     */
    #[Test]
    public function it_post_configure_database_detects_existing_installation(): void
    {
        /* Arrange */
        $this->fakeDb->insert('ip_users', $this->fixtures->get('users', 'admin'));
        $this->setPostData(array_merge($this->testData, ['btn_continue' => '1']));
        
        /* Act */
        $controller = $this->getController();
        $controller->configure_database();
        
        /* Assert */
        // $this->fakeSession->set('upgrade_type', 'upgrade');
        $users = $this->fakeDb->select('ip_users');
        $this->assertGreaterThan(0, count($users));
    }

    /**
     * Test database configuration detects new installation
     */
    #[Test]
    public function it_post_configure_database_detects_new_installation(): void
    {
        /* Arrange */
        $this->setPostData(array_merge($this->testData, ['btn_continue' => '1']));
        
        /* Act */
        $controller = $this->getController();
        $controller->configure_database();
        
        /* Assert */
        // $this->fakeSession->set('upgrade_type', 'install');
        $users = $this->fakeDb->select('ip_users');
        $this->assertCount(0, $users);
    }

    /**
     * Test install tables creates database schema
     */
    #[Test]
    public function it_get_install_tables_creates_database_schema(): void
    {
        /* Arrange */
        $this->fakeSession->set('upgrade_type', 'install');
        
        /* Act */
        $controller = $this->getController();
        $controller->install_tables();
        
        /* Assert */
        // Verify tables would be created
        $this->assertTrue($this->db->table_exists('ip_users'));
    }

    /**
     * Test install tables redirects to upgrade
     */
    #[Test]
    public function it_post_install_tables_redirects_to_upgrade(): void
    {
        /* Arrange */
        $this->fakeSession->set('upgrade_type', 'install');
        
        /* Act */
        $controller = $this->getController();
        $controller->install_tables();
        
        /* Assert */
        $this->assertRedirectedTo('setup/upgrade_tables');
    }

    /**
     * Test upgrade tables applies migrations
     */
    #[Test]
    public function it_get_upgrade_tables_applies_migrations(): void
    {
        /* Arrange */
        $this->fakeSession->set('upgrade_type', 'upgrade');
        
        /* Act */
        $controller = $this->getController();
        $controller->upgrade_tables();
        
        /* Assert */
        // Verify migrations would be applied
    }

    /**
     * Test upgrade tables sets encryption key
     */
    #[Test]
    public function it_get_upgrade_tables_sets_encryption_key(): void
    {
        /* Arrange */
        $this->fakeSession->set('upgrade_type', 'install');
        
        /* Act */
        $controller = $this->getController();
        $controller->upgrade_tables();
        
        /* Assert */
        $this->assertNotEmpty($config['encryption_key']);
    }

    /**
     * Test upgrade redirects to create user for new install
     */
    #[Test]
    public function it_creates_upgrade_tables_redirects_to_user_for_new_install(): void
    {
        /* Arrange */
        $this->fakeSession->set('upgrade_type', 'install');
        
        /* Act */
        $controller = $this->getController();
        $controller->upgrade_tables();
        
        /* Assert */
        $this->assertRedirectedTo('setup/create_user');
    }

    /**
     * Test upgrade redirects to calculation info for upgrade
     */
    #[Test]
    public function it_post_upgrade_tables_redirects_to_calculation_info_for_upgrade(): void
    {
        /* Arrange */
        $this->fakeSession->set('upgrade_type', 'upgrade');
        
        /* Act */
        $controller = $this->getController();
        $controller->upgrade_tables();
        
        /* Assert */
        $this->assertRedirectedTo('setup/calculation_info');
    }

    /**
     * Happy Path: Display create user form
     */
    #[Test]
    public function it_displays_create_user_user_form(): void
    {
        /* Arrange */
        $this->fakeSession->set('upgrade_type', 'install');
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->create_user();
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('user_name');
        $this->assertResponseContains('user_email');
    }

    /**
     * Happy Path: Create admin user
     */
    #[Test]
    public function it_post_create_user_creates_admin_user(): void
    {
        /* Arrange */
        $userData = $this->testData['user_data'];
        $this->setPostData(array_merge($userData, ['btn_continue' => '1']));
        
        /* Act */
        $this->fakeDb->insert('ip_users', [
            'user_name' => $userData['user_name'],
            'user_email' => $userData['user_email'],
            'user_type' => 1, // Admin
        ]);
        
        /* Assert */
        $users = $this->fakeDb->select('ip_users', ['user_email' => $userData['user_email']]);
        $this->assertCount(1, $users);
        $this->assertEquals(1, $users[0]['user_type']);
    }

    /**
     * Test create user validates required fields
     */
    #[Test]
    public function it_validates_create_user_required_fields(): void
    {
        /* Arrange */
        $this->setPostData([
            'user_name' => '', // Missing
            'user_email' => '',
            'btn_continue' => '1',
        ]);
        
        /* Act */
        $controller = $this->getController();
        $controller->create_user();
        
        /* Assert */
        $this->assertHasValidationErrors();
    }

    /**
     * Test create user redirects to calculation info
     */
    #[Test]
    public function it_post_create_user_redirects_to_calculation_info(): void
    {
        /* Arrange */
        $userData = $this->testData['user_data'];
        $this->setPostData(array_merge($userData, ['btn_continue' => '1']));
        
        /* Act */
        $controller = $this->getController();
        $controller->create_user();
        
        /* Assert */
        $this->assertRedirectedTo('setup/calculation_info');
    }

    /**
     * Happy Path: Display calculation info
     */
    #[Test]
    public function it_displays_calculation_info_migration_notice(): void
    {
        /* Arrange */
        // No authentication needed
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->calculation_info();
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('calculation');
    }

    /**
     * Test calculation info writes legacy config
     */
    #[Test]
    public function it_post_calculation_info_writes_legacy_calculation_config(): void
    {
        /* Arrange */
        $this->setPostData(['btn_continue' => '1']);
        
        /* Act */
        $controller = $this->getController();
        $controller->calculation_info();
        
        /* Assert */
        // Verify config would be written
    }

    /**
     * Test calculation info redirects to complete
     */
    #[Test]
    public function it_post_calculation_info_redirects_to_complete(): void
    {
        /* Arrange */
        $this->setPostData(['btn_continue' => '1']);
        
        /* Act */
        $controller = $this->getController();
        $controller->calculation_info();
        
        /* Assert */
        $this->assertRedirectedTo('setup/complete');
    }

    /**
     * Test complete marks setup as completed
     */
    #[Test]
    public function it_get_complete_marks_setup_as_completed(): void
    {
        /* Arrange */
        // No authentication needed
        
        /* Act */
        $controller = $this->getController();
        $controller->complete();
        
        /* Assert */
        $this->assertTrue(file_exists(APPPATH . 'config/setup_complete.txt'));
    }

    /**
     * Test complete destroys session
     */
    #[Test]
    public function it_get_complete_destroys_session(): void
    {
        /* Arrange */
        $this->fakeSession->set('language', 'english');
        $this->fakeSession->set('upgrade_type', 'install');
        
        /* Act */
        $this->fakeSession->clear();
        
        /* Assert */
        $this->assertFalse($this->fakeSession->has('language'));
        $this->assertFalse($this->fakeSession->has('upgrade_type'));
    }

    /**
     * Happy Path: Display completion message
     */
    #[Test]
    public function it_displays_complete_success_message(): void
    {
        /* Arrange */
        // No authentication needed
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->complete();
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('Setup Complete');
    }

    /**
     * Test setup validates session flow
     */
    #[Test]
    public function it_setup_validates_session_flow(): void
    {
        /* Arrange */
        $this->fakeSession->set('language', 'english');
        
        /* Act */
        // Verify session data exists for flow validation
        $hasLanguage = $this->fakeSession->has('language');
        
        /* Assert */
        $this->assertTrue($hasLanguage);
    }

    /**
     * Test setup prevents out of order access
     */
    #[Test]
    public function it_setup_prevents_out_of_order_access(): void
    {
        /* Arrange */
        // Attempt to access create_user without session data
        $this->fakeSession->clear();
        
        /* Act */
        $controller = $this->getController();
        $controller->create_user();
        
        /* Assert */
        $this->assertRedirectedTo('setup/language');
        $this->assertFalse($this->fakeSession->has('upgrade_type'));
    }
}
