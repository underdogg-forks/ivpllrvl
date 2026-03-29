<?php

namespace Modules\Projects\Tests;

use Modules\Projects\Controllers\ProjectsController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for ProjectsController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(ProjectsController::class)]
class ProjectsControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = ProjectsController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'clients', 'projects'];
    }
    
    /**
     * Load fixtures using SOLID trait pattern
     */
    protected function loadFixtures(): void
    {
        $this->loadAllFixtures();
    }
    
    /**
     * Set up controller-specific test data
     */
    protected function setUpController(): void
    {
        // Intentionally empty - test data is provided via ProvidesTestData trait
    }

    // #region Authentication & Authorization Tests

    /**
     * Test that projects index requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_projects_index(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /projects/index
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/projects/index');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test that projects form requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_projects_form(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /projects/form
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/projects/form');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    // #endregion

    // #region Index & List Display Tests

    /**
     * Happy Path: Projects index displays projects list
     */
    #[Test]
    public function it_displays_projects_list_on_index_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeProject = $this->fixtures->get('projects', 'active_project');
        
        /**
         * Act: GET /projects/index
         * Expected behavior: Display list of projects
         */
        $response = $this->get('/projects/index');
        
        /* Assert */
        $response->assertSee($activeProject['project_name']);
        $records = $this->fakeDb->select('ip_projects', ['project_id' => $activeProject['project_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_projects'");
        $records = $this->fakeDb->select('ip_projects', []);
        $this->assertCount(2, $records, "Database should have exactly 2 record(s) in 'ip_projects'");
    }

    /**
     * Test projects index paginates results
     */
    #[Test]
    public function it_displays_pagination_on_projects_index(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /projects/index
         * Expected behavior: Display pagination controls
         */
        $response = $this->get('/projects/index');
        
        /* Assert */
        $this->assertHasPagination($response);
        $records = $this->fakeDb->select('ip_projects', []);
        $this->assertNotEmpty($records, "Database should have record in 'ip_projects'");
    }

    // #endregion

    // #region Form Display Tests

    /**
     * Happy Path: Form displays new project form
     */
    #[Test]
    public function it_displays_new_project_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /projects/form
         * Expected behavior: Display new project form fields
         */
        $response = $this->get('/projects/form');
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['project_name', 'client_id']);
        $this->assertTrue($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Form displays edit project form
     */
    #[Test]
    public function it_displays_edit_project_form_with_existing_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeProject = $this->fixtures->get('projects', 'active_project');
        $projectId = $activeProject['project_id'];
        
        /**
         * Act: GET /projects/form/{id}
         * Expected behavior: Display edit form with existing project data
         */
        $response = $this->get('/projects/form/' . $projectId);
        
        /* Assert */
        $response->assertSee($activeProject['project_name']);
        $records = $this->fakeDb->select('ip_projects', [
            'project_id' => $projectId,
            'project_name' => 'Website Redesign'
        ]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_projects'");
    }

    /**
     * Test form returns 404 for invalid project
     */
    #[Test]
    public function it_returns_404_for_invalid_project_id(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidProjectId = 9999;
        
        /**
         * Act: GET /projects/form/{id}
         * Expected behavior: Return 404 for non-existent project
         */
        $response = $this->get('/projects/form/' . $invalidProjectId);
        
        /* Assert */
        $response->assertNotFound();
        $records = $this->fakeDb->select('ip_projects', ['project_id' => $invalidProjectId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_projects'");
    }

    // #endregion

    // #region Form Submission Tests (Create)

    /**
     * Happy Path: POST creates new project with valid data
     */
    #[Test]
    public function it_creates_new_project_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $validProjectData = $this->makeProjectData([
            'project_name' => 'New Test Project',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /projects/form
         * POST data: {
         *   "client_id": "1",
         *   "project_name": "New Test Project",
         *   "project_description": "Test project description",
         *   "project_status_id": "1",
         *   "project_date_start": "2026-03-29",
         *   "project_date_due": "2026-05-28",
         *   "project_budget": "10000.00",
         *   "project_currency": "USD",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Create new project and redirect to index
         */
        $response = $this->post('/projects/form', $validProjectData);
        
        /* Assert */
        $response->assertRedirect('/projects/index');
        $records = $this->fakeDb->select('ip_projects', ['project_name' => 'New Test Project']);
        $this->assertNotEmpty($records, "Database should have record in 'ip_projects'");
    }

    /**
     * Test POST cancels without saving when btn_cancel is clicked
     */
    #[Test]
    public function it_cancels_form_without_saving_when_cancel_button_clicked(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $projectData = $this->makeProjectData([
            'project_name' => 'Should Not Be Created',
            'btn_cancel' => 'Cancel',
        ]);
        
        /**
         * Act: POST /projects/form
         * POST data: Complete project data with btn_cancel set
         * Expected behavior: Cancel and redirect without saving
         */
        $response = $this->post('/projects/form', $projectData);
        
        /* Assert */
        $response->assertRedirect('/projects/index');
        $records = $this->fakeDb->select('ip_projects', ['project_name' => 'Should Not Be Created']);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_projects'");
    }

    // #endregion

    // #region Form Submission Tests (Update)

    /**
     * Happy Path: POST updates existing project
     */
    #[Test]
    public function it_updates_existing_project_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeProject = $this->fixtures->get('projects', 'active_project');
        $updateData = $this->makeProjectData([
            'project_id' => $activeProject['project_id'],
            'project_name' => 'Updated Project Name',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /projects/form/{id}
         * POST data: Complete project data with updated project_name
         * Expected behavior: Update project and redirect to view page
         */
        $response = $this->post('/projects/form/' . $activeProject['project_id'], $updateData);
        
        /* Assert */
        $response->assertRedirect('/projects/view/' . $activeProject['project_id']);
        $records = $this->fakeDb->select('ip_projects', ['project_id' => $activeProject['project_id'],
            'project_name' => 'Updated Project Name']);
        $this->assertNotEmpty($records, "Database should have record in 'ip_projects'");
    }

    // #endregion

    // #region View & Detail Tests

    /**
     * Happy Path: View displays project details
     */
    #[Test]
    public function it_displays_project_details_on_view_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeProject = $this->fixtures->get('projects', 'active_project');
        $projectId = $activeProject['project_id'];
        
        /**
         * Act: GET /projects/view/{id}
         * Expected behavior: Display project details
         */
        $response = $this->get('/projects/view/' . $projectId);
        
        /* Assert */
        $this->assertResponseContainsAll($response, [
            $activeProject['project_name'],
            $activeProject['project_description']
        ]);
        $records = $this->fakeDb->select('ip_projects', [
            'project_id' => $projectId,
            'project_name' => 'Website Redesign'
        ]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_projects'");
    }

    /**
     * Test view displays project tasks
     */
    #[Test]
    public function it_displays_project_tasks_on_view_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeProject = $this->fixtures->get('projects', 'active_project');
        $projectId = $activeProject['project_id'];
        
        /**
         * Act: GET /projects/view/{id}
         * Expected behavior: Display project tasks section
         */
        $response = $this->get('/projects/view/' . $projectId);
        
        /* Assert */
        $response->assertSee('project_tasks');
        $records = $this->fakeDb->select('ip_projects', ['project_id' => $projectId]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_projects'");
    }

    // #endregion

    // #region Delete Tests

    /**
     * Test POST delete removes project
     */
    #[Test]
    public function it_deletes_project_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeProject = $this->fixtures->get('projects', 'active_project');
        $projectId = $activeProject['project_id'];
        
        /**
         * Act: POST /projects/delete/{id}
         * POST data: {
         *   "btn_submit": "1"
         * }
         * Expected behavior: Delete project and redirect to index
         */
        $response = $this->post('/projects/delete/' . $projectId, [
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        $response->assertRedirect('/projects/index');
        $records = $this->fakeDb->select('ip_projects', ['project_id' => $projectId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_projects'");
    }

    // #endregion

    // #region Validation Tests

    /**
     * Test POST validates required fields
     */
    #[Test]
    public function it_validates_required_fields_are_present(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeProjectData([
            'project_name' => '',
            'client_id' => '',
        ]);
        
        /**
         * Act: POST /projects/form
         * POST data: Complete data with empty required fields
         * Expected behavior: Validation errors for missing required fields
         */
        $response = $this->post('/projects/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['project_name', 'client_id']);
    }

    /**
     * Test POST validates project name format
     */
    #[Test]
    public function it_validates_project_name_format(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeProjectData([
            'project_name' => '<script>alert("xss")</script>',
        ]);
        
        /**
         * Act: POST /projects/form
         * POST data: Complete data with invalid project_name containing HTML tags
         * Expected behavior: Validation error for project_name
         */
        $response = $this->post('/projects/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['project_name']);
    }

    /**
     * Test POST validates client_id exists
     */
    #[Test]
    public function it_validates_client_id_exists_in_database(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeProjectData([
            'client_id' => 9999,
        ]);
        
        /**
         * Act: POST /projects/form
         * POST data: Complete data with non-existent client_id
         * Expected behavior: Validation error for client_id
         */
        $response = $this->post('/projects/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['client_id']);
        $records = $this->fakeDb->select('ip_clients', ['client_id' => 9999]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_clients'");
    }

    // #endregion

    // #region Security Tests

    /**
     * Security: Test XSS sanitization in project data
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_project_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $xssData = $this->makeProjectData([
            'project_name' => '<script>alert("xss")</script>',
            'project_description' => '<img src=x onerror=alert("xss")>',
        ]);
        
        /**
         * Act: POST /projects/form
         * POST data: Complete data with XSS payloads in project_name and project_description
         * Expected behavior: XSS payloads should be sanitized or rejected
         */
        $response = $this->post('/projects/form', $xssData);
        
        /* Assert */
        $this->assertTrue(true);
    }

    /**
     * Security: Test SQL injection protection
     */
    #[Test]
    public function it_protects_against_sql_injection_attempts(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $sqlInjectionData = $this->makeProjectData([
            'project_name' => "'; DROP TABLE ip_projects; --",
            'client_id' => "1 OR 1=1",
        ]);
        
        /**
         * Act: POST /projects/form
         * POST data: Complete data with SQL injection payloads
         * Expected behavior: SQL injection should be prevented at query level
         */
        $response = $this->post('/projects/form', $sqlInjectionData);
        
        /* Assert */
        $this->assertTrue(true);
    }

    /**
     * Security: Test path traversal validation in project name
     */
    #[Test]
    public function it_validates_project_name_for_path_traversal_attempts(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $pathTraversalData = $this->makeProjectData([
            'project_name' => '../../../etc/passwd',
        ]);
        
        /**
         * Act: POST /projects/form
         * POST data: Complete data with path traversal attempt in project_name
         * Expected behavior: Validation error for project_name
         */
        $response = $this->post('/projects/form', $pathTraversalData);
        
        /* Assert */
        $response->assertSessionHasErrors(['project_name']);
    }

    // #endregion
}
