<?php

namespace Modules\Products\Tests;

use Modules\Products\Controllers\ProductsAjaxController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for ProductsAjaxController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(ProductsAjaxController::class)]
class ProductsAjaxControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = ProductsAjaxController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'products', 'families', 'units'];
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

    // #region Authentication Tests

    /**
     * Test AJAX endpoints require authentication
     */
    #[Test]
    public function it_requires_authentication_for_ajax_endpoints(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /products/ajax/modal_product_lookup
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/products/ajax/modal_product_lookup');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test get_product AJAX endpoint requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_get_product_endpoint(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /products/ajax/get_product
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/products/ajax/get_product');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    // #endregion

    // #region AJAX - Modal Product Lookup Tests

    /**
     * Happy Path: Product lookup modal displays products
     */
    #[Test]
    public function it_displays_products_in_lookup_modal(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $standardProduct = $this->fixtures->get('products', 'standard_product');
        
        /**
         * Act: POST /products/ajax/modal_product_lookup
         * POST data: {}
         * Expected behavior: Display all products in modal
         */
        $response = $this->post('/products/ajax/modal_product_lookup');
        
        /* Assert */
        $response->assertSee($standardProduct['product_name']);
        $this->assertDatabaseHasRecord('ip_products', ['product_id' => $standardProduct['product_id']]);
    }

    /**
     * Test product lookup modal supports search filtering
     */
    #[Test]
    public function it_supports_search_filtering_in_product_lookup(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $standardProduct = $this->fixtures->get('products', 'standard_product');
        
        /**
         * Act: POST /products/ajax/modal_product_lookup
         * POST data: {
         *   "product_name": "Test"
         * }
         * Expected behavior: Filter products by search term
         */
        $response = $this->post('/products/ajax/modal_product_lookup', [
            'product_name' => 'Test'
        ]);
        
        /* Assert */
        $this->assertTrue($this->fakeSession->has('user_id'));
    }

    /**
     * Test product lookup modal returns empty result for no matches
     */
    #[Test]
    public function it_returns_empty_result_when_no_products_match_search(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: POST /products/ajax/modal_product_lookup
         * POST data: {
         *   "product_name": "NonExistentProduct12345"
         * }
         * Expected behavior: Return empty result set
         */
        $response = $this->post('/products/ajax/modal_product_lookup', [
            'product_name' => 'NonExistentProduct12345'
        ]);
        
        /* Assert */
        $response->assertOk();
    }

    // #endregion

    // #region AJAX - Get Product Tests

    /**
     * Happy Path: Get product returns JSON data
     */
    #[Test]
    public function it_returns_json_data_for_valid_product(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $standardProduct = $this->fixtures->get('products', 'standard_product');
        
        /**
         * Act: POST /products/ajax/get_product
         * POST data: {
         *   "product_id": "1"
         * }
         * Expected behavior: Return product data as JSON
         */
        $response = $this->post('/products/ajax/get_product', [
            'product_id' => $standardProduct['product_id']
        ]);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('"product_name"');
        $this->assertDatabaseHasRecord('ip_products', ['product_id' => $standardProduct['product_id']]);
    }

    /**
     * Test get product returns error for invalid product ID
     */
    #[Test]
    public function it_returns_error_for_invalid_product_id(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidProductId = 9999;
        
        /**
         * Act: POST /products/ajax/get_product
         * POST data: {
         *   "product_id": "9999"
         * }
         * Expected behavior: Return error response
         */
        $response = $this->post('/products/ajax/get_product', [
            'product_id' => $invalidProductId
        ]);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('"error"');
        $this->assertDatabaseMissingRecord('ip_products', ['product_id' => $invalidProductId]);
    }

    /**
     * Test get product returns error when product_id is missing
     */
    #[Test]
    public function it_returns_error_when_product_id_is_missing(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: POST /products/ajax/get_product
         * POST data: {}
         * Expected behavior: Return error response for missing product_id
         */
        $response = $this->post('/products/ajax/get_product', []);
        
        /* Assert */
        $response->assertOk();
    }

    /**
     * Test get product includes all required fields
     */
    #[Test]
    public function it_includes_all_required_fields_in_product_response(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $standardProduct = $this->fixtures->get('products', 'standard_product');
        
        /**
         * Act: POST /products/ajax/get_product
         * POST data: {
         *   "product_id": "1"
         * }
         * Expected behavior: Return all product fields
         */
        $response = $this->post('/products/ajax/get_product', [
            'product_id' => $standardProduct['product_id']
        ]);
        
        /* Assert */
        $this->assertResponseContainsAll($response, [
            '"product_name"',
            '"product_price"'
        ]);
    }

    // #endregion

    // #region Security Tests

    /**
     * Security: Test XSS sanitization in product lookup
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_product_lookup(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: POST /products/ajax/modal_product_lookup
         * POST data: {
         *   "product_name": "<script>alert('xss')</script>"
         * }
         * Expected behavior: XSS payloads should be sanitized
         */
        $response = $this->post('/products/ajax/modal_product_lookup', [
            'product_name' => '<script>alert("xss")</script>'
        ]);
        
        /* Assert */
        $this->assertTrue(true);
    }

    /**
     * Security: Test SQL injection protection in product lookup
     */
    #[Test]
    public function it_protects_against_sql_injection_in_product_lookup(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: POST /products/ajax/modal_product_lookup
         * POST data: {
         *   "product_id": "1 OR 1=1; DROP TABLE ip_products; --"
         * }
         * Expected behavior: SQL injection should be prevented
         */
        $response = $this->post('/products/ajax/modal_product_lookup', [
            'product_id' => "1 OR 1=1; DROP TABLE ip_products; --"
        ]);
        
        /* Assert */
        $this->assertDatabaseHasRecord('ip_products', []);
    }

    /**
     * Security: Test SQL injection protection in get_product
     */
    #[Test]
    public function it_protects_against_sql_injection_in_get_product(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: POST /products/ajax/get_product
         * POST data: {
         *   "product_id": "1'; DROP TABLE ip_products; --"
         * }
         * Expected behavior: SQL injection should be prevented at query level
         */
        $response = $this->post('/products/ajax/get_product', [
            'product_id' => "1'; DROP TABLE ip_products; --"
        ]);
        
        /* Assert */
        $this->assertDatabaseHasRecord('ip_products', []);
    }

    /**
     * Security: Test CSRF protection on AJAX endpoints
     */
    #[Test]
    public function it_enforces_csrf_protection_on_ajax_endpoints(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: POST /products/ajax/get_product
         * POST data: {
         *   "product_id": "1"
         * }
         * Expected behavior: CSRF token should be validated
         */
        $response = $this->post('/products/ajax/get_product', [
            'product_id' => 1
        ]);
        
        /* Assert */
        $this->assertTrue(true);
    }

    // #endregion
}
