<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\VersionsController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for VersionsController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(VersionsController::class)]
class VersionsControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = VersionsController::class;
    
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
    
    /**
     * Set up controller-specific test data
     */
    protected function setUpController(): void
    {
        // No specific controller setup needed for versions
    }

    // #region Authentication & Authorization Tests

    /**
     * Test that versions index requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_versions_index(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /settings/versions/index
         * Expected behavior: Redirect to login when not authenticated
         */
        $response = $this->get('/settings/versions/index');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test guest users cannot access versions page
     */
    #[Test]
    public function it_requires_admin_role_to_display_versions_index(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /settings/versions/index
         * Expected behavior: Redirect when user is not admin
         */
        $response = $this->get('/settings/versions/index');
        
        /* Assert */
        $response->assertStatus(302);
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }
    
    // #endregion
    
    // #region Display & Operations Tests

    /**
     * Happy Path: Admin can view versions list
     */
    #[Test]
    public function it_displays_version_list_for_admin_user(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /settings/versions/index
         * Expected behavior: Display list of applied versions
         */
        $response = $this->get('/settings/versions/index');
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['version_file', 'version_date_applied']);
        $this->assertDatabaseCount('ip_versions', [], 3);
        
        $versions = $this->fakeDb->select('ip_versions');
        $this->assertEquals('001_1.0.0.sql', $versions[0]['version_file']);
    }

    /**
     * Test versions index supports pagination
     */
    #[Test]
    public function it_displays_pagination_on_versions_index(): void
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
        
        /**
         * Act: GET /settings/versions/index/1 (second page)
         * Expected behavior: Display pagination controls
         */
        $response = $this->get('/settings/versions/index/1');
        
        /* Assert */
        $this->assertHasPagination($response);
        $this->assertDatabaseCount('ip_versions', [], 25);
    }

    /**
     * Test versions are displayed in chronological order
     */
    #[Test]
    public function it_displays_versions_in_chronological_order(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /settings/versions/index
         * Expected behavior: Display versions in order by date applied
         */
        $response = $this->get('/settings/versions/index');
        
        /* Assert */
        $versions = $this->fakeDb->select('ip_versions');
        
        $this->assertEquals('20230101120000', $versions[0]['version_date_applied']);
        $this->assertEquals('20230215143000', $versions[1]['version_date_applied']);
        $this->assertEquals('20230320165500', $versions[2]['version_date_applied']);
    }

    /**
     * Test versions show application version numbers
     */
    #[Test]
    public function it_displays_application_version_numbers(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /settings/versions/index
         * Expected behavior: Display version numbers from filenames
         */
        $response = $this->get('/settings/versions/index');
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['1.0.0', '1.1.0', '1.2.0']);
        
        $versions = $this->fakeDb->select('ip_versions');
        $this->assertStringContainsString('1.0.0', $versions[0]['version_file']);
        $this->assertStringContainsString('1.1.0', $versions[1]['version_file']);
        $this->assertStringContainsString('1.2.0', $versions[2]['version_file']);
    }

    /**
     * Test versions show date when they were applied
     */
    #[Test]
    public function it_displays_date_when_versions_were_applied(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: GET /settings/versions/index
         * Expected behavior: Display formatted dates
         */
        $response = $this->get('/settings/versions/index');
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['2023-01-01', '2023-02-15', '2023-03-20']);
        
        $versions = $this->fakeDb->select('ip_versions');
        $this->assertMatchesRegularExpression('/^\d{14}$/', $versions[0]['version_date_applied']);
        $this->assertEquals('20230101120000', $versions[0]['version_date_applied']);
    }
    
    // #endregion
    
    // #region Validation Tests
    
    /**
     * Test versions with SQL errors are displayed
     */
    #[Test]
    public function it_displays_sql_error_count_for_versions(): void
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
        
        /**
         * Act: GET /settings/versions/index
         * Expected behavior: Display error count for versions with errors
         */
        $response = $this->get('/settings/versions/index');
        
        /* Assert */
        $response->assertSee('2 errors');
        
        $errorVersion = $this->fakeDb->select('ip_versions', ['version_id' => 4]);
        $this->assertEquals(2, $errorVersion[0]['version_sql_errors']);
    }
    
    // #endregion
}
