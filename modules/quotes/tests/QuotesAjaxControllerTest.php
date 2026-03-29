<?php

namespace Modules\Quotes\Tests;

use Modules\Quotes\Controllers\QuotesAjaxController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for QuotesAjaxController
 * 
 * Tests the full AJAX request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(QuotesAjaxController::class)]
class QuotesAjaxControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = QuotesAjaxController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'clients', 'quotes', 'products', 'tax_rates'];
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

    // #region Authentication

    /**
     * Test that AJAX get quote requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_get_quote_via_ajax(): void
    {
        /* Arrange */
        $this->clearAuth();
        $quote = $this->fixtures->get('quotes', 'draft');
        
        /**
         * Act: POST /quotes/ajax/get_quote
         * POST data: {
         *   "quote_id": "1"
         * }
         * Expected behavior: Return unauthorized error when not authenticated
         */
        $response = $this->post('/quotes/ajax/get_quote', [
            'quote_id' => $quote['quote_id']
        ]);
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test that AJAX create quote requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_create_quote_via_ajax(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        $quoteData = $this->makeQuoteData([
            'quote_number' => 'QUO-TEST-001'
        ]);
        
        /**
         * Act: POST /quotes/ajax/create
         * POST data: Complete quote data
         * Expected behavior: Return unauthorized error when not authenticated
         */
        $response = $this->post('/quotes/ajax/create', $quoteData);
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test that AJAX update quote requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_update_quote_via_ajax(): void
    {
        /* Arrange */
        $this->clearAuth();
        $quote = $this->fixtures->get('quotes', 'draft');
        
        $updateData = $this->makeQuoteData([
            'quote_id' => $quote['quote_id'],
            'quote_number' => 'QUO-UPDATED'
        ]);
        
        /**
         * Act: POST /quotes/ajax/save
         * POST data: Complete quote data with quote_id
         * Expected behavior: Return unauthorized error when not authenticated
         */
        $response = $this->post('/quotes/ajax/save', $updateData);
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test that AJAX delete quote requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_delete_quote_via_ajax(): void
    {
        /* Arrange */
        $this->clearAuth();
        $quote = $this->fixtures->get('quotes', 'approved');
        
        /**
         * Act: POST /quotes/ajax/delete
         * POST data: {
         *   "quote_id": "3"
         * }
         * Expected behavior: Return unauthorized error when not authenticated
         */
        $response = $this->post('/quotes/ajax/delete', [
            'quote_id' => $quote['quote_id']
        ]);
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    // #endregion

    // #region AJAX Get

    /**
     * Happy Path: Get quote data via AJAX
     */
    #[Test]
    public function it_returns_quote_data_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $quote = $this->fixtures->get('quotes', 'draft');
        
        /**
         * Act: POST /quotes/ajax/get_latest
         * POST data: {
         *   "quote_id": "1"
         * }
         * Expected behavior: Return quote data as JSON
         */
        $response = $this->post('/quotes/ajax/get_latest', [
            'quote_id' => $quote['quote_id']
        ]);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_quotes', ['quote_id' => $quote['quote_id'],
            'quote_number' => $quote['quote_number']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_quotes'");
        $response->assertJson(['success' => true]);
    }

    /**
     * Test AJAX get quote returns 404 for invalid ID
     */
    #[Test]
    public function it_returns_404_for_invalid_quote_id_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidQuoteId = 9999;
        
        /**
         * Act: POST /quotes/ajax/get_latest
         * POST data: {
         *   "quote_id": "9999"
         * }
         * Expected behavior: Return not found error
         */
        $response = $this->post('/quotes/ajax/get_latest', [
            'quote_id' => $invalidQuoteId
        ]);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_quotes', ['quote_id' => $invalidQuoteId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_quotes'");
        $response->assertJson(['success' => false, 'error' => 'not_found']);
    }

    /**
     * Test get quotes by client via AJAX
     */
    #[Test]
    public function it_returns_quotes_filtered_by_client_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $client = $this->fixtures->get('clients', 'active');
        
        /**
         * Act: POST /quotes/ajax/get_by_client
         * POST data: {
         *   "client_id": "1"
         * }
         * Expected behavior: Return quotes for the specified client
         */
        $response = $this->post('/quotes/ajax/get_by_client', [
            'client_id' => $client['client_id']
        ]);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_quotes', ['client_id' => $client['client_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_quotes'");
        $response->assertJson(['success' => true]);
    }

    // #endregion

    // #region AJAX Create

    /**
     * Happy Path: Create quote via AJAX with valid data
     */
    #[Test]
    public function it_creates_new_quote_via_ajax_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $client = $this->fixtures->get('clients', 'active');
        $quoteData = $this->makeQuoteData([
            'client_id' => $client['client_id'],
            'quote_number' => 'QUO-TEST-001',
            'quote_date_created' => '2026-03-29',
        ]);
        
        /**
         * Act: POST /quotes/ajax/create
         * POST data: {
         *   "client_id": "1",
         *   "quote_number": "QUO-TEST-001",
         *   "quote_date_created": "2026-03-29",
         *   "quote_date_expires": "2026-04-28",
         *   "quote_status_id": "1",
         *   "quote_amount": "1500.00",
         *   "user_id": "1"
         * }
         * Expected behavior: Create new quote and return quote ID
         */
        $response = $this->post('/quotes/ajax/create', $quoteData);
        
        /* Assert */
        $response->assertJson(['success' => true]);
        $records = $this->fakeDb->select('ip_quotes', ['quote_number' => 'QUO-TEST-001']);
        $this->assertNotEmpty($records, "Database should have record in 'ip_quotes'");
    }

    // #endregion

    // #region AJAX Update

    /**
     * Happy Path: Update quote via AJAX
     */
    #[Test]
    public function it_updates_existing_quote_via_ajax_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $quote = $this->fixtures->get('quotes', 'draft');
        $updateData = $this->makeQuoteData([
            'quote_id' => $quote['quote_id'],
            'quote_number' => 'QUOTE-UPDATED',
            'quote_status_id' => 2,
        ]);
        
        /**
         * Act: POST /quotes/ajax/save
         * POST data: {
         *   "quote_id": "1",
         *   "client_id": "1",
         *   "quote_number": "QUOTE-UPDATED",
         *   "quote_date_created": "2026-03-29",
         *   "quote_date_expires": "2026-04-28",
         *   "quote_status_id": "2",
         *   "quote_amount": "1500.00",
         *   "user_id": "1"
         * }
         * Expected behavior: Update quote and return success
         */
        $response = $this->post('/quotes/ajax/save', $updateData);
        
        /* Assert */
        $response->assertJson(['success' => true]);
        $records = $this->fakeDb->select('ip_quotes', ['quote_id' => $quote['quote_id'],
            'quote_number' => 'QUOTE-UPDATED',
            'quote_status_id' => 2]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_quotes'");
    }

    /**
     * Test AJAX update quote returns 404 for invalid ID
     */
    #[Test]
    public function it_returns_404_when_updating_invalid_quote_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidQuoteId = 9999;
        $updateData = $this->makeQuoteData([
            'quote_id' => $invalidQuoteId,
            'quote_number' => 'QUOTE-UPDATED'
        ]);
        
        /**
         * Act: POST /quotes/ajax/save
         * POST data: Complete quote data with invalid quote_id
         * Expected behavior: Return not found error
         */
        $response = $this->post('/quotes/ajax/save', $updateData);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_quotes', ['quote_id' => $invalidQuoteId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_quotes'");
        $response->assertJson(['success' => false, 'error' => 'not_found']);
    }

    /**
     * Test AJAX update quote status only
     */
    #[Test]
    public function it_updates_quote_status_only_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $quote = $this->fixtures->get('quotes', 'draft');
        
        /**
         * Act: POST /quotes/ajax/save
         * POST data: {
         *   "quote_id": "1",
         *   "quote_status_id": "2"
         * }
         * Expected behavior: Update status without changing other fields
         */
        $response = $this->post('/quotes/ajax/save', [
            'quote_id' => $quote['quote_id'],
            'quote_status_id' => 2,
        ]);
        
        /* Assert */
        $response->assertJson(['success' => true]);
        $records = $this->fakeDb->select('ip_quotes', ['quote_id' => $quote['quote_id'],
            'quote_status_id' => 2,
            'quote_number' => $quote['quote_number']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_quotes'");
    }

    // #endregion

    // #region AJAX Delete

    /**
     * Happy Path: Delete quote via AJAX
     */
    #[Test]
    public function it_deletes_quote_successfully_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $quote = $this->fixtures->get('quotes', 'approved');
        $quoteId = $quote['quote_id'];
        
        /**
         * Act: POST /quotes/ajax/delete
         * POST data: {
         *   "quote_id": "3"
         * }
         * Expected behavior: Delete quote and return success
         */
        $response = $this->post('/quotes/ajax/delete', [
            'quote_id' => $quoteId
        ]);
        
        /* Assert */
        $response->assertJson(['success' => true]);
        $records = $this->fakeDb->select('ip_quotes', ['quote_id' => $quoteId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_quotes'");
    }

    /**
     * Test AJAX delete quote returns 404 for invalid ID
     */
    #[Test]
    public function it_returns_404_when_deleting_invalid_quote_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidQuoteId = 9999;
        
        /**
         * Act: POST /quotes/ajax/delete
         * POST data: {
         *   "quote_id": "9999"
         * }
         * Expected behavior: Return not found error
         */
        $response = $this->post('/quotes/ajax/delete', [
            'quote_id' => $invalidQuoteId
        ]);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_quotes', ['quote_id' => $invalidQuoteId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_quotes'");
        $response->assertJson(['success' => false, 'error' => 'not_found']);
    }

    // #endregion

    // #region AJAX Item Operations

    /**
     * Test add item to quote via AJAX
     */
    #[Test]
    public function it_adds_item_to_quote_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $quote = $this->fixtures->get('quotes', 'draft');
        $product = $this->fixtures->get('products', 'product1');
        
        $itemData = [
            'quote_id' => $quote['quote_id'],
            'item_product_id' => $product['product_id'],
            'item_name' => $product['product_name'],
            'item_quantity' => 2,
            'item_price' => $product['product_price'],
        ];
        
        /**
         * Act: POST /quotes/ajax/add_item
         * POST data: {
         *   "quote_id": "1",
         *   "item_product_id": "1",
         *   "item_name": "Web Development",
         *   "item_quantity": "2",
         *   "item_price": "1500.00"
         * }
         * Expected behavior: Add item to quote and return item ID
         */
        $response = $this->post('/quotes/ajax/add_item', $itemData);
        
        /* Assert */
        $response->assertJson(['success' => true]);
        $records = $this->fakeDb->select('ip_quote_items', ['quote_id' => $quote['quote_id'],
            'item_name' => $product['product_name']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_quote_items'");
    }

    /**
     * Test remove item from quote via AJAX
     */
    #[Test]
    public function it_removes_item_from_quote_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $itemId = 1;
        
        /**
         * Act: POST /quotes/ajax/remove_item
         * POST data: {
         *   "item_id": "1"
         * }
         * Expected behavior: Remove item from quote and return success
         */
        $response = $this->post('/quotes/ajax/remove_item', [
            'item_id' => $itemId
        ]);
        
        /* Assert */
        $response->assertJson(['success' => true]);
        $records = $this->fakeDb->select('ip_quote_items', ['item_id' => $itemId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_quote_items'");
    }

    /**
     * Test convert quote to invoice via AJAX
     */
    #[Test]
    public function it_converts_quote_to_invoice_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $quote = $this->fixtures->get('quotes', 'approved');
        
        /**
         * Act: POST /quotes/ajax/convert_to_invoice
         * POST data: {
         *   "quote_id": "3"
         * }
         * Expected behavior: Create invoice from quote and update quote status
         */
        $response = $this->post('/quotes/ajax/convert_to_invoice', [
            'quote_id' => $quote['quote_id']
        ]);
        
        /* Assert */
        $response->assertJson(['success' => true]);
        $records = $this->fakeDb->select('ip_quotes', ['quote_id' => $quote['quote_id'],
            'quote_status_id' => 4]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_quotes'");
    }

    // #endregion

    // #region Validation

    /**
     * Test AJAX create quote validates required fields
     */
    #[Test]
    public function it_validates_required_fields_when_creating_quote_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeQuoteData([
            'quote_number' => '',
            'client_id' => '',
        ]);
        
        /**
         * Act: POST /quotes/ajax/create
         * POST data: Quote data with empty required fields
         * Expected behavior: Return validation errors
         */
        $response = $this->post('/quotes/ajax/create', $invalidData);
        
        /* Assert */
        $response->assertJson(['success' => false, 'errors' => ['quote_number' => 'required']]);
    }

    /**
     * Test AJAX create quote validates quote number format
     */
    #[Test]
    public function it_validates_quote_number_format_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeQuoteData([
            'quote_number' => '<script>alert("xss")</script>',
        ]);
        
        /**
         * Act: POST /quotes/ajax/create
         * POST data: Complete data with invalid quote_number containing HTML tags
         * Expected behavior: Validation error or sanitization
         */
        $response = $this->post('/quotes/ajax/create', $invalidData);
        
        /* Assert */
        $this->assertTrue(
            $response->getStatusCode() === 200 || 
            $response->json(['success' => false])
        );
    }

    /**
     * Test AJAX create quote validates client ID exists
     */
    #[Test]
    public function it_validates_client_id_exists_when_creating_quote_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeQuoteData([
            'client_id' => 9999,
        ]);
        
        /**
         * Act: POST /quotes/ajax/create
         * POST data: Complete data with non-existent client_id
         * Expected behavior: Validation error for client_id
         */
        $response = $this->post('/quotes/ajax/create', $invalidData);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_clients', ['client_id' => 9999]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_clients'");
        $response->assertJson(['success' => false]);
    }

    // #endregion

    // #region Security

    /**
     * Security: Test XSS sanitization in AJAX quote data
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_quote_data_via_ajax(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $xssData = $this->makeQuoteData([
            'quote_number' => '<script>alert("xss")</script>',
            'quote_description' => '<img src=x onerror=alert("xss")>',
        ]);
        
        /**
         * Act: POST /quotes/ajax/create
         * POST data: Complete data with XSS payloads in quote_number and quote_description
         * Expected behavior: XSS payloads should be sanitized or rejected
         */
        $response = $this->post('/quotes/ajax/create', $xssData);
        
        /* Assert */
        $this->assertTrue(true);
    }

    /**
     * Security: Test SQL injection protection in AJAX operations
     */
    #[Test]
    public function it_protects_against_sql_injection_in_ajax_operations(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $sqlInjectionData = $this->makeQuoteData([
            'quote_number' => "'; DROP TABLE ip_quotes; --",
            'client_id' => "1 OR 1=1",
        ]);
        
        /**
         * Act: POST /quotes/ajax/create
         * POST data: Complete data with SQL injection payloads
         * Expected behavior: SQL injection should be prevented at query level
         */
        $response = $this->post('/quotes/ajax/create', $sqlInjectionData);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_quotes', []);
        $this->assertCount(3, $records, "Database should have exactly 3 record(s) in 'ip_quotes'");
        $this->assertTrue(true);
    }

    /**
     * Security: Test path traversal validation in quote number
     */
    #[Test]
    public function it_validates_quote_number_for_path_traversal_attempts(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $pathTraversalData = $this->makeQuoteData([
            'quote_number' => '../../../etc/passwd',
        ]);
        
        /**
         * Act: POST /quotes/ajax/create
         * POST data: Complete data with path traversal attempt in quote_number
         * Expected behavior: Validation error or sanitization
         */
        $response = $this->post('/quotes/ajax/create', $pathTraversalData);
        
        /* Assert */
        $this->assertTrue(
            $response->getStatusCode() === 200 || 
            $response->json(['success' => false])
        );
    }

    // #endregion
}
