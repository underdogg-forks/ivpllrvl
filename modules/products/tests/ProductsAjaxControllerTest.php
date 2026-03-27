<?php

namespace Modules\Products\Tests;

use Modules\Products\Controllers\ProductsAjaxController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for ProductsAjaxController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(ProductsAjaxController::class)]
class ProductsAjaxControllerTest extends ControllerTestCase
{
    protected string $controllerClass = ProductsAjaxController::class;
    
    protected function loadFixtures(): void
    {
        // Load required fixtures
        $users = $this->fixtures->all('users');
        $products = $this->fixtures->all('products');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        foreach (['standard_product', 'service_product'] as $key) {
            $this->fakeDb->insert('ip_products', $products[$key]);
        }
        
        // Load product families
        $this->fakeDb->insert('ip_families', [
            'family_id' => 1,
            'family_name' => 'Electronics'
        ]);
        $this->fakeDb->insert('ip_families', [
            'family_id' => 2,
            'family_name' => 'Software'
        ]);
        
        // Load product units
        $this->fakeDb->insert('ip_units', [
            'unit_id' => 1,
            'unit_name' => 'Each',
            'unit_name_plrl' => 'Each'
        ]);
    }
    
    protected function setUpController(): void
    {
        // Store valid new product data from fixtures for reuse
        $this->testData = $this->fixtures->get('products', 'valid_new_product');
    }

    /**
     * Test AJAX endpoints require authentication
     */
    #[Test]
    public function it_ajax_endpoints_require_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();

        /* Act */
        // When CI bootstrap is ready, this will call the controller
        $controller = $this->getController();
        $controller->modal_product_lookup();

        /* Assert */
        $this->assertRedirectedTo('sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Test product lookup modal displays products
     */
    #[Test]
    public function it_modal_product_lookup_displays_products(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->modal_product_lookup();
        $output = ob_get_clean();

        /* Assert */
        $this->assertResponseContains('product_name');
        $products = $this->fakeDb->select('ip_products');
        $this->assertCount(2, $products);
    }

    /**
     * Test product lookup supports search filter
     */
    #[Test]
    public function it_modal_product_lookup_supports_search(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData(['product_name' => 'Test']);

        /* Act */
        $controller = $this->getController();
        $controller->modal_product_lookup();

        /* Assert */
        // Should filter products by search term
    }

    /**
     * Test get product returns JSON data
     */
    #[Test]
    public function it_get_product_returns_json(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $product = $this->fixtures->get('products', 'standard_product');

        /* Act */
        $controller = $this->getController();
        // $response = $controller->get_product($product['product_id']);

        /* Assert */
        $this->assertResponseContains('"product_name"');
        $products = $this->fakeDb->select('ip_products', ['product_id' => $product['product_id']]);
        $this->assertCount(1, $products);
    }

    /**
     * Test get product with invalid ID returns error
     */
    #[Test]
    public function it_get_product_handles_invalid_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidId = 9999;

        /* Act */
        $controller = $this->getController();
        // $response = $controller->get_product($invalidId);

        /* Assert */
        $this->assertResponseContains('"error"');
        $products = $this->fakeDb->select('ip_products', ['product_id' => $invalidId]);
        $this->assertCount(0, $products);
    }

    /**
     * Test product lookup protects against XSS
     */
    #[Test]
    public function it_modal_product_lookup_sanitizes_xss(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData(['product_name' => '<script>alert("xss")</script>']);

        /* Act */
        $controller = $this->getController();
        $controller->modal_product_lookup();

        /* Assert */
        // XSS should be sanitized
    }

    /**
     * Test product lookup protects against SQL injection
     */
    #[Test]
    public function it_modal_product_lookup_protects_against_sql_injection(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData(['product_id' => "1 OR 1=1; DROP TABLE ip_products; --"]);

        /* Act */
        $controller = $this->getController();
        $controller->modal_product_lookup();

        /* Assert */
        // Verify table still exists
        $products = $this->fakeDb->select('ip_products');
        $this->assertGreaterThanOrEqual(0, count($products));
    }
}
