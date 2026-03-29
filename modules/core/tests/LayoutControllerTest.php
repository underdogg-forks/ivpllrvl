<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\LayoutController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for LayoutController
 * 
 * Tests view buffering and rendering functionality with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * Note: LayoutController is a utility class for view buffering/rendering,
 * not an HTTP controller. These tests verify internal buffer() and render()
 * methods directly, plus HTTP routes for header/footer/sidebar.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(LayoutController::class)]
class LayoutControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = LayoutController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return [];
    }
    
    /**
     * Load fixtures using SOLID trait pattern
     */
    protected function loadFixtures(): void
    {
        // No fixtures needed for layout controller
    }
    
    /**
     * Set up controller-specific test data
     */
    protected function setUpController(): void
    {
        // Intentionally empty - no specific setup needed
    }
    
    /**
     * Helper to get controller instance for testing internal methods
     */
    protected function getController(): LayoutController
    {
        return new LayoutController();
    }
    
    // #region Internal Method Tests (buffer, set, render)
    /**
     * Happy Path: buffer() loads view content into view_data
     */
    #[Test]
    public function it_loads_view_into_view_data_with_buffer_method(): void
    {
        /* Arrange */
        $testData = [
            'test_var' => 'test_value',
            'content' => '<p>Test content</p>',
        ];
        
        /* Act */
        $controller = $this->getController();
        $controller->buffer('content', 'invoices/index', $testData);
        
        /* Assert */
        $this->assertArrayHasKey('content', $controller->view_data);
    }

    /**
     * Test buffer() accepts single array argument with multiple buffers
     */
    #[Test]
    public function it_accepts_array_argument_with_multiple_buffers(): void
    {
        /* Arrange */
        $buffersArray = [
            ['content', 'module/view1'],
            ['sidebar', 'module/view2'],
        ];
        
        /* Act */
        $controller = $this->getController();
        $controller->buffer($buffersArray);
        
        /* Assert */
        $this->assertArrayHasKey('content', $controller->view_data);
        $this->assertArrayHasKey('sidebar', $controller->view_data);
    }

    /**
     * Test buffer() merges data with existing view_data
     */
    #[Test]
    public function it_merges_data_with_existing_view_data(): void
    {
        /* Arrange */
        
        /* Act */
        $controller = $this->getController();
        $controller->set('existing_var', 'value1');
        $controller->buffer('content', 'module/view', ['new_var' => 'value2']);
        
        /* Assert */
        $this->assertEquals('value1', $controller->view_data['existing_var']);
        $this->assertEquals('value2', $controller->view_data['new_var']);
    }

    /**
     * Happy Path: set() adds single value to view_data
     */
    #[Test]
    public function it_adds_single_value_to_view_data_with_set_method(): void
    {
        /* Arrange */
        
        /* Act */
        $controller = $this->getController();
        $controller->set('key', 'value');
        
        /* Assert */
        $this->assertEquals('value', $controller->view_data['key']);
    }

    /**
     * Test set() accepts array of values
     */
    #[Test]
    public function it_accepts_array_of_values_with_set_method(): void
    {
        /* Arrange */
        $data = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        
        /* Act */
        $controller = $this->getController();
        $controller->set($data);
        
        /* Assert */
        $this->assertEquals('value1', $controller->view_data['key1']);
        $this->assertEquals('value2', $controller->view_data['key2']);
        $this->assertEquals('value3', $controller->view_data['key3']);
    }

    /**
     * Test set() returns layout instance for method chaining
     */
    #[Test]
    public function it_returns_layout_instance_for_chaining_with_set(): void
    {
        /* Arrange */
        
        /* Act */
        $controller = $this->getController();
        $result = $controller->set('key', 'value');
        
        /* Assert */
        $this->assertInstanceOf(LayoutController::class, $result);
    }

    /**
     * Test buffer() returns layout instance for method chaining
     */
    #[Test]
    public function it_returns_layout_instance_for_chaining_with_buffer(): void
    {
        /* Arrange */
        
        /* Act */
        $controller = $this->getController();
        $result = $controller->buffer('content', 'module/view');
        
        /* Assert */
        $this->assertInstanceOf(LayoutController::class, $result);
    }

    /**
     * Happy Path: render() loads and outputs layout view
     */
    #[Test]
    public function it_loads_and_outputs_layout_view_with_render(): void
    {
        /* Arrange */
        
        /* Act */
        $controller = $this->getController();
        $controller->set('title', 'Test Page');
        ob_start();
        $controller->render();
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertStringContainsString('Test Page', $output);
    }

    /**
     * Happy Path: load_view() loads view directly without layout
     */
    #[Test]
    public function it_loads_view_directly_without_layout(): void
    {
        /* Arrange */
        $data = ['test_var' => 'test_value'];
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->load_view('module/view', $data);
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertNotEmpty($output);
    }

    /**
     * Test load_view() handles two-part view path (module/view)
     */
    #[Test]
    public function it_handles_two_part_view_path(): void
    {
        /* Arrange */
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->load_view('module/view');
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertNotEmpty($output);
    }

    /**
     * Test load_view() handles three-part view path (module/subfolder/view)
     */
    #[Test]
    public function it_handles_three_part_view_path(): void
    {
        /* Arrange */
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->load_view('module/subfolder/view');
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertNotEmpty($output);
    }

    /**
     * Test method chaining works correctly with set() and buffer()
     */
    #[Test]
    public function it_supports_method_chaining_correctly(): void
    {
        /* Arrange */
        
        /* Act */
        $controller = $this->getController();
        $controller->set('title', 'Test')
            ->buffer('content', 'module/view')
            ->set('footer', 'Footer content');
        
        /* Assert */
        $this->assertEquals('Test', $controller->view_data['title']);
        $this->assertArrayHasKey('content', $controller->view_data);
        $this->assertEquals('Footer content', $controller->view_data['footer']);
    }

    /**
     * Test view_data is accessible across multiple method calls
     */
    #[Test]
    public function it_maintains_view_data_across_method_calls(): void
    {
        /* Arrange */
        
        /* Act */
        $controller = $this->getController();
        $controller->set('key1', 'value1');
        $controller->buffer('content', 'module/view');
        $controller->set('key2', 'value2');
        
        /* Assert */
        $this->assertEquals('value1', $controller->view_data['key1']);
        $this->assertEquals('value2', $controller->view_data['key2']);
    }

    /**
     * Test buffer() handles empty data parameter
     */
    #[Test]
    public function it_handles_empty_data_parameter_in_buffer(): void
    {
        /* Arrange */
        
        /* Act */
        $controller = $this->getController();
        $controller->buffer('content', 'module/view');
        
        /* Assert */
        $this->assertArrayHasKey('content', $controller->view_data);
    }
    
    // #endregion
    
    // #region HTTP Route Tests
    
    /**
     * Happy Path: header route returns successful response
     */
    #[Test]
    public function it_returns_successful_response_for_header_route(): void
    {
        /* Arrange */
        
        /**
         * Act: GET /layout/header
         * Expected behavior: Return header view
         */
        $response = $this->get('/layout/header');
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Happy Path: footer route returns successful response
     */
    #[Test]
    public function it_returns_successful_response_for_footer_route(): void
    {
        /* Arrange */
        
        /**
         * Act: GET /layout/footer
         * Expected behavior: Return footer view
         */
        $response = $this->get('/layout/footer');
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Happy Path: sidebar route returns successful response
     */
    #[Test]
    public function it_returns_successful_response_for_sidebar_route(): void
    {
        /* Arrange */
        
        /**
         * Act: GET /layout/sidebar
         * Expected behavior: Return sidebar view
         */
        $response = $this->get('/layout/sidebar');
        
        /* Assert */
        $response->assertOk();
    }
    
    // #endregion
}
