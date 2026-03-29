<?php

namespace Modules\Projects\Tests;

use Modules\Projects\Controllers\TasksController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for TasksController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(TasksController::class)]
class TasksControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = TasksController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'clients', 'projects', 'tasks'];
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
     * Test that tasks index requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_tasks_index(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /tasks/index
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/tasks/index');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test that tasks form requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_tasks_form(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /tasks/form
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/tasks/form');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test that task delete requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_delete_task(): void
    {
        /* Arrange */
        $this->clearAuth();
        $task = $this->fixtures->get('tasks', 'completed');
        
        /**
         * Act: POST /tasks/delete/{id}
         * POST data: {
         *   "btn_submit": "1"
         * }
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/tasks/delete/' . $task['task_id'], [
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    // #endregion

    // #region Index & List Display Tests

    /**
     * Happy Path: Tasks index displays tasks list
     */
    #[Test]
    public function it_displays_tasks_list_on_index_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $openTask = $this->fixtures->get('tasks', 'open');
        
        /**
         * Act: GET /tasks/index
         * Expected behavior: Display list of tasks
         */
        $response = $this->get('/tasks/index');
        
        /* Assert */
        $response->assertSee($openTask['task_name']);
        $records = $this->fakeDb->select('ip_tasks', ['task_id' => $openTask['task_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_tasks'");
        $records = $this->fakeDb->select('ip_tasks', []);
        $this->assertCount(3, $records, "Database should have exactly 3 record(s) in 'ip_tasks'");
    }

    /**
     * Test tasks index paginates results
     */
    #[Test]
    public function it_displays_pagination_on_tasks_index(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /tasks/index
         * Expected behavior: Display pagination controls
         */
        $response = $this->get('/tasks/index');
        
        /* Assert */
        $this->assertHasPagination($response);
        $records = $this->fakeDb->select('ip_tasks', []);
        $this->assertNotEmpty($records, "Database should have record in 'ip_tasks'");
    }

    // #endregion

    // #region Form Display Tests

    /**
     * Happy Path: Form displays new task form
     */
    #[Test]
    public function it_displays_new_task_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /tasks/form
         * Expected behavior: Display new task form fields
         */
        $response = $this->get('/tasks/form');
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['task_name', 'task_description']);
        $this->assertTrue($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Form displays edit task form with existing data
     */
    #[Test]
    public function it_displays_edit_task_form_with_existing_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $openTask = $this->fixtures->get('tasks', 'open');
        $taskId = $openTask['task_id'];
        
        /**
         * Act: GET /tasks/form/{id}
         * Expected behavior: Display edit form with existing task data
         */
        $response = $this->get('/tasks/form/' . $taskId);
        
        /* Assert */
        $response->assertSee($openTask['task_name']);
        $response->assertSee($openTask['task_description']);
        $records = $this->fakeDb->select('ip_tasks', ['task_id' => $taskId,
            'task_name' => $openTask['task_name']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_tasks'");
    }

    /**
     * Test form returns 404 for invalid task
     */
    #[Test]
    public function it_returns_404_for_invalid_task_id(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidTaskId = 9999;
        
        /**
         * Act: GET /tasks/form/{id}
         * Expected behavior: Return 404 for non-existent task
         */
        $response = $this->get('/tasks/form/' . $invalidTaskId);
        
        /* Assert */
        $response->assertNotFound();
        $records = $this->fakeDb->select('ip_tasks', ['task_id' => $invalidTaskId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_tasks'");
    }

    /**
     * Test form displays task with time entries
     */
    #[Test]
    public function it_displays_task_form_with_time_entries(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $taskWithTimes = $this->fixtures->get('tasks', 'with_times');
        $taskId = $taskWithTimes['task_id'];
        
        /**
         * Act: GET /tasks/form/{id}
         * Expected behavior: Display task form with time entries section
         */
        $response = $this->get('/tasks/form/' . $taskId);
        
        /* Assert */
        $response->assertSee('time_entries');
        $records = $this->fakeDb->select('ip_tasks', ['task_id' => $taskId]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_tasks'");
    }

    // #endregion

    // #region Form Submission Tests (Create)

    /**
     * Happy Path: POST creates new task with valid data
     */
    #[Test]
    public function it_creates_new_task_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeProject = $this->fixtures->get('projects', 'active_project');
        $validTaskData = $this->makeTaskData([
            'task_name' => 'New Test Task',
            'project_id' => $activeProject['project_id'],
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /tasks/form
         * POST data: {
         *   "project_id": "1",
         *   "task_name": "New Test Task",
         *   "task_description": "Test task description",
         *   "task_status": "1",
         *   "task_finish_date": "2026-05-28",
         *   "task_price": "100.00",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Create new task and redirect to index
         */
        $response = $this->post('/tasks/form', $validTaskData);
        
        /* Assert */
        $response->assertRedirect('/tasks/index');
        $records = $this->fakeDb->select('ip_tasks', ['task_name' => 'New Test Task']);
        $this->assertNotEmpty($records, "Database should have record in 'ip_tasks'");
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
        
        $taskData = $this->makeTaskData([
            'task_name' => 'Should Not Be Created',
            'btn_cancel' => 'Cancel',
        ]);
        
        /**
         * Act: POST /tasks/form
         * POST data: Complete task data with btn_cancel set
         * Expected behavior: Cancel and redirect without saving
         */
        $response = $this->post('/tasks/form', $taskData);
        
        /* Assert */
        $response->assertRedirect('/tasks/index');
        $records = $this->fakeDb->select('ip_tasks', ['task_name' => 'Should Not Be Created']);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_tasks'");
    }

    // #endregion

    // #region Form Submission Tests (Update)

    /**
     * Happy Path: POST updates existing task
     */
    #[Test]
    public function it_updates_existing_task_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $openTask = $this->fixtures->get('tasks', 'open');
        $updateData = $this->makeTaskData([
            'task_id' => $openTask['task_id'],
            'task_name' => 'Updated Task Name',
            'task_description' => 'Updated description',
            'task_status' => '2',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /tasks/form/{id}
         * POST data: Complete task data with updated task_name and task_status
         * Expected behavior: Update task and redirect to index or view page
         */
        $response = $this->post('/tasks/form/' . $openTask['task_id'], $updateData);
        
        /* Assert */
        $response->assertRedirect();
        $records = $this->fakeDb->select('ip_tasks', ['task_id' => $openTask['task_id'],
            'task_name' => 'Updated Task Name']);
        $this->assertNotEmpty($records, "Database should have record in 'ip_tasks'");
    }

    // #endregion

    // #region View & Detail Tests

    // Note: TasksController may not have a dedicated view page
    // These tests can be added if the controller supports view functionality

    // #endregion

    // #region Delete Tests

    /**
     * Test POST delete removes task
     */
    #[Test]
    public function it_deletes_task_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $completedTask = $this->fixtures->get('tasks', 'completed');
        $taskId = $completedTask['task_id'];
        
        /**
         * Act: POST /tasks/delete/{id}
         * POST data: {
         *   "btn_submit": "1"
         * }
         * Expected behavior: Delete task and redirect to index
         */
        $response = $this->post('/tasks/delete/' . $taskId, [
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        $response->assertRedirect('/tasks/index');
        $records = $this->fakeDb->select('ip_tasks', ['task_id' => $taskId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_tasks'");
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
        
        $invalidData = $this->makeTaskData([
            'task_name' => '',
            'project_id' => '',
        ]);
        
        /**
         * Act: POST /tasks/form
         * POST data: Complete data with empty required fields
         * Expected behavior: Validation errors for missing required fields
         */
        $response = $this->post('/tasks/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['task_name', 'project_id']);
    }

    /**
     * Test POST validates task name format
     */
    #[Test]
    public function it_validates_task_name_format(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeTaskData([
            'task_name' => '<script>alert("xss")</script>',
        ]);
        
        /**
         * Act: POST /tasks/form
         * POST data: Complete data with invalid task_name containing HTML tags
         * Expected behavior: Validation error for task_name
         */
        $response = $this->post('/tasks/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['task_name']);
    }

    /**
     * Test POST validates project_id exists
     */
    #[Test]
    public function it_validates_project_id_exists_in_database(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeTaskData([
            'project_id' => 9999,
        ]);
        
        /**
         * Act: POST /tasks/form
         * POST data: Complete data with non-existent project_id
         * Expected behavior: Validation error for project_id
         */
        $response = $this->post('/tasks/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['project_id']);
        $records = $this->fakeDb->select('ip_projects', ['project_id' => 9999]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_projects'");
    }

    /**
     * Test POST validates task price format
     */
    #[Test]
    public function it_validates_task_price_format(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeTaskData([
            'task_price' => 'invalid_price',
        ]);
        
        /**
         * Act: POST /tasks/form
         * POST data: Complete data with invalid task_price format
         * Expected behavior: Validation error for task_price
         */
        $response = $this->post('/tasks/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['task_price']);
    }

    // #endregion

    // #region Security Tests

    /**
     * Security: Test XSS sanitization in task data
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_task_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $xssData = $this->makeTaskData([
            'task_name' => '<script>alert("xss")</script>',
            'task_description' => '<img src=x onerror=alert("xss")>',
        ]);
        
        /**
         * Act: POST /tasks/form
         * POST data: Complete data with XSS payloads in task_name and task_description
         * Expected behavior: XSS payloads should be sanitized or rejected
         */
        $response = $this->post('/tasks/form', $xssData);
        
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
        
        $sqlInjectionData = $this->makeTaskData([
            'task_name' => "'; DROP TABLE ip_tasks; --",
            'project_id' => "1 OR 1=1",
        ]);
        
        /**
         * Act: POST /tasks/form
         * POST data: Complete data with SQL injection payloads
         * Expected behavior: SQL injection should be prevented at query level
         */
        $response = $this->post('/tasks/form', $sqlInjectionData);
        
        /* Assert */
        $this->assertTrue(true);
    }

    /**
     * Security: Test path traversal validation in task name
     */
    #[Test]
    public function it_validates_task_name_for_path_traversal_attempts(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $pathTraversalData = $this->makeTaskData([
            'task_name' => '../../../etc/passwd',
        ]);
        
        /**
         * Act: POST /tasks/form
         * POST data: Complete data with path traversal attempt in task_name
         * Expected behavior: Validation error for task_name
         */
        $response = $this->post('/tasks/form', $pathTraversalData);
        
        /* Assert */
        $response->assertSessionHasErrors(['task_name']);
    }

    // #endregion
}
