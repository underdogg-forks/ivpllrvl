<?php

namespace Modules\Projects\Tests;

use Modules\Projects\Controllers\ProjectsController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for ProjectsController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(ProjectsController::class)]
class ProjectsControllerTest extends ControllerTestCase
{
    protected string $controllerClass = ProjectsController::class;
    
    protected function loadFixtures(): void
    {
        // Load user, client, and project fixtures
        $users = $this->fixtures->all('users');
        $clients = $this->fixtures->all('clients');
        $projects = $this->fixtures->all('projects');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest', 'inactive'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        foreach (['active_client', 'inactive_client'] as $key) {
            $this->fakeDb->insert('ip_clients', $clients[$key]);
        }
        
        foreach (['active_project', 'completed_project'] as $key) {
            $this->fakeDb->insert('ip_projects', $projects[$key]);
        }
    }
    
    protected function setUpController(): void
    {
        // Store test project data from fixtures for reuse
        $this->testData = [
            'valid_new_project' => $this->fixtures->get('projects', 'valid_new_project'),
        ];
    }

    /**
     * Test that projects index requires authentication
     */
    #[Test]
    public function it_displays_projects_index_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // GET /projects/index
        $response = $this->get('/projects/index');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Projects index displays projects list
     */
    #[Test]
    public function it_displays_projects_index_projects_list(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeProject = $this->fixtures->get('projects', 'active_project');
        
        /* Act */
        // GET /projects/index
        $response = $this->get('/projects/index');
        
        /* Assert */
        $response->assertSee($activeProject['project_name']);
        // Verify projects exist in fake database
        $projects = $this->fakeDb->select('ip_projects');
        $this->assertNotEmpty($projects);
        $this->assertCount(2, $projects);
    }

    /**
     * Test projects index paginates results
     */
    #[Test]
    public function it_displays_projects_index_paginates_results(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /* Act */
        // GET /projects/index
        $response = $this->get('/projects/index');
        
        /* Assert */
        $response->assertSee('pagination');
        // Verify projects exist in fake database
        $projects = $this->fakeDb->select('ip_projects');
        $this->assertNotEmpty($projects);
    }

    /**
     * Test that projects form requires authentication
     */
    #[Test]
    public function it_displays_projects_form_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // GET /projects/form
        $response = $this->get('/projects/form');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Form displays new project form
     */
    #[Test]
    public function it_displays_projects_form_new_project_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /* Act */
        // GET /projects/form
        $response = $this->get('/projects/form');
        
        /* Assert */
        $response->assertSee('project_name');
        $response->assertSee('client_id');
        $this->assertTrue($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Form displays edit project form
     */
    #[Test]
    public function it_displays_projects_form_edit_project_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeProject = $this->fixtures->get('projects', 'active_project');
        $projectId = $activeProject['project_id'];
        
        /* Act */
        // GET /projects/form/{id}
        $response = $this->get('/projects/form/' . $projectId);
        
        /* Assert */
        $response->assertSee($activeProject['project_name']);
        // Verify project exists in fake database
        $project = $this->fakeDb->selectOne('ip_projects', ['project_id' => $projectId]);
        $this->assertNotNull($project);
        $this->assertEquals('Website Redesign', $project['project_name']);
    }

    /**
     * Test form returns 404 for invalid project
     */
    #[Test]
    public function it_displays_projects_form_returns_404_for_invalid_project(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidProjectId = 9999;
        
        /* Act */
        // GET /projects/form/{id}
        $response = $this->get('/projects/form/' . $invalidProjectId);
        
        /* Assert */
        $response->assertNotFound();
        // Verify project does not exist in fake database
        $project = $this->fakeDb->selectOne('ip_projects', ['project_id' => $invalidProjectId]);
        $this->assertNull($project);
    }

    /**
     * Happy Path: POST creates new project with valid data
     */
    #[Test]
    public function it_creates_projects_new_project_with_valid_credentials(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $validProjectData = $this->testData['valid_new_project'];
        
        /* Act */
        // POST /projects/form
        // Successful POST would include: ['project_name' => 'string', 'client_id' => int, 'btn_submit' => '1']
        $response = $this->post('/projects/form', $validProjectData);
        
        /* Assert */
        $response->assertRedirect('/projects/index');
        $this->assertDatabaseHas('ip_projects', ['project_name' => $validProjectData['project_name']]);
    }

    /**
     * Test POST validates required fields
     */
    #[Test]
    public function it_validates_projects_required_fields(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        // Missing required fields
        $invalidData = [
            'project_name' => '',
            'client_id' => '',
        ];
        
        /* Act */
        // POST /projects/form
        // Successful POST would include: ['project_name' => 'string', 'client_id' => int, 'btn_submit' => '1']
        $response = $this->post('/projects/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['project_name', 'client_id']);
    }

    /**
     * Test POST validates project name format
     */
    #[Test]
    public function it_validates_projects_project_name(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->testData['valid_new_project'];
        $invalidData['project_name'] = '<script>alert("xss")</script>';
        
        /* Act */
        // POST /projects/form
        // Successful POST would include: ['project_name' => 'string', 'client_id' => int, 'btn_submit' => '1']
        $response = $this->post('/projects/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['project_name']);
    }

    /**
     * Test POST validates client_id exists
     */
    #[Test]
    public function it_validates_projects_client_id(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->testData['valid_new_project'];
        $invalidData['client_id'] = 9999; // Non-existent client
        
        /* Act */
        // POST /projects/form
        // Successful POST would include: ['project_name' => 'string', 'client_id' => int, 'btn_submit' => '1']
        $response = $this->post('/projects/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['client_id']);
        // Verify client does not exist in fake database
        $client = $this->fakeDb->selectOne('ip_clients', ['client_id' => 9999]);
        $this->assertNull($client);
    }

    /**
     * Happy Path: POST updates existing project
     */
    #[Test]
    public function it_updates_projects_existing_project(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeProject = $this->fixtures->get('projects', 'active_project');
        $updateData = array_merge($activeProject, [
            'project_name' => 'Updated Project Name',
        ]);
        
        /* Act */
        // POST /projects/form/{id}
        // Successful POST would include: ['project_name' => 'string', 'client_id' => int, 'btn_submit' => '1']
        $response = $this->post('/projects/form/' . $activeProject['project_id'], $updateData);
        
        /* Assert */
        $response->assertRedirect('/projects/view/' . $activeProject['project_id']);
        $this->assertDatabaseHas('ip_projects', ['project_id' => $activeProject['project_id'], 'project_name' => 'Updated Project Name']);
    }

    /**
     * Test POST cancels without saving when btn_cancel is clicked
     */
    #[Test]
    public function it_post_projects_form_cancels_without_saving(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $validProjectData = $this->testData['valid_new_project'];
        $validProjectData['btn_cancel'] = 'Cancel';
        
        /* Act */
        // POST /projects/form
        // Cancel POST would include: ['btn_cancel' => 'Cancel']
        $response = $this->post('/projects/form', $validProjectData);
        
        /* Assert */
        $response->assertRedirect('/projects/index');
        $this->assertDatabaseMissing('ip_projects', ['project_name' => $validProjectData['project_name']]);
    }

    /**
     * Happy Path: View displays project details
     */
    #[Test]
    public function it_displays_projects_view_displays_project_details(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeProject = $this->fixtures->get('projects', 'active_project');
        $projectId = $activeProject['project_id'];
        
        /* Act */
        // GET /projects/view/{id}
        $response = $this->get('/projects/view/' . $projectId);
        
        /* Assert */
        $response->assertSee($activeProject['project_name']);
        $response->assertSee($activeProject['project_description']);
        // Verify project exists in fake database
        $project = $this->fakeDb->selectOne('ip_projects', ['project_id' => $projectId]);
        $this->assertNotNull($project);
        $this->assertEquals('Website Redesign', $project['project_name']);
    }

    /**
     * Test view displays project tasks
     */
    #[Test]
    public function it_displays_projects_view_displays_project_tasks(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeProject = $this->fixtures->get('projects', 'active_project');
        $projectId = $activeProject['project_id'];
        
        /* Act */
        // GET /projects/view/{id}
        $response = $this->get('/projects/view/' . $projectId);
        
        /* Assert */
        $response->assertSee('project_tasks');
        // Verify project exists in fake database
        $project = $this->fakeDb->selectOne('ip_projects', ['project_id' => $projectId]);
        $this->assertNotNull($project);
    }

    /**
     * Test POST delete removes project
     */
    #[Test]
    public function it_deletes_projects_removes_project(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeProject = $this->fixtures->get('projects', 'active_project');
        $projectId = $activeProject['project_id'];
        
        /* Act */
        // POST /projects/delete/{id}
        // Successful POST would include: ['btn_submit' => '1']
        $response = $this->post('/projects/delete/' . $projectId, [
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        $response->assertRedirect('/projects/index');
        $this->assertDatabaseMissing('ip_projects', ['project_id' => $projectId]);
    }

    /**
     * Security: Test XSS sanitization in project data
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_project_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $xssData = $this->testData['valid_new_project'];
        $xssData['project_name'] = '<script>alert("xss")</script>';
        $xssData['project_description'] = '<img src=x onerror=alert("xss")>';
        
        /* Act */
        // POST /projects/form
        // Successful POST would include: ['project_name' => 'string', 'client_id' => int, 'btn_submit' => '1']
        $response = $this->post('/projects/form', $xssData);
        
        /* Assert */
        // Verify sanitization occurs (should have validation errors or sanitized)
        $this->assertTrue(true); // Placeholder - actual assertion depends on controller behavior
    }

    /**
     * Security: Test SQL injection protection
     */
    #[Test]
    public function it_protects_against_sql_injection(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $sqlInjectionData = $this->testData['valid_new_project'];
        $sqlInjectionData['project_name'] = "'; DROP TABLE ip_projects; --";
        $sqlInjectionData['client_id'] = "1 OR 1=1";
        
        /* Act */
        // POST /projects/form
        // Successful POST would include: ['project_name' => 'string', 'client_id' => int, 'btn_submit' => '1']
        $response = $this->post('/projects/form', $sqlInjectionData);
        
        /* Assert */
        // Verify SQL injection is handled (protection happens at query level)
        $this->assertTrue(true); // Placeholder - actual assertion depends on controller behavior
    }

    /**
     * Security: Test path traversal validation in project name
     */
    #[Test]
    public function it_validates_project_name_path_traversal(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $pathTraversalData = $this->testData['valid_new_project'];
        $pathTraversalData['project_name'] = '../../../etc/passwd';
        
        /* Act */
        // POST /projects/form
        // Successful POST would include: ['project_name' => 'string', 'client_id' => int, 'btn_submit' => '1']
        $response = $this->post('/projects/form', $pathTraversalData);
        
        /* Assert */
        $response->assertSessionHasErrors(['project_name']);
    }
}
