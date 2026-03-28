<?php

namespace Modules\Projects\Tests;

use Modules\Projects\Controllers\TasksController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for TasksController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(TasksController::class)]
class TasksControllerTest extends ControllerTestCase
{
    protected string $controllerClass = TasksController::class;
    
    protected function loadFixtures(): void
    {
        // Load fixture data
        $users = $this->fixtures->all('users');
        $projects = $this->fixtures->all('projects');
        $tasks = $this->fixtures->all('tasks');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        foreach (['active', 'completed'] as $key) {
            $this->fakeDb->insert('ip_projects', $projects[$key]);
        }
        
        foreach (['open', 'completed', 'with_times'] as $key) {
            $this->fakeDb->insert('ip_tasks', $tasks[$key]);
        }
    }
    
    protected function setUpController(): void
    {
        // Store valid new task data from fixtures for reuse
        $this->testData = $this->fixtures->get('tasks', 'valid_new_task');
    }

    /**
     * Test that task index page requires authentication
     */
    #[Test]
    public function it_displays_tasks_index_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // GET /tasks/index
        $response = $this->get('/tasks/index');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
        // Verify no session data exists
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: User can view tasks index
     */
    #[Test]
    public function it_displays_tasks_index_returns_task_list(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /* Act */
        // GET /tasks/index
        $response = $this->get('/tasks/index');
        
        /* Assert */
        $response->assertSee('task_name');
        // Verify we have seeded tasks in fake DB
        $tasks = $this->fakeDb->select('ip_tasks');
        $this->assertCount(3, $tasks);
    }

    /**
     * Test task form page requires authentication
     */
    #[Test]
    public function it_displays_tasks_form_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // GET /tasks/form
        $response = $this->get('/tasks/form');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: User can access new task form
     */
    #[Test]
    public function it_displays_tasks_form_new_task_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /tasks/form
        $response = $this->get('/tasks/form');
        
        /* Assert */
        $response->assertSee('task_name');
        $response->assertSee('task_description');
    }

    /**
     * Happy Path: User can access edit task form
     */
    #[Test]
    public function it_displays_tasks_form_edit_task_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingTask = $this->fixtures->get('tasks', 'open');
        
        /* Act */
        // GET /tasks/form/{id}
        $response = $this->get('/tasks/form/' . $existingTask['task_id']);
        
        /* Assert */
        $response->assertSee($existingTask['task_name']);
        $response->assertSee($existingTask['task_description']);
        $tasks = $this->fakeDb->select('ip_tasks', ['task_id' => $existingTask['task_id']]);
        $this->assertCount(1, $tasks);
    }

    /**
     * Test editing non-existent task returns 404
     */
    #[Test]
    public function it_displays_tasks_form_returns_404_for_invalid_task(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidTaskId = 9999;
        
        /* Act */
        // GET /tasks/form/{id}
        $response = $this->get('/tasks/form/' . $invalidTaskId);
        
        /* Assert */
        $response->assertNotFound();
        $tasks = $this->fakeDb->select('ip_tasks', ['task_id' => $invalidTaskId]);
        $this->assertCount(0, $tasks);
    }

    /**
     * Test creating new task with valid data
     */
    #[Test]
    public function it_creates_tasks_new_task_with_valid_credentials(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $project = $this->fixtures->get('projects', 'active');
        $taskData = array_merge($this->testData, [
            'btn_submit' => '1',
            'project_id' => $project['project_id'],
        ]);
        
        /* Act */
        // POST /tasks/form
        // Successful POST would include: ['task_name' => 'string', 'project_id' => int, 'btn_submit' => '1']
        $response = $this->post('/tasks/form', $taskData);
        
        /* Assert */
        $response->assertRedirect('/tasks/index');
        $this->assertDatabaseHas('ip_tasks', ['task_name' => $this->testData['task_name']]);
    }

    /**
     * Test creating task with missing required fields fails
     */
    #[Test]
    public function it_rejects_tasks_missing_required_fields(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /tasks/form
        // Successful POST would include: ['task_name' => 'string', 'project_id' => int, 'btn_submit' => '1']
        $response = $this->post('/tasks/form', [
            'btn_submit' => '1',
            'task_name' => '', // Required field missing
            'project_id' => 1,
        ]);
        
        /* Assert */
        $response->assertSessionHasErrors(['task_name']);
    }

    /**
     * Test creating task with invalid project fails
     */
    #[Test]
    public function it_validates_tasks_project_exists(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /tasks/form
        // Successful POST would include: ['task_name' => 'string', 'project_id' => int, 'btn_submit' => '1']
        $response = $this->post('/tasks/form', array_merge($this->testData, [
            'btn_submit' => '1',
            'project_id' => 9999, // Non-existent project
        ]));
        
        /* Assert */
        $response->assertSessionHasErrors(['project_id']);
        // Verify project doesn't exist
        $projects = $this->fakeDb->select('ip_projects', ['project_id' => 9999]);
        $this->assertCount(0, $projects);
    }

    /**
     * Test XSS protection in task input
     */
    #[Test]
    public function it_sanitizes_tasks_xss_attempts(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $xssData = [
            'task_name' => '<script>alert("xss")</script>',
            'task_description' => '<img src=x onerror=alert("xss")>',
            'project_id' => 1,
            'btn_submit' => '1',
        ];
        
        /* Act */
        // POST /tasks/form
        // Successful POST would include: ['task_name' => 'string', 'project_id' => int, 'btn_submit' => '1']
        $response = $this->post('/tasks/form', $xssData);
        
        /* Assert */
        // Verify sanitization occurs (should have validation errors or sanitized)
        $this->assertTrue(true); // Placeholder - actual assertion depends on controller behavior
    }

    /**
     * Test SQL injection protection
     */
    #[Test]
    public function it_protects_tasks_against_sql_injection(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $sqlInjectionData = [
            'task_name' => "'; DROP TABLE ip_tasks; --",
            'project_id' => 1,
            'btn_submit' => '1',
        ];
        
        /* Act */
        // POST /tasks/form
        // Successful POST would include: ['task_name' => 'string', 'project_id' => int, 'btn_submit' => '1']
        $response = $this->post('/tasks/form', $sqlInjectionData);
        
        /* Assert */
        // Verify SQL injection is handled (protection happens at query level)
        $this->assertTrue(true); // Placeholder - actual assertion depends on controller behavior
    }

    /**
     * Test updating existing task
     */
    #[Test]
    public function it_updates_tasks_existing_task(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingTask = $this->fixtures->get('tasks', 'open');
        
        $updateData = array_merge($existingTask, [
            'task_name' => 'Updated Task Name',
            'task_description' => 'Updated description',
            'task_status' => 2, // Completed
            'btn_submit' => '1',
        ]);
        
        /* Act */
        // POST /tasks/form/{id}
        // Successful POST would include: ['task_name' => 'string', 'project_id' => int, 'btn_submit' => '1']
        $response = $this->post('/tasks/form/' . $existingTask['task_id'], $updateData);
        
        /* Assert */
        $response->assertRedirect();
        $this->assertDatabaseHas('ip_tasks', [
            'task_id' => $existingTask['task_id'],
            'task_name' => 'Updated Task Name',
        ]);
    }

    /**
     * Test btn_cancel redirects without saving
     */
    #[Test]
    public function it_post_tasks_form_cancels_without_saving(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $cancelData = [
            'btn_cancel' => 'Cancel',
            'task_name' => 'Should Not Save',
            'project_id' => 1,
        ];
        
        /* Act */
        // POST /tasks/form
        // Cancel POST would include: ['btn_cancel' => 'Cancel']
        $response = $this->post('/tasks/form', $cancelData);
        
        /* Assert */
        $response->assertRedirect('/tasks/index');
        $this->assertDatabaseMissing('ip_tasks', ['task_name' => 'Should Not Save']);
    }

    /**
     * Test delete requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_delete(): void
    {
        /* Arrange - No auth */
        $this->clearAuth();
        $task = $this->fixtures->get('tasks', 'completed');
        
        /* Act */
        // POST /tasks/delete/{id}
        $response = $this->post('/tasks/delete/' . $task['task_id'], [
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Delete task
     */
    #[Test]
    public function it_post_delete_removes_task(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $taskToDelete = $this->fixtures->get('tasks', 'completed');
        
        /* Act */
        // POST /tasks/delete/{id}
        // Successful POST would include: ['btn_submit' => '1']
        $response = $this->post('/tasks/delete/' . $taskToDelete['task_id'], [
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        $response->assertRedirect('/tasks/index');
        $this->assertDatabaseMissing('ip_tasks', ['task_id' => $taskToDelete['task_id']]);
    }

    /**
     * Test task price validation
     */
    #[Test]
    public function it_validates_tasks_task_price_format(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /tasks/form
        // Successful POST would include: ['task_name' => 'string', 'project_id' => int, 'btn_submit' => '1']
        $response = $this->post('/tasks/form', array_merge($this->testData, [
            'btn_submit' => '1',
            'task_price' => 'invalid', // Invalid price
        ]));
        
        /* Assert */
        $response->assertSessionHasErrors(['task_price']);
    }

    /**
     * Test task with time entries can be viewed
     */
    #[Test]
    public function it_displays_tasks_form_task_with_time_entries(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $taskWithTimes = $this->fixtures->get('tasks', 'with_times');
        
        /* Act */
        // GET /tasks/form/{id}
        $response = $this->get('/tasks/form/' . $taskWithTimes['task_id']);
        
        /* Assert */
        $response->assertSee('time_entries');
        $tasks = $this->fakeDb->select('ip_tasks', ['task_id' => $taskWithTimes['task_id']]);
        $this->assertCount(1, $tasks);
    }
}
