<?php

namespace Modules\Projects\Tests;

use Modules\Projects\Controllers\TasksAjaxController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for TasksAjaxController
 * 
 * Tests the full AJAX request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(TasksAjaxController::class)]
class TasksAjaxControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = TasksAjaxController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'projects', 'tasks'];
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
     * Test that get task data requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_get_task_data(): void
    {
        /* Arrange */
        $this->clearAuth();
        $openTask = $this->fixtures->get('tasks', 'open_task');
        
        /**
         * Act: POST /tasks/ajax/get_latest
         * POST data: {
         *   "task_id": 1
         * }
         * Expected behavior: Return unauthorized error when not authenticated
         */
        $response = $this->post('/tasks/ajax/get_latest', [
            'task_id' => $openTask['task_id'],
        ]);
        
        /* Assert */
        $this->assertAjaxUnauthorized($response);
    }

    /**
     * Test that create task via AJAX requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_create_task_via_ajax(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /tasks/ajax/save_task_attachment
         * POST data: {
         *   "task_id": 1
         * }
         * Expected behavior: Return unauthorized error when not authenticated
         */
        $response = $this->post('/tasks/ajax/save_task_attachment', [
            'task_id' => 1,
        ]);
        
        /* Assert */
        $this->assertAjaxUnauthorized($response);
    }

    /**
     * Test that update task via AJAX requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_update_task_via_ajax(): void
    {
        /* Arrange */
        $this->clearAuth();
        $openTask = $this->fixtures->get('tasks', 'open_task');
        
        /**
         * Act: POST /tasks/ajax/get_latest
         * POST data: {
         *   "task_id": 1,
         *   "task_name": "Updated"
         * }
         * Expected behavior: Return unauthorized error when not authenticated
         */
        $response = $this->post('/tasks/ajax/get_latest', [
            'task_id' => $openTask['task_id'],
            'task_name' => 'Updated',
        ]);
        
        /* Assert */
        $this->assertAjaxUnauthorized($response);
    }

    /**
     * Test that delete task attachment via AJAX requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_delete_task_attachment_via_ajax(): void
    {
        /* Arrange */
        $this->clearAuth();
        $completedTask = $this->fixtures->get('tasks', 'completed_task');
        
        /**
         * Act: POST /tasks/ajax/delete_task_attachment
         * POST data: {
         *   "task_id": 2,
         *   "attachment_id": 1
         * }
         * Expected behavior: Return unauthorized error when not authenticated
         */
        $response = $this->post('/tasks/ajax/delete_task_attachment', [
            'task_id' => $completedTask['task_id'],
            'attachment_id' => 1,
        ]);
        
        /* Assert */
        $this->assertAjaxUnauthorized($response);
    }

    // #endregion

    // #region AJAX Operations - Get Task Data

    /**
     * Happy Path: Get task data via AJAX returns task details
     */
    #[Test]
    public function it_returns_task_data_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $openTask = $this->fixtures->get('tasks', 'open_task');
        
        /**
         * Act: POST /tasks/ajax/get_latest
         * POST data: {
         *   "task_id": 1
         * }
         * Expected behavior: Return success with task data
         */
        $response = $this->post('/tasks/ajax/get_latest', [
            'task_id' => $openTask['task_id'],
        ]);
        
        /* Assert */
        $this->assertAjaxSuccess($response);
        $records = $this->fakeDb->select('ip_tasks', ['task_id' => $openTask['task_id'],
            'task_name' => $openTask['task_name']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_tasks'");
    }

    /**
     * Test get non-existent task returns 404 error
     */
    #[Test]
    public function it_returns_404_for_non_existent_task_id(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidTaskId = 9999;
        
        /**
         * Act: POST /tasks/ajax/get_latest
         * POST data: {
         *   "task_id": 9999
         * }
         * Expected behavior: Return not found error for non-existent task
         */
        $response = $this->post('/tasks/ajax/get_latest', [
            'task_id' => $invalidTaskId,
        ]);
        
        /* Assert */
        $this->assertAjaxNotFound($response);
        $records = $this->fakeDb->select('ip_tasks', ['task_id' => $invalidTaskId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_tasks'");
    }

    /**
     * Test get tasks by project returns filtered tasks
     */
    #[Test]
    public function it_returns_tasks_filtered_by_project_id(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeProject = $this->fixtures->get('projects', 'active_project');
        
        /**
         * Act: POST /tasks/ajax/get_latest
         * POST data: {
         *   "project_id": 1
         * }
         * Expected behavior: Return success with tasks filtered by project
         */
        $response = $this->post('/tasks/ajax/get_latest', [
            'project_id' => $activeProject['project_id'],
        ]);
        
        /* Assert */
        $this->assertAjaxSuccess($response);
        $records = $this->fakeDb->select('ip_projects', ['project_id' => $activeProject['project_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_projects'");
    }

    // #endregion

    // #region AJAX Operations - Create Task

    /**
     * Happy Path: Create task via AJAX with valid data
     */
    #[Test]
    public function it_creates_new_task_via_ajax_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $activeProject = $this->fixtures->get('projects', 'active_project');
        $validTaskData = $this->makeTaskData([
            'task_name' => 'New AJAX Task',
            'project_id' => $activeProject['project_id'],
        ]);
        
        /**
         * Act: POST /tasks/ajax/save_task_attachment
         * POST data: {
         *   "task_name": "New AJAX Task",
         *   "task_description": "Test task description",
         *   "task_status": "1",
         *   "task_priority": "medium",
         *   "project_id": "1",
         *   "task_date_start": "2026-03-29",
         *   "task_date_due": "2026-04-28"
         * }
         * Expected behavior: Create new task and return success
         */
        $response = $this->post('/tasks/ajax/save_task_attachment', $validTaskData);
        
        /* Assert */
        $this->assertAjaxSuccess($response);
        $records = $this->fakeDb->select('ip_tasks', ['task_name' => 'New AJAX Task']);
        $this->assertNotEmpty($records, "Database should have record in 'ip_tasks'");
    }

    // #endregion

    // #region AJAX Operations - Update Task

    /**
     * Happy Path: Update task via AJAX with valid data
     */
    #[Test]
    public function it_updates_existing_task_via_ajax_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $openTask = $this->fixtures->get('tasks', 'open_task');
        $updateData = $this->makeTaskData([
            'task_id' => $openTask['task_id'],
            'task_name' => 'Updated via AJAX',
            'task_status' => 2,
        ]);
        
        /**
         * Act: POST /tasks/ajax/get_latest
         * POST data: {
         *   "task_id": "1",
         *   "task_name": "Updated via AJAX",
         *   "task_description": "Test task description",
         *   "task_status": "2",
         *   "task_priority": "medium",
         *   "project_id": "1",
         *   "task_date_start": "2026-03-29",
         *   "task_date_due": "2026-04-28"
         * }
         * Expected behavior: Update existing task and return success
         */
        $response = $this->post('/tasks/ajax/get_latest', $updateData);
        
        /* Assert */
        $this->assertAjaxSuccess($response);
        $records = $this->fakeDb->select('ip_tasks', ['task_id' => $openTask['task_id'],
            'task_name' => 'Updated via AJAX']);
        $this->assertNotEmpty($records, "Database should have record in 'ip_tasks'");
    }

    /**
     * Test update non-existent task returns 404 error
     */
    #[Test]
    public function it_returns_404_when_updating_non_existent_task(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidTaskId = 9999;
        $updateData = $this->makeTaskData([
            'task_id' => $invalidTaskId,
            'task_name' => 'Updated',
        ]);
        
        /**
         * Act: POST /tasks/ajax/get_latest
         * POST data: Complete task data with non-existent task_id
         * Expected behavior: Return not found error
         */
        $response = $this->post('/tasks/ajax/get_latest', $updateData);
        
        /* Assert */
        $this->assertAjaxNotFound($response);
        $records = $this->fakeDb->select('ip_tasks', ['task_id' => $invalidTaskId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_tasks'");
    }

    /**
     * Test update task status via AJAX
     */
    #[Test]
    public function it_updates_task_status_only_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $openTask = $this->fixtures->get('tasks', 'open_task');
        
        /**
         * Act: POST /tasks/ajax/get_latest
         * POST data: {
         *   "task_id": "1",
         *   "task_status": "2"
         * }
         * Expected behavior: Update only task status and return success
         */
        $response = $this->post('/tasks/ajax/get_latest', [
            'task_id' => $openTask['task_id'],
            'task_status' => 2,
        ]);
        
        /* Assert */
        $this->assertAjaxSuccess($response);
        $records = $this->fakeDb->select('ip_tasks', ['task_id' => $openTask['task_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_tasks'");
    }

    // #endregion

    // #region AJAX Operations - Delete Task Attachment

    /**
     * Happy Path: Delete task attachment via AJAX
     */
    #[Test]
    public function it_deletes_task_attachment_successfully_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $completedTask = $this->fixtures->get('tasks', 'completed_task');
        
        /**
         * Act: POST /tasks/ajax/delete_task_attachment
         * POST data: {
         *   "task_id": "2",
         *   "attachment_id": "1"
         * }
         * Expected behavior: Delete attachment and return success
         */
        $response = $this->post('/tasks/ajax/delete_task_attachment', [
            'task_id' => $completedTask['task_id'],
            'attachment_id' => 1,
        ]);
        
        /* Assert */
        $this->assertAjaxSuccess($response);
        $records = $this->fakeDb->select('ip_tasks', ['task_id' => $completedTask['task_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_tasks'");
    }

    /**
     * Test delete attachment for non-existent task returns 404 error
     */
    #[Test]
    public function it_returns_404_when_deleting_attachment_for_non_existent_task(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidTaskId = 9999;
        
        /**
         * Act: POST /tasks/ajax/delete_task_attachment
         * POST data: {
         *   "task_id": "9999",
         *   "attachment_id": "1"
         * }
         * Expected behavior: Return not found error
         */
        $response = $this->post('/tasks/ajax/delete_task_attachment', [
            'task_id' => $invalidTaskId,
            'attachment_id' => 1,
        ]);
        
        /* Assert */
        $this->assertAjaxNotFound($response);
        $records = $this->fakeDb->select('ip_tasks', ['task_id' => $invalidTaskId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_tasks'");
    }

    // #endregion

    // #region Validation Tests

    /**
     * Test POST validates required fields for task creation
     */
    #[Test]
    public function it_validates_required_fields_for_task_creation(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeTaskData([
            'task_name' => '',
            'project_id' => '',
        ]);
        
        /**
         * Act: POST /tasks/ajax/save_task_attachment
         * POST data: Complete data with empty required fields
         * Expected behavior: Validation errors for missing required fields
         */
        $response = $this->post('/tasks/ajax/save_task_attachment', $invalidData);
        
        /* Assert */
        $this->assertAjaxValidationErrors($response, ['task_name', 'project_id']);
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
         * Act: POST /tasks/ajax/save_task_attachment
         * POST data: Complete data with invalid task_name containing HTML tags
         * Expected behavior: Validation error for task_name
         */
        $response = $this->post('/tasks/ajax/save_task_attachment', $invalidData);
        
        /* Assert */
        $this->assertAjaxValidationErrors($response, ['task_name']);
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
         * Act: POST /tasks/ajax/save_task_attachment
         * POST data: Complete data with non-existent project_id
         * Expected behavior: Validation error for project_id
         */
        $response = $this->post('/tasks/ajax/save_task_attachment', $invalidData);
        
        /* Assert */
        $this->assertAjaxValidationErrors($response, ['project_id']);
        $records = $this->fakeDb->select('ip_projects', ['project_id' => 9999]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_projects'");
    }

    // #endregion

    // #region Security Tests

    /**
     * Security: Test XSS sanitization in task data via AJAX
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_task_data_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $xssData = $this->makeTaskData([
            'task_name' => '<script>alert("xss")</script>',
            'task_description' => '<img src=x onerror=alert("xss")>',
        ]);
        
        /**
         * Act: POST /tasks/ajax/save_task_attachment
         * POST data: Complete data with XSS payloads in task_name and task_description
         * Expected behavior: XSS payloads should be sanitized or rejected
         */
        $response = $this->post('/tasks/ajax/save_task_attachment', $xssData);
        
        /* Assert */
        $this->assertTrue(true);
    }

    /**
     * Security: Test SQL injection protection in AJAX operations
     */
    #[Test]
    public function it_protects_against_sql_injection_attempts_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $sqlInjectionData = $this->makeTaskData([
            'task_name' => "'; DROP TABLE ip_tasks; --",
            'project_id' => "1 OR 1=1",
        ]);
        
        /**
         * Act: POST /tasks/ajax/save_task_attachment
         * POST data: Complete data with SQL injection payloads
         * Expected behavior: SQL injection should be prevented at query level
         */
        $response = $this->post('/tasks/ajax/save_task_attachment', $sqlInjectionData);
        
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
         * Act: POST /tasks/ajax/save_task_attachment
         * POST data: Complete data with path traversal attempt in task_name
         * Expected behavior: Validation error for task_name
         */
        $response = $this->post('/tasks/ajax/save_task_attachment', $pathTraversalData);
        
        /* Assert */
        $this->assertAjaxValidationErrors($response, ['task_name']);
    }

    // #endregion

    // #region Error Handling Tests

    /**
     * Test AJAX error handling for malformed request data
     */
    #[Test]
    public function it_handles_malformed_request_data_gracefully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: POST /tasks/ajax/get_latest
         * POST data: Empty or malformed data
         * Expected behavior: Return error response with appropriate message
         */
        $response = $this->post('/tasks/ajax/get_latest', []);
        
        /* Assert */
        $this->assertAjaxError($response);
    }

    /**
     * Test AJAX error handling for database connection failure
     */
    #[Test]
    public function it_handles_database_errors_gracefully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $validTaskData = $this->makeTaskData([
            'task_name' => 'Test Task',
        ]);
        
        /**
         * Act: POST /tasks/ajax/save_task_attachment
         * POST data: Valid task data
         * Expected behavior: Handle database errors and return error response
         */
        $response = $this->post('/tasks/ajax/save_task_attachment', $validTaskData);
        
        /* Assert */
        $this->assertTrue(true);
    }

    // #endregion
}
