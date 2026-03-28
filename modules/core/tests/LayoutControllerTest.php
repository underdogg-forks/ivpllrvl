<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\LayoutController;
use Modules\Core\Testing\TestCase;

/**
 * Note: LayoutController is a utility class for view buffering/rendering,
 * not an HTTP controller. These tests verify internal buffer() and render()
 * methods directly since they have no HTTP routes.
 */
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for LayoutController
 * 
 * Tests view buffering and rendering functionality.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(LayoutController::class)]
class LayoutControllerTest extends TestCase
{
    
    /**
     * Helper to get controller instance for testing internal methods
     */
    protected function getController(): LayoutController
    {
        return new LayoutController();
    }
    #[Test]
    public function it_buffer_method_loads_view_into_view_data(): void
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

    #[Test]
    public function it_buffer_method_accepts_single_array_argument(): void
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

    #[Test]
    public function it_buffer_method_merges_data(): void
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

    #[Test]
    public function it_set_method_adds_single_value_to_view_data(): void
    {
        /* Arrange */
        
        /* Act */
        $controller = $this->getController();
        $controller->set('key', 'value');
        
        /* Assert */
        $this->assertEquals('value', $controller->view_data['key']);
    }

    #[Test]
    public function it_set_method_accepts_array_of_values(): void
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

    #[Test]
    public function it_set_method_returns_layout_for_chaining(): void
    {
        /* Arrange */
        
        /* Act */
        $controller = $this->getController();
        $result = $controller->set('key', 'value');
        
        /* Assert */
        $this->assertInstanceOf(LayoutController::class, $result);
    }

    #[Test]
    public function it_buffer_method_returns_layout_for_chaining(): void
    {
        /* Arrange */
        
        /* Act */
        $controller = $this->getController();
        $result = $controller->buffer('content', 'module/view');
        
        /* Assert */
        $this->assertInstanceOf(LayoutController::class, $result);
    }

    #[Test]
    public function it_render_method_loads_layout_view(): void
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

    #[Test]
    public function it_load_view_method_loads_view_directly(): void
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

    #[Test]
    public function it_load_view_handles_two_part_view_path(): void
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

    #[Test]
    public function it_load_view_handles_three_part_view_path(): void
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

    #[Test]
    public function it_method_chaining_works_correctly(): void
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

    #[Test]
    public function it_view_data_is_accessible_across_methods(): void
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

    #[Test]
    public function it_buffer_handles_empty_data_parameter(): void
    {
        /* Arrange */
        
        /* Act */
        $controller = $this->getController();
        $controller->buffer('content', 'module/view');
        
        /* Assert */
        $this->assertArrayHasKey('content', $controller->view_data);
    }

    // HTTP Route Tests

    #[Test]
    public function it_header_route_returns_successful_response(): void
    {
        /* Arrange */
        // Route: GET /layout/header
        
        /* Act */
        $response = $this->get('/layout/header');
        
        /* Assert */
        $response->assertOk();
    }

    #[Test]
    public function it_footer_route_returns_successful_response(): void
    {
        /* Arrange */
        // Route: GET /layout/footer
        
        /* Act */
        $response = $this->get('/layout/footer');
        
        /* Assert */
        $response->assertOk();
    }

    #[Test]
    public function it_sidebar_route_returns_successful_response(): void
    {
        /* Arrange */
        // Route: GET /layout/sidebar
        
        /* Act */
        $response = $this->get('/layout/sidebar');
        
        /* Assert */
        $response->assertOk();
    }
}
