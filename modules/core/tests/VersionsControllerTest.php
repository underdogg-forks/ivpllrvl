<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\VersionsController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for VersionsController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(VersionsController::class)]
class VersionsControllerTest extends ControllerTestCase
{
    protected string $controllerClass = VersionsController::class;
    
    protected function loadFixtures(): void
    {
        // Load user fixtures for authentication tests
        $users = $this->fixtures->all('users');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        // Seed version history data
        $this->fakeDb->insert('ip_versions', [
            'version_id' => 1,
            'version_date_applied' => '20230101120000',
            'version_file' => '001_1.0.0.sql',
            'version_sql_errors' => 0,
        ]);
        
        $this->fakeDb->insert('ip_versions', [
            'version_id' => 2,
            'version_date_applied' => '20230215143000',
            'version_file' => '002_1.1.0.sql',
            'version_sql_errors' => 0,
        ]);
        
        $this->fakeDb->insert('ip_versions', [
            'version_id' => 3,
            'version_date_applied' => '20230320165500',
            'version_file' => '003_1.2.0.sql',
            'version_sql_errors' => 0,
        ]);
    }
    
    protected function setUpController(): void
    {
        // No specific controller setup needed for versions
    }

    /**
     * Test that versions index page requires authentication
     */
    #[Test]
    public function it_displays_versions_index_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // GET /settings/versions/index
        $response = $this->get('/settings/versions/index');
        
        /* Assert */
        $response->assertStatus(302);
        // Verify no session data exists
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Admin can view versions list
     */
    #[Test]
    public function it_displays_versions_index_returns_version_list(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /* Act */
        // GET /settings/versions/index
        $response = $this->get('/settings/versions/index');
        
        /* Assert */
        $response->assertSee('version_file');
        $response->assertSee('version_date_applied');
        
        // Verify we have seeded versions in fake DB
        $versions = $this->fakeDb->select('ip_versions');
        $this->assertCount(3, $versions);
        $this->assertEquals('001_1.0.0.sql', $versions[0]['version_file']);
    }

    /**
     * Test versions index supports pagination
     */
    #[Test]
    public function it_displays_versions_index_pagination(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        // Add more versions to test pagination
        for ($i = 4; $i <= 25; $i++) {
            $this->fakeDb->insert('ip_versions', [
                'version_id' => $i,
                'version_date_applied' => sprintf('202304%02d120000', $i),
                'version_file' => sprintf('%03d_1.%d.0.sql', $i, $i),
                'version_sql_errors' => 0,
            ]);
        }
        
        /* Act */
        // GET /settings/versions/index/1 (second page)
        $response = $this->get('/settings/versions/index/1');
        
        /* Assert */
        $response->assertSee('pagination');
        
        // Verify we have enough versions for pagination
        $versions = $this->fakeDb->select('ip_versions');
        $this->assertCount(25, $versions);
    }

    /**
     * Test versions are displayed in chronological order
     */
    #[Test]
    public function it_versions_are_displayed_in_chronological_order(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /settings/versions/index
        $response = $this->get('/settings/versions/index');
        
        /* Assert */
        $versions = $this->fakeDb->select('ip_versions');
        
        // Verify chronological order by date applied
        $this->assertEquals('20230101120000', $versions[0]['version_date_applied']);
        $this->assertEquals('20230215143000', $versions[1]['version_date_applied']);
        $this->assertEquals('20230320165500', $versions[2]['version_date_applied']);
    }

    /**
     * Test versions show application version numbers
     */
    #[Test]
    public function it_versions_show_application_version_numbers(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /settings/versions/index
        $response = $this->get('/settings/versions/index');
        
        /* Assert */
        $response->assertSee('1.0.0');
        $response->assertSee('1.1.0');
        $response->assertSee('1.2.0');
        
        // Verify version files contain version numbers
        $versions = $this->fakeDb->select('ip_versions');
        $this->assertStringContainsString('1.0.0', $versions[0]['version_file']);
        $this->assertStringContainsString('1.1.0', $versions[1]['version_file']);
        $this->assertStringContainsString('1.2.0', $versions[2]['version_file']);
    }

    /**
     * Test versions show date when they were applied
     */
    #[Test]
    public function it_versions_show_date_applied(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /settings/versions/index
        $response = $this->get('/settings/versions/index');
        
        /* Assert */
        $response->assertSee('2023-01-01');
        $response->assertSee('2023-02-15');
        $response->assertSee('2023-03-20');
        
        // Verify date applied format (YmdHis)
        $versions = $this->fakeDb->select('ip_versions');
        $this->assertMatchesRegularExpression('/^\d{14}$/', $versions[0]['version_date_applied']);
        $this->assertEquals('20230101120000', $versions[0]['version_date_applied']);
    }
    
    /**
     * Test guest users cannot access versions page
     */
    #[Test]
    public function it_displays_versions_index_requires_admin_role(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /* Act */
        // GET /settings/versions/index
        $response = $this->get('/settings/versions/index');
        
        /* Assert */
        $response->assertStatus(302);
        // Verify session has guest user type
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }
    
    /**
     * Test versions with SQL errors are displayed
     */
    #[Test]
    public function it_versions_show_sql_error_count(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        // Add a version with SQL errors
        $this->fakeDb->insert('ip_versions', [
            'version_id' => 4,
            'version_date_applied' => '20230415100000',
            'version_file' => '004_1.3.0.sql',
            'version_sql_errors' => 2,
        ]);
        
        /* Act */
        // GET /settings/versions/index
        $response = $this->get('/settings/versions/index');
        
        /* Assert */
        $response->assertSee('2 errors');
        
        // Verify error count is stored
        $errorVersion = $this->fakeDb->select('ip_versions', ['version_id' => 4]);
        $this->assertEquals(2, $errorVersion[0]['version_sql_errors']);
    }
}
