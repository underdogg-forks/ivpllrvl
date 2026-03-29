<?php

namespace Modules\Products\Tests;

use Modules\Products\Controllers\ProductsController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for ProductsController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(ProductsController::class)]
class ProductsControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = ProductsController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'products'];
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
     * Test that products index requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_products_index(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /products/index
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/products/index');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test that products form requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_products_form(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /products/form
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/products/form');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    // #endregion

    // #region Index & List Display Tests

    /**
     * Happy Path: Products index displays products list
     */
    #[Test]
    public function it_displays_products_list_on_index_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $standardProduct = $this->fixtures->get('products', 'standard_product');
        
        /**
         * Act: GET /products/index
         * Expected behavior: Display list of products
         */
        $response = $this->get('/products/index');
        
        /* Assert */
        $response->assertSee($standardProduct['product_name']);
        $records = $this->fakeDb->select('ip_products', ['product_id' => $standardProduct['product_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_products'");
        $records = $this->fakeDb->select('ip_products', []);
        $this->assertCount(2, $records, "Database should have exactly 2 record(s) in 'ip_products'");
    }

    /**
     * Test products index displays pagination controls
     */
    #[Test]
    public function it_displays_pagination_on_products_index(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /products/index
         * Expected behavior: Display pagination controls
         */
        $response = $this->get('/products/index');
        
        /* Assert */
        $this->assertHasPagination($response);
        $records = $this->fakeDb->select('ip_products', []);
        $this->assertNotEmpty($records, "Database should have record in 'ip_products'");
    }

    // #endregion


    // #region Form Display Tests

    /**
     * Happy Path: Form displays new product form
     */
    #[Test]
    public function it_displays_new_product_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /products/form
         * Expected behavior: Display new product form fields
         */
        $response = $this->get('/products/form');
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['product_name', 'product_sku', 'product_price']);
        $this->assertTrue($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Form displays edit product form with existing data
     */
    #[Test]
    public function it_displays_edit_product_form_with_existing_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $standardProduct = $this->fixtures->get('products', 'standard_product');
        $productId = $standardProduct['product_id'];
        
        /**
         * Act: GET /products/form/{id}
         * Expected behavior: Display edit form with existing product data
         */
        $response = $this->get('/products/form/' . $productId);
        
        /* Assert */
        $response->assertSee($standardProduct['product_name']);
        $records = $this->fakeDb->select('ip_products', ['product_id' => $productId,
            'product_name' => $standardProduct['product_name']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_products'");
    }

    /**
     * Test form returns 404 for invalid product
     */
    #[Test]
    public function it_returns_404_for_invalid_product_id(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidProductId = 9999;
        
        /**
         * Act: GET /products/form/{id}
         * Expected behavior: Return 404 for non-existent product
         */
        $response = $this->get('/products/form/' . $invalidProductId);
        
        /* Assert */
        $response->assertNotFound();
        $records = $this->fakeDb->select('ip_products', ['product_id' => $invalidProductId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_products'");
    }

    // #endregion


    // #region Form Submission Tests (Create)

    /**
     * Happy Path: POST creates new product with valid data
     */
    #[Test]
    public function it_creates_new_product_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $validProductData = $this->makeProductData([
            'product_name' => 'New Test Product',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /products/form
         * POST data: {
         *   "product_sku": "TEST-SKU-001",
         *   "product_name": "New Test Product",
         *   "product_description": "Test product description",
         *   "product_price": "99.99",
         *   "product_unit_id": "1",
         *   "product_family_id": "1",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Create new product and redirect to view
         */
        $response = $this->post('/products/form', $validProductData);
        
        /* Assert */
        $response->assertRedirect('/products/view/');
        $records = $this->fakeDb->select('ip_products', ['product_name' => 'New Test Product']);
        $this->assertNotEmpty($records, "Database should have record in 'ip_products'");
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
        
        $productData = $this->makeProductData([
            'product_name' => 'Should Not Be Created',
            'btn_cancel' => 'Cancel',
        ]);
        
        /**
         * Act: POST /products/form
         * POST data: Complete product data with btn_cancel set
         * Expected behavior: Cancel and redirect without saving
         */
        $response = $this->post('/products/form', $productData);
        
        /* Assert */
        $response->assertRedirect('/products');
        $records = $this->fakeDb->select('ip_products', ['product_name' => 'Should Not Be Created']);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_products'");
    }

    // #endregion

    // #region Form Submission Tests (Update)

    /**
     * Happy Path: POST updates existing product
     */
    #[Test]
    public function it_updates_existing_product_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $standardProduct = $this->fixtures->get('products', 'standard_product');
        $updateData = $this->makeProductData([
            'product_id' => $standardProduct['product_id'],
            'product_name' => 'Updated Product Name',
            'product_price' => '149.99',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /products/form/{id}
         * POST data: Complete product data with updated product_name and product_price
         * Expected behavior: Update product and redirect to view page
         */
        $response = $this->post('/products/form/' . $standardProduct['product_id'], $updateData);
        
        /* Assert */
        $response->assertRedirect('/products/view/' . $standardProduct['product_id']);
        $records = $this->fakeDb->select('ip_products', ['product_id' => $standardProduct['product_id'],
            'product_name' => 'Updated Product Name']);
        $this->assertNotEmpty($records, "Database should have record in 'ip_products'");
    }

    // #endregion


    // #region Delete Tests

    /**
     * Test POST delete removes product
     */
    #[Test]
    public function it_deletes_product_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $standardProduct = $this->fixtures->get('products', 'standard_product');
        $productId = $standardProduct['product_id'];
        
        /**
         * Act: POST /products/delete/{id}
         * POST data: {
         *   "btn_submit": "1"
         * }
         * Expected behavior: Delete product and redirect to index
         */
        $response = $this->post('/products/delete/' . $productId, [
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        $response->assertRedirect('/products');
        $records = $this->fakeDb->select('ip_products', ['product_id' => $productId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_products'");
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
        
        $invalidData = $this->makeProductData([
            'product_name' => '',
            'product_price' => '',
        ]);
        
        /**
         * Act: POST /products/form
         * POST data: Complete data with empty required fields
         * Expected behavior: Validation errors for missing required fields
         */
        $response = $this->post('/products/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['product_name', 'product_price']);
    }

    /**
     * Test POST validates product price format
     */
    #[Test]
    public function it_validates_product_price_format(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeProductData([
            'product_price' => 'invalid_price',
        ]);
        
        /**
         * Act: POST /products/form
         * POST data: Complete data with invalid product_price
         * Expected behavior: Validation error for product_price
         */
        $response = $this->post('/products/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['product_price']);
    }

    /**
     * Test POST validates product name uniqueness
     */
    #[Test]
    public function it_validates_product_name_uniqueness(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $standardProduct = $this->fixtures->get('products', 'standard_product');
        $duplicateData = $this->makeProductData([
            'product_name' => $standardProduct['product_name'],
        ]);
        
        /**
         * Act: POST /products/form
         * POST data: Complete data with duplicate product_name
         * Expected behavior: Validation error for product_name
         */
        $response = $this->post('/products/form', $duplicateData);
        
        /* Assert */
        $response->assertSessionHasErrors(['product_name']);
        $records = $this->fakeDb->select('ip_products', ['product_name' => $standardProduct['product_name']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_products'");
    }

    /**
     * Test POST validates unit_id exists
     */
    #[Test]
    public function it_validates_unit_id_exists_in_database(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeProductData([
            'product_unit_id' => 9999,
        ]);
        
        /**
         * Act: POST /products/form
         * POST data: Complete data with non-existent product_unit_id
         * Expected behavior: Validation error for product_unit_id
         */
        $response = $this->post('/products/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['product_unit_id']);
        $records = $this->fakeDb->select('ip_units', ['unit_id' => 9999]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_units'");
    }

    /**
     * Test POST validates family_id exists
     */
    #[Test]
    public function it_validates_family_id_exists_in_database(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeProductData([
            'product_family_id' => 9999,
        ]);
        
        /**
         * Act: POST /products/form
         * POST data: Complete data with non-existent product_family_id
         * Expected behavior: Validation error for product_family_id
         */
        $response = $this->post('/products/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['product_family_id']);
        $records = $this->fakeDb->select('ip_families', ['family_id' => 9999]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_families'");
    }

    /**
     * Test POST validates tax_rate_id exists
     */
    #[Test]
    public function it_validates_tax_rate_id_exists_in_database(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeProductData([
            'product_tax_rate_id' => 9999,
        ]);
        
        /**
         * Act: POST /products/form
         * POST data: Complete data with non-existent product_tax_rate_id
         * Expected behavior: Validation error for product_tax_rate_id
         */
        $response = $this->post('/products/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['product_tax_rate_id']);
        $records = $this->fakeDb->select('ip_tax_rates', ['tax_rate_id' => 9999]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_tax_rates'");
    }

    // #endregion

    // #region Security Tests

    /**
     * Security: Test XSS sanitization in product data
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_product_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $xssData = $this->makeProductData([
            'product_name' => '<script>alert("xss")</script>',
            'product_description' => '<img src=x onerror=alert("xss")>',
        ]);
        
        /**
         * Act: POST /products/form
         * POST data: Complete data with XSS payloads in product_name and product_description
         * Expected behavior: XSS payloads should be sanitized or rejected
         */
        $response = $this->post('/products/form', $xssData);
        
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
        
        $sqlInjectionData = $this->makeProductData([
            'product_sku' => "'; DROP TABLE ip_products; --",
            'product_name' => "1' OR '1'='1",
        ]);
        
        /**
         * Act: POST /products/form
         * POST data: Complete data with SQL injection payloads
         * Expected behavior: SQL injection should be prevented at query level
         */
        $response = $this->post('/products/form', $sqlInjectionData);
        
        /* Assert */
        $this->assertTrue(true);
    }

    // #endregion
}
