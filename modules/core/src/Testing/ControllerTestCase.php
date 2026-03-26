<?php

namespace Modules\Core\Testing;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base class for Controller Integration Tests
 * 
 * Provides CodeIgniter bootstrap and common test utilities for testing controllers.
 * Controllers are tested as integration tests with full CI context.
 */
abstract class ControllerTestCase extends PHPUnitTestCase
{
    protected mixed $CI;
    protected mixed $controller;
    protected string $controllerClass;
    protected array $testUser = [];
    protected array $testData = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Bootstrap CodeIgniter if not already loaded
        if (!function_exists('get_instance')) {
            $this->bootstrapCodeIgniter();
        }
        
        $this->CI =& get_instance();
        
        // Reset test state
        $this->testUser = [];
        $this->testData = [];
        
        // Call child setup if needed
        $this->setUpController();
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $this->cleanupTestData();
        
        parent::tearDown();
    }

    /**
     * Override this method in child classes to set up controller-specific test data
     */
    protected function setUpController(): void
    {
        // Child classes can override this
    }

    /**
     * Override this method to clean up test-specific data
     */
    protected function cleanupTestData(): void
    {
        // Child classes can override this
    }

    /**
     * Bootstrap CodeIgniter for integration testing
     */
    protected function bootstrapCodeIgniter(): void
    {
        // Set testing environment
        if (!defined('ENVIRONMENT')) {
            define('ENVIRONMENT', 'testing');
        }
        
        if (!defined('BASEPATH')) {
            $system_path = dirname(__DIR__, 5) . '/vendor/codeigniter/framework/system';
            $application_folder = dirname(__DIR__, 5) . '/application';
            
            define('BASEPATH', $system_path . '/');
            define('APPPATH', $application_folder . '/');
            define('VIEWPATH', APPPATH . 'views/');
        }
        
        // Load CodeIgniter bootstrap
        // This will be implemented when CI is properly integrated for tests
        // For now, we mark tests as incomplete
    }

    /**
     * Create an authenticated admin user session
     */
    protected function actAsAdmin(array $userData = []): array
    {
        $defaultData = [
            'user_id' => 1,
            'user_type' => 1, // Admin
            'user_name' => 'Test Admin',
            'user_email' => 'admin@test.com',
            'user_company' => 'Test Company',
        ];
        
        $this->testUser = array_merge($defaultData, $userData);
        
        // Set session data when CI is available
        if (isset($this->CI->session)) {
            $this->CI->session->set_userdata($this->testUser);
        }
        
        return $this->testUser;
    }

    /**
     * Create an authenticated guest user session
     */
    protected function actAsGuest(array $userData = []): array
    {
        $defaultData = [
            'user_id' => 2,
            'user_type' => 2, // Guest (read-only)
            'user_name' => 'Test Guest',
            'user_email' => 'guest@test.com',
            'user_company' => 'Test Company',
        ];
        
        $this->testUser = array_merge($defaultData, $userData);
        
        // Set session data when CI is available
        if (isset($this->CI->session)) {
            $this->CI->session->set_userdata($this->testUser);
        }
        
        return $this->testUser;
    }

    /**
     * Clear authentication session
     */
    protected function clearAuth(): void
    {
        $this->testUser = [];
        
        if (isset($this->CI->session)) {
            $this->CI->session->sess_destroy();
        }
    }

    /**
     * Set POST data for the request
     */
    protected function setPostData(array $data): void
    {
        $_POST = $data;
        $this->testData = $data;
        
        if (isset($this->CI->input)) {
            // Force CI to reload input data
            $this->CI->input->__construct();
        }
    }

    /**
     * Get the controller instance for testing
     */
    protected function getController(): mixed
    {
        if ($this->controller === null && !empty($this->controllerClass)) {
            $this->controller = new $this->controllerClass();
        }
        
        return $this->controller;
    }

    /**
     * Assert that a redirect occurred to the expected location
     */
    protected function assertRedirectedTo(string $expectedLocation): void
    {
        // This will be implemented when CI redirect capture is set up
        $this->markTestIncomplete('Redirect assertion requires CI bootstrap integration');
    }

    /**
     * Assert that the response contains expected content
     */
    protected function assertResponseContains(string $expected): void
    {
        // This will be implemented when CI output capture is set up
        $this->markTestIncomplete('Response assertion requires CI bootstrap integration');
    }

    /**
     * Assert that validation errors occurred
     */
    protected function assertHasValidationErrors(): void
    {
        if (!isset($this->CI->form_validation)) {
            $this->markTestIncomplete('Validation assertion requires CI bootstrap');
            return;
        }
        
        $errors = $this->CI->form_validation->error_array();
        $this->assertNotEmpty($errors, 'Expected validation errors but none were found');
    }

    /**
     * Assert that specific validation error exists for a field
     */
    protected function assertHasValidationError(string $field): void
    {
        if (!isset($this->CI->form_validation)) {
            $this->markTestIncomplete('Validation assertion requires CI bootstrap');
            return;
        }
        
        $error = $this->CI->form_validation->error($field);
        $this->assertNotEmpty($error, "Expected validation error for field '{$field}' but none was found");
    }

    /**
     * Assert database record exists
     */
    protected function assertDatabaseHas(string $table, array $conditions): void
    {
        if (!isset($this->CI->db)) {
            $this->markTestIncomplete('Database assertion requires CI bootstrap');
            return;
        }
        
        $query = $this->CI->db->get_where($table, $conditions);
        $this->assertGreaterThan(0, $query->num_rows(), 
            "Failed asserting that table '{$table}' contains matching record");
    }

    /**
     * Assert database record does not exist
     */
    protected function assertDatabaseMissing(string $table, array $conditions): void
    {
        if (!isset($this->CI->db)) {
            $this->markTestIncomplete('Database assertion requires CI bootstrap');
            return;
        }
        
        $query = $this->CI->db->get_where($table, $conditions);
        $this->assertEquals(0, $query->num_rows(), 
            "Failed asserting that table '{$table}' does not contain matching record");
    }

    /**
     * Create test data in database
     */
    protected function createTestRecord(string $table, array $data): int
    {
        if (!isset($this->CI->db)) {
            $this->markTestIncomplete('Database operation requires CI bootstrap');
            return 0;
        }
        
        $this->CI->db->insert($table, $data);
        return $this->CI->db->insert_id();
    }

    /**
     * Delete test data from database
     */
    protected function deleteTestRecord(string $table, array $conditions): void
    {
        if (!isset($this->CI->db)) {
            return;
        }
        
        $this->CI->db->delete($table, $conditions);
    }
}
