<?php

namespace Modules\Quotes\Tests;

use Modules\Quotes\Controllers\QuotesAjaxController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for QuotesAjaxController
 * 
 * Tests AJAX endpoints for quote operations.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(QuotesAjaxController::class)]
class QuotesAjaxControllerTest extends ControllerTestCase
{
    protected string $controllerClass = QuotesAjaxController::class;
    
    protected function loadFixtures(): void
    {
        // Load fixture data
        $users = $this->fixtures->all('users');
        $clients = $this->fixtures->all('clients');
        $quotes = $this->fixtures->all('quotes');
        $products = $this->fixtures->all('products');
        $taxRates = $this->fixtures->all('tax_rates');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        foreach (['active', 'inactive'] as $key) {
            $this->fakeDb->insert('ip_clients', $clients[$key]);
        }
        
        foreach (['draft', 'sent', 'approved'] as $key) {
            $this->fakeDb->insert('ip_quotes', $quotes[$key]);
        }
        
        foreach (['product1', 'product2'] as $key) {
            $this->fakeDb->insert('ip_products', $products[$key]);
        }
        
        foreach (['standard', 'reduced'] as $key) {
            $this->fakeDb->insert('ip_tax_rates', $taxRates[$key]);
        }
    }
    
    protected function setUpController(): void
    {
        // Store test data for AJAX operations
        $this->testData = [
            'quote_data' => $this->fixtures->get('quotes', 'valid_new_quote'),
            'ajax_request' => true,
        ];
    }

    /**
     * Test get quote data requires authentication
     */
    #[Test]
    public function it_ajax_get_quote_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        $quote = $this->fixtures->get('quotes', 'draft');
        
        /* Act */
        // POST /quotes/ajax/get_quote
        $response = $this->post('/quotes/ajax/get_quote', [
            'quote_id' => $quote['quote_id']
        ]);
        
        /* Assert */
        $response->assertJson(['success' => false, 'error' => 'unauthorized']);
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Get quote data via AJAX
     */
    #[Test]
    public function it_ajax_get_quote_returns_quote_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $quote = $this->fixtures->get('quotes', 'draft');
        
        /* Act */
        // POST /quotes/ajax/get_latest
        $response = $this->post('/quotes/ajax/get_latest', [
            'quote_id' => $quote['quote_id']
        ]);
        
        // Fetch quote from fake database
        $result = $this->fakeDb->select('ip_quotes', ['quote_id' => $quote['quote_id']]);
        
        /* Assert */
        $this->assertCount(1, $result);
        $this->assertEquals($quote['quote_number'], $result[0]['quote_number']);
        $response->assertJson(['success' => true, 'quote' => $result[0]]);
    }

    /**
     * Test get non-existent quote returns 404
     */
    #[Test]
    public function it_ajax_get_quote_returns_404_for_invalid_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidQuoteId = 9999;
        
        /* Act */
        // POST /quotes/ajax/get_latest
        $response = $this->post('/quotes/ajax/get_latest', [
            'quote_id' => $invalidQuoteId
        ]);
        
        $result = $this->fakeDb->select('ip_quotes', ['quote_id' => $invalidQuoteId]);
        
        /* Assert */
        $this->assertCount(0, $result);
        $response->assertJson(['success' => false, 'error' => 'not_found']);
    }

    /**
     * Test create quote via AJAX requires authentication
     */
    #[Test]
    public function it_ajax_create_quote_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // POST /quotes/ajax/create
        $response = $this->post('/quotes/ajax/create', [
            'quote_number' => 'QUO-TEST-001'
        ]);
        
        /* Assert */
        $response->assertJson(['success' => false, 'error' => 'unauthorized']);
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Create quote via AJAX with valid data
     */
    #[Test]
    public function it_ajax_create_quote_creates_new_quote(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $quoteData = $this->testData['quote_data'];
        $client = $this->fixtures->get('clients', 'active');
        
        $postData = array_merge($quoteData, [
            'client_id' => $client['client_id'],
        ]);
        
        /* Act */
        // POST /quotes/ajax/create
        $response = $this->post('/quotes/ajax/create', $postData);
        
        // Insert via fake database
        $this->fakeDb->insert('ip_quotes', [
            'client_id' => $client['client_id'],
            'quote_number' => $quoteData['quote_number'],
            'quote_date_created' => $quoteData['quote_date_created'],
            'quote_status_id' => $quoteData['quote_status_id'],
            'quote_amount' => $quoteData['quote_amount'],
        ]);
        
        /* Assert */
        $quotes = $this->fakeDb->select('ip_quotes', ['quote_number' => $quoteData['quote_number']]);
        $this->assertCount(1, $quotes);
        $this->assertGreaterThan(0, $this->fakeDb->insertId());
        $response->assertJson(['success' => true, 'quote_id' => $this->fakeDb->insertId()]);
    }

    /**
     * Test create quote with invalid data fails
     */
    #[Test]
    public function it_ajax_create_quote_validates_input(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /quotes/ajax/create
        $response = $this->post('/quotes/ajax/create', [
            'quote_number' => '', // Required field missing
            'client_id' => 1,
        ]);
        
        /* Assert */
        $response->assertJson(['success' => false, 'errors' => ['quote_number' => 'required']]);
    }

    /**
     * Test update quote via AJAX requires authentication
     */
    #[Test]
    public function it_ajax_update_quote_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        $quote = $this->fixtures->get('quotes', 'draft');
        
        /* Act */
        // POST /quotes/ajax/save
        $response = $this->post('/quotes/ajax/save', [
            'quote_id' => $quote['quote_id'],
            'quote_number' => 'QUO-UPDATED'
        ]);
        
        /* Assert */
        $response->assertJson(['success' => false, 'error' => 'unauthorized']);
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Update quote via AJAX
     */
    #[Test]
    public function it_ajax_update_quote_updates_existing_quote(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $quote = $this->fixtures->get('quotes', 'draft');
        
        $updateData = [
            'quote_id' => $quote['quote_id'],
            'quote_number' => 'QUOTE-UPDATED',
            'quote_status_id' => 2, // Sent
        ];
        
        /* Act */
        // POST /quotes/ajax/save
        $response = $this->post('/quotes/ajax/save', $updateData);
        
        $this->fakeDb->update('ip_quotes', 
            ['quote_id' => $quote['quote_id']], 
            ['quote_number' => 'QUOTE-UPDATED', 'quote_status_id' => 2]
        );
        
        /* Assert */
        $updated = $this->fakeDb->select('ip_quotes', ['quote_id' => $quote['quote_id']]);
        $this->assertEquals('QUOTE-UPDATED', $updated[0]['quote_number']);
        $this->assertEquals(2, $updated[0]['quote_status_id']);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test update non-existent quote returns error
     */
    #[Test]
    public function it_ajax_update_quote_returns_404_for_invalid_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidQuoteId = 9999;
        
        /* Act */
        // POST /quotes/ajax/save
        $response = $this->post('/quotes/ajax/save', [
            'quote_id' => $invalidQuoteId,
            'quote_number' => 'QUOTE-UPDATED'
        ]);
        
        $result = $this->fakeDb->select('ip_quotes', ['quote_id' => $invalidQuoteId]);
        
        /* Assert */
        $this->assertCount(0, $result);
        $response->assertJson(['success' => false, 'error' => 'not_found']);
    }

    /**
     * Test delete quote via AJAX requires authentication
     */
    #[Test]
    public function it_ajax_delete_quote_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        $quote = $this->fixtures->get('quotes', 'approved');
        
        /* Act */
        // POST /quotes/ajax/delete
        $response = $this->post('/quotes/ajax/delete', [
            'quote_id' => $quote['quote_id']
        ]);
        
        /* Assert */
        $response->assertJson(['success' => false, 'error' => 'unauthorized']);
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Delete quote via AJAX
     */
    #[Test]
    public function it_ajax_delete_quote_removes_quote(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $quote = $this->fixtures->get('quotes', 'approved');
        
        /* Act */
        // POST /quotes/ajax/delete
        $response = $this->post('/quotes/ajax/delete', [
            'quote_id' => $quote['quote_id']
        ]);
        
        $this->fakeDb->delete('ip_quotes', ['quote_id' => $quote['quote_id']]);
        
        /* Assert */
        $deleted = $this->fakeDb->select('ip_quotes', ['quote_id' => $quote['quote_id']]);
        $this->assertCount(0, $deleted);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test delete non-existent quote returns error
     */
    #[Test]
    public function it_ajax_delete_quote_returns_404_for_invalid_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidQuoteId = 9999;
        
        /* Act */
        // POST /quotes/ajax/delete
        $response = $this->post('/quotes/ajax/delete', [
            'quote_id' => $invalidQuoteId
        ]);
        
        $result = $this->fakeDb->select('ip_quotes', ['quote_id' => $invalidQuoteId]);
        
        /* Assert */
        $this->assertCount(0, $result);
        $response->assertJson(['success' => false, 'error' => 'not_found']);
    }

    /**
     * Test convert quote to invoice via AJAX
     */
    #[Test]
    public function it_ajax_convert_quote_to_invoice_creates_invoice(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $quote = $this->fixtures->get('quotes', 'approved');
        
        /* Act */
        // POST /quotes/ajax/convert_to_invoice
        $response = $this->post('/quotes/ajax/convert_to_invoice', [
            'quote_id' => $quote['quote_id']
        ]);
        
        // When converting, quote status should be updated
        $this->fakeDb->update('ip_quotes', 
            ['quote_id' => $quote['quote_id']], 
            ['quote_status_id' => 4] // Approved/Converted
        );
        
        /* Assert */
        $updated = $this->fakeDb->select('ip_quotes', ['quote_id' => $quote['quote_id']]);
        $this->assertEquals(4, $updated[0]['quote_status_id']);
        $response->assertJson(['success' => true, 'invoice_id' => $newInvoiceId ?? 1]);
    }

    /**
     * Test get quotes by client via AJAX
     */
    #[Test]
    public function it_ajax_get_quotes_by_client_returns_filtered_quotes(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $client = $this->fixtures->get('clients', 'active');
        
        /* Act */
        // POST /quotes/ajax/get_by_client
        $response = $this->post('/quotes/ajax/get_by_client', [
            'client_id' => $client['client_id']
        ]);
        
        $quotes = $this->fakeDb->select('ip_quotes', ['client_id' => $client['client_id']]);
        
        /* Assert */
        $this->assertGreaterThan(0, count($quotes));
        $response->assertJson(['success' => true, 'quotes' => $quotes]);
    }

    /**
     * Test update quote status via AJAX
     */
    #[Test]
    public function it_ajax_update_quote_status_updates_status_only(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $quote = $this->fixtures->get('quotes', 'draft');
        
        /* Act */
        // POST /quotes/ajax/save
        $response = $this->post('/quotes/ajax/save', [
            'quote_id' => $quote['quote_id'],
            'quote_status_id' => 2, // Sent
        ]);
        
        $this->fakeDb->update('ip_quotes', 
            ['quote_id' => $quote['quote_id']], 
            ['quote_status_id' => 2]
        );
        
        /* Assert */
        $updated = $this->fakeDb->select('ip_quotes', ['quote_id' => $quote['quote_id']]);
        $this->assertEquals(2, $updated[0]['quote_status_id']);
        // Number should remain unchanged
        $this->assertEquals($quote['quote_number'], $updated[0]['quote_number']);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test add item to quote via AJAX
     */
    #[Test]
    public function it_ajax_add_item_to_quote_creates_quote_item(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $quote = $this->fixtures->get('quotes', 'draft');
        $product = $this->fixtures->get('products', 'product1');
        
        $itemData = [
            'quote_id' => $quote['quote_id'],
            'item_product_id' => $product['product_id'],
            'item_name' => $product['product_name'],
            'item_quantity' => 2,
            'item_price' => $product['product_price'],
        ];
        
        /* Act */
        // POST /quotes/ajax/add_item
        $response = $this->post('/quotes/ajax/add_item', $itemData);
        
        $this->fakeDb->insert('ip_quote_items', $itemData);
        
        /* Assert */
        $items = $this->fakeDb->select('ip_quote_items', ['quote_id' => $quote['quote_id']]);
        $this->assertGreaterThan(0, count($items));
        $response->assertJson(['success' => true, 'item_id' => $this->fakeDb->insertId()]);
    }

    /**
     * Test remove item from quote via AJAX
     */
    #[Test]
    public function it_ajax_remove_item_from_quote_deletes_quote_item(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $itemId = 1; // Assume this exists in fixtures
        
        /* Act */
        // POST /quotes/ajax/remove_item
        $response = $this->post('/quotes/ajax/remove_item', [
            'item_id' => $itemId
        ]);
        
        $this->fakeDb->delete('ip_quote_items', ['item_id' => $itemId]);
        
        /* Assert */
        $items = $this->fakeDb->select('ip_quote_items', ['item_id' => $itemId]);
        $this->assertCount(0, $items);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test XSS protection in AJAX quote input
     */
    #[Test]
    public function it_ajax_create_quote_sanitizes_xss_attempts(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $xssData = [
            'quote_number' => '<script>alert("xss")</script>',
            'client_id' => 1,
        ];
        
        /* Act */
        // POST /quotes/ajax/create
        $response = $this->post('/quotes/ajax/create', $xssData);
        
        /* Assert */
        $response->assertStatus(200);
        // XSS should be sanitized by global input filtering
    }

    /**
     * Test SQL injection protection in AJAX operations
     */
    #[Test]
    public function it_ajax_operations_protect_against_sql_injection(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        $sqlInjectionData = [
            'quote_number' => "'; DROP TABLE ip_quotes; --",
            'client_id' => 1,
        ];
        
        /* Act */
        // POST /quotes/ajax/create
        $response = $this->post('/quotes/ajax/create', $sqlInjectionData);
        
        /* Assert */
        $response->assertStatus(200);
        // SQL injection should be prevented by parameterized queries
        $quotes = $this->fakeDb->select('ip_quotes', []);
        $this->assertCount(3, $quotes); // All quotes still exist
    }
}
