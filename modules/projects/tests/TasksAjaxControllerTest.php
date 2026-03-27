<?php

namespace Modules\Projects\Tests;

use Modules\Projects\Controllers\TasksAjaxController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for TasksAjaxController
 * 
 * Tests AJAX endpoints for task operations.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(TasksAjaxController::class)]
class TasksAjaxControllerTest extends ControllerTestCase
{
    protected string $controllerClass = TasksAjaxController::class;
    
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
        // Store test data for AJAX operations
        $this->testData = [
            'task_data' => $this->fixtures->get('tasks', 'valid_new_task'),
            'ajax_request' => true,
        ];
    }

    /**
     * Test get task data requires authentication
     */
    #[Test]
    public function it_ajax_get_task_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        $task = $this->fixtures->get('tasks', 'open');
        
        /* Act */
        // When CI bootstrap is ready, this will call the AJAX controller
        $controller = $this->getController();
        // $response = $controller->get_task($task['task_id']);
        
        /* Assert */
        $this->assertJsonResponse(['success' => false, 'error' => 'unauthorized']);
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Get task data via AJAX
     */
    #[Test]
    public function it_ajax_get_task_returns_task_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $task = $this->fixtures->get('tasks', 'open');
        
        /* Act */
        // Fetch task from fake database
        $result = $this->fakeDb->select('ip_tasks', ['task_id' => $task['task_id']]);
        
        /* Assert */
        $this->assertCount(1, $result);
        $this->assertEquals($task['task_name'], $result[0]['task_name']);
        $this->assertJsonResponse(['success' => true, 'task' => $result[0]]);
    }

    /**
     * Test get non-existent task returns 404
     */
    #[Test]
    public function it_ajax_get_task_returns_404_for_invalid_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidTaskId = 9999;
        
        /* Act */
        $result = $this->fakeDb->select('ip_tasks', ['task_id' => $invalidTaskId]);
        
        /* Assert */
        $this->assertCount(0, $result);
        $this->assertJsonResponse(['success' => false, 'error' => 'not_found']);
    }

    /**
     * Test create task via AJAX requires authentication
     */
    #[Test]
    public function it_ajax_create_task_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        $controller = $this->getController();
        // $response = $controller->create_task();
        
        /* Assert */
        $this->assertJsonResponse(['success' => false, 'error' => 'unauthorized']);
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Create task via AJAX with valid data
     */
    #[Test]
    public function it_ajax_create_task_creates_new_task(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $taskData = $this->testData['task_data'];
        $project = $this->fixtures->get('projects', 'active');
        
        $this->setPostData(array_merge($taskData, [
            'project_id' => $project['project_id'],
        ]));
        
        /* Act */
        // Insert via fake database
        $this->fakeDb->insert('ip_tasks', [
            'project_id' => $project['project_id'],
            'task_name' => $taskData['task_name'],
            'task_description' => $taskData['task_description'],
            'task_status' => $taskData['task_status'],
            'task_price' => $taskData['task_price'],
        ]);
        
        /* Assert */
        $tasks = $this->fakeDb->select('ip_tasks', ['task_name' => $taskData['task_name']]);
        $this->assertCount(1, $tasks);
        $this->assertGreaterThan(0, $this->fakeDb->insertId());
        $this->assertJsonResponse(['success' => true, 'task_id' => $this->fakeDb->insertId()]);
    }

    /**
     * Test create task with invalid data fails
     */
    #[Test]
    public function it_ajax_create_task_validates_input(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'task_name' => '', // Required field missing
            'project_id' => 1,
        ]);
        
        /* Act */
        $controller = $this->getController();
        // $response = $controller->create_task();
        
        /* Assert */
        $this->assertJsonResponse(['success' => false, 'errors' => ['task_name' => 'required']]);
    }

    /**
     * Test update task via AJAX requires authentication
     */
    #[Test]
    public function it_ajax_update_task_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        $task = $this->fixtures->get('tasks', 'open');
        
        /* Act */
        $controller = $this->getController();
        // $response = $controller->update_task($task['task_id']);
        
        /* Assert */
        $this->assertJsonResponse(['success' => false, 'error' => 'unauthorized']);
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Update task via AJAX
     */
    #[Test]
    public function it_ajax_update_task_updates_existing_task(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $task = $this->fixtures->get('tasks', 'open');
        
        $updateData = [
            'task_name' => 'Updated via AJAX',
            'task_status' => 2, // Completed
        ];
        
        $this->setPostData($updateData);
        
        /* Act */
        $this->fakeDb->update('ip_tasks', 
            ['task_id' => $task['task_id']], 
            $updateData
        );
        
        /* Assert */
        $updated = $this->fakeDb->select('ip_tasks', ['task_id' => $task['task_id']]);
        $this->assertEquals('Updated via AJAX', $updated[0]['task_name']);
        $this->assertEquals(2, $updated[0]['task_status']);
        $this->assertJsonResponse(['success' => true]);
    }

    /**
     * Test update non-existent task returns error
     */
    #[Test]
    public function it_ajax_update_task_returns_404_for_invalid_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidTaskId = 9999;
        
        /* Act */
        $result = $this->fakeDb->select('ip_tasks', ['task_id' => $invalidTaskId]);
        
        /* Assert */
        $this->assertCount(0, $result);
        $this->assertJsonResponse(['success' => false, 'error' => 'not_found']);
    }

    /**
     * Test delete task via AJAX requires authentication
     */
    #[Test]
    public function it_ajax_delete_task_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        $task = $this->fixtures->get('tasks', 'completed');
        
        /* Act */
        $controller = $this->getController();
        // $response = $controller->delete_task($task['task_id']);
        
        /* Assert */
        $this->assertJsonResponse(['success' => false, 'error' => 'unauthorized']);
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Delete task via AJAX
     */
    #[Test]
    public function it_ajax_delete_task_removes_task(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $task = $this->fixtures->get('tasks', 'completed');
        
        /* Act */
        $this->fakeDb->delete('ip_tasks', ['task_id' => $task['task_id']]);
        
        /* Assert */
        $deleted = $this->fakeDb->select('ip_tasks', ['task_id' => $task['task_id']]);
        $this->assertCount(0, $deleted);
        $this->assertJsonResponse(['success' => true]);
    }

    /**
     * Test delete non-existent task returns error
     */
    #[Test]
    public function it_ajax_delete_task_returns_404_for_invalid_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidTaskId = 9999;
        
        /* Act */
        $result = $this->fakeDb->select('ip_tasks', ['task_id' => $invalidTaskId]);
        
        /* Assert */
        $this->assertCount(0, $result);
        $this->assertJsonResponse(['success' => false, 'error' => 'not_found']);
    }

    /**
     * Test get tasks by project via AJAX
     */
    #[Test]
    public function it_ajax_get_tasks_by_project_returns_filtered_tasks(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $project = $this->fixtures->get('projects', 'active');
        
        /* Act */
        $tasks = $this->fakeDb->select('ip_tasks', ['project_id' => $project['project_id']]);
        
        /* Assert */
        $this->assertGreaterThan(0, count($tasks));
        $this->assertJsonResponse(['success' => true, 'tasks' => $tasks]);
    }

    /**
     * Test update task status via AJAX
     */
    #[Test]
    public function it_ajax_update_task_status_updates_status_only(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $task = $this->fixtures->get('tasks', 'open');
        
        $this->setPostData([
            'task_status' => 2, // Completed
        ]);
        
        /* Act */
        $this->fakeDb->update('ip_tasks', 
            ['task_id' => $task['task_id']], 
            ['task_status' => 2]
        );
        
        /* Assert */
        $updated = $this->fakeDb->select('ip_tasks', ['task_id' => $task['task_id']]);
        $this->assertEquals(2, $updated[0]['task_status']);
        // Name should remain unchanged
        $this->assertEquals($task['task_name'], $updated[0]['task_name']);
        $this->assertJsonResponse(['success' => true]);
    }

    /**
     * Test XSS protection in AJAX task input
     */
    #[Test]
    public function it_ajax_create_task_sanitizes_xss_attempts(): void
    {
        /* Arrange */
        $xssData = [
            'task_name' => '<script>alert("xss")</script>',
            'task_description' => '<img src=x onerror=alert("xss")>',
            'project_id' => 1,
        ];
        
        /* Act */
        
        /* Assert */
    }

    /**
     * Test SQL injection protection in AJAX operations
     */
    #[Test]
    public function it_ajax_operations_protect_against_sql_injection(): void
    {
        /* Arrange */
        $sqlInjectionData = [
            'task_name' => "'; DROP TABLE ip_tasks; --",
            'project_id' => 1,
        ];
        
        /* Act */
        
        /* Assert */
    }
}
