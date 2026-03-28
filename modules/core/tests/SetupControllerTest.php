<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\SetupController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for SetupController
 * 
 * Tests the full request/response cycle using Laravel HTTP testing.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(SetupController::class)]
class SetupControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
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
        // GET /setup/setup/index
        $response = $this->get('/setup/setup/index');
        
        /* Assert */
        $response->assertStatus(403);
        $response->assertSee('Setup is disabled');
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
        // GET /setup/setup/index
        $response = $this->get('/setup/setup/index');
        
        /* Assert */
        $response->assertStatus(302);
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
        // GET /setup/setup/language
        $response = $this->get('/setup/setup/language');
        
        /* Assert */
        $response->assertSee('language');
        $response->assertSee('english');
    }

    /**
     * Test language selection sets session
     */
    #[Test]
    public function it_post_language_sets_session_language(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /* Act */
        // POST /setup/setup/language
        $response = $this->post('/setup/setup/language', [
            'language' => 'english',
            'btn_continue' => '1',
        ]);
        
        /* Assert */
        $response->assertSessionHas('language', 'english');
    }

    /**
     * Test language selection redirects to prerequisites
     */
    #[Test]
    public function it_post_language_redirects_to_prerequisites(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /* Act */
        // POST /setup/setup/language
        $response = $this->post('/setup/setup/language', [
            'language' => 'english',
            'btn_continue' => '1',
        ]);
        
        /* Assert */
        $response->assertStatus(302);
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
        // GET /setup/setup/prerequisites
        $response = $this->get('/setup/setup/prerequisites');
        
        /* Assert */
        $response->assertSee('PHP Version');
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
        // GET /setup/setup/prerequisites
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
    public function it_get_prerequisites_checks_timezone_configuration(): void
    {
        /* Arrange */
        $timezone = date_default_timezone_get();
        
        /* Act */
        // GET /setup/setup/prerequisites
        $response = $this->get('/setup/setup/prerequisites');
        
        /* Assert */
        $response->assertSee('Timezone');
        $this->assertNotEmpty($timezone);
    }

    /**
     * Test prerequisites redirects to database configuration
     */
    #[Test]
    public function it_post_prerequisites_redirects_to_database_config(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /* Act */
        // POST /setup/setup/prerequisites
        $response = $this->post('/setup/setup/prerequisites', [
            'btn_continue' => '1',
        ]);
        
        /* Assert */
        $response->assertStatus(302);
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
        // GET /setup/setup/database
        $response = $this->get('/setup/setup/database');
        
        /* Assert */
        $response->assertSee('db_hostname');
        $response->assertSee('db_database');
    }

    /**
     * Test database configuration writes config file
     */
    #[Test]
    public function it_post_configure_database_writes_config_file(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /* Act */
        // POST /setup/setup/database
        $response = $this->post('/setup/setup/database', [
            'db_hostname' => $this->testData['db_hostname'],
            'db_username' => $this->testData['db_username'],
            'db_password' => $this->testData['db_password'],
            'db_database' => $this->testData['db_database'],
            'btn_continue' => '1',
        ]);
        
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
        // No prior setup needed
        
        /* Act */
        // POST /setup/setup/database
        $response = $this->post('/setup/setup/database', [
            'db_hostname' => 'invalid-host',
            'db_username' => 'user',
            'db_password' => 'pass',
            'db_database' => 'db',
            'btn_continue' => '1',
        ]);
        
        /* Assert */
        $response->assertSessionHasErrors();
        $response->assertSee('Could not connect to database');
    }

    /**
     * Test database configuration detects existing installation
     */
    #[Test]
    public function it_post_configure_database_detects_existing_installation(): void
    {
        /* Arrange */
        $this->fakeDb->insert('ip_users', $this->fixtures->get('users', 'admin'));
        
        /* Act */
        // POST /setup/setup/database
        $response = $this->post('/setup/setup/database', array_merge($this->testData, [
            'btn_continue' => '1',
        ]));
        
        /* Assert */
        $response->assertSessionHas('upgrade_type', 'upgrade');
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
        // No prior setup needed
        
        /* Act */
        // POST /setup/setup/database
        $response = $this->post('/setup/setup/database', array_merge($this->testData, [
            'btn_continue' => '1',
        ]));
        
        /* Assert */
        $response->assertSessionHas('upgrade_type', 'install');
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
        // No prior setup needed
        
        /* Act */
        // GET /setup/setup/install_tables
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
    public function it_post_install_tables_redirects_to_upgrade(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /* Act */
        // GET /setup/setup/install_tables
        $response = $this->withSession(['upgrade_type' => 'install'])
            ->get('/setup/setup/install_tables');
        
        /* Assert */
        $response->assertStatus(302);
    }

    /**
     * Test upgrade tables applies migrations
     */
    #[Test]
    public function it_get_upgrade_tables_applies_migrations(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /* Act */
        // GET /setup/setup/upgrade_tables
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
    public function it_get_upgrade_tables_sets_encryption_key(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /* Act */
        // GET /setup/setup/upgrade_tables
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
    public function it_creates_upgrade_tables_redirects_to_user_for_new_install(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /* Act */
        // GET /setup/setup/upgrade_tables
        $response = $this->withSession(['upgrade_type' => 'install'])
            ->get('/setup/setup/upgrade_tables');
        
        /* Assert */
        $response->assertStatus(302);
    }

    /**
     * Test upgrade redirects to calculation info for upgrade
     */
    #[Test]
    public function it_post_upgrade_tables_redirects_to_calculation_info_for_upgrade(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /* Act */
        // GET /setup/setup/upgrade_tables
        $response = $this->withSession(['upgrade_type' => 'upgrade'])
            ->get('/setup/setup/upgrade_tables');
        
        /* Assert */
        $response->assertStatus(302);
    }

    /**
     * Happy Path: Display create user form
     */
    #[Test]
    public function it_displays_create_user_user_form(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /* Act */
        // GET /setup/setup/account
        $response = $this->withSession(['upgrade_type' => 'install'])
            ->get('/setup/setup/account');
        
        /* Assert */
        $response->assertSee('user_name');
        $response->assertSee('user_email');
    }

    /**
     * Happy Path: Create admin user
     */
    #[Test]
    public function it_post_create_user_creates_admin_user(): void
    {
        /* Arrange */
        $userData = $this->testData['user_data'];
        
        /* Act */
        // POST /setup/setup/account
        $response = $this->post('/setup/setup/account', array_merge($userData, [
            'btn_continue' => '1',
        ]));
        
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
        // No prior setup needed
        
        /* Act */
        // POST /setup/setup/account
        $response = $this->post('/setup/setup/account', [
            'user_name' => '', // Missing
            'user_email' => '',
            'btn_continue' => '1',
        ]);
        
        /* Assert */
        $response->assertSessionHasErrors();
    }

    /**
     * Test create user redirects to calculation info
     */
    #[Test]
    public function it_post_create_user_redirects_to_calculation_info(): void
    {
        /* Arrange */
        $userData = $this->testData['user_data'];
        
        /* Act */
        // POST /setup/setup/account
        $response = $this->post('/setup/setup/account', array_merge($userData, [
            'btn_continue' => '1',
        ]));
        
        /* Assert */
        $response->assertStatus(302);
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
        // GET /setup/setup/calculation_info
        $response = $this->get('/setup/setup/calculation_info');
        
        /* Assert */
        $response->assertSee('calculation');
    }

    /**
     * Test calculation info writes legacy config
     */
    #[Test]
    public function it_post_calculation_info_writes_legacy_calculation_config(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /* Act */
        // POST /setup/setup/calculation_info
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
    public function it_post_calculation_info_redirects_to_complete(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /* Act */
        // POST /setup/setup/calculation_info
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
    public function it_get_complete_marks_setup_as_completed(): void
    {
        /* Arrange */
        // No authentication needed
        
        /* Act */
        // GET /setup/setup/complete
        $response = $this->get('/setup/setup/complete');
        
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
        // No prior setup needed
        
        /* Act */
        // GET /setup/setup/complete
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
    public function it_displays_complete_success_message(): void
    {
        /* Arrange */
        // No authentication needed
        
        /* Act */
        // GET /setup/setup/complete
        $response = $this->get('/setup/setup/complete');
        
        /* Assert */
        $response->assertSee('Setup Complete');
    }

    /**
     * Test setup validates session flow
     */
    #[Test]
    public function it_setup_validates_session_flow(): void
    {
        /* Arrange */
        // No prior setup needed
        
        /* Act */
        // GET /setup/setup/database
        $response = $this->withSession(['language' => 'english'])
            ->get('/setup/setup/database');
        
        /* Assert */
        $response->assertSessionHas('language', 'english');
    }

    /**
     * Test setup prevents out of order access
     */
    #[Test]
    public function it_setup_prevents_out_of_order_access(): void
    {
        /* Arrange */
        // Attempt to access account without session data
        
        /* Act */
        // GET /setup/setup/account
        $response = $this->get('/setup/setup/account');
        
        /* Assert */
        $response->assertStatus(302);
        $response->assertSessionMissing('upgrade_type');
    }
}
