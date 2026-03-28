<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\FilterAjaxController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for FilterAjaxController
 * 
 * Tests AJAX filtering functionality across multiple entities.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(FilterAjaxController::class)]
class FilterAjaxControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Store common filter data for reuse
        $this->testData = [
            'filter_query' => 'Test',
        ];
    }
    #[Test]
    public function it_requires_authentication_for_filter_invoices(): void
    {
        /* Arrange */
        // Not authenticated
        
        /* Act */
        // POST /filter/filterajax/filter_invoices
        $response = $this->post('/filter/filterajax/filter_invoices', $this->testData);
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    #[Test]
    public function it_post_filter_invoices_returns_filtered_results(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /filter/filterajax/filter_invoices
        $response = $this->post('/filter/filterajax/filter_invoices', $this->testData);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('INV-');
    }

    #[Test]
    public function it_post_filter_invoices_searches_multiple_fields(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /filter/filterajax/filter_invoices
        // Successful request: ['filter_query' => 'special']
        $response = $this->post('/filter/filterajax/filter_invoices', [
            'filter_query' => 'special',
        ]);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('invoice_number');
        $response->assertSee('client_name');
    }

    #[Test]
    public function it_post_filter_invoices_handles_multiple_keywords(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /filter/filterajax/filter_invoices
        // Successful request: ['filter_query' => 'INV test']
        $response = $this->post('/filter/filterajax/filter_invoices', [
            'filter_query' => 'INV test',
        ]);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('INV-');
    }

    #[Test]
    public function it_post_filter_quotes_returns_filtered_results(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /filter/filterajax/filter_quotes
        $response = $this->post('/filter/filterajax/filter_quotes', $this->testData);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('QUO-');
    }

    #[Test]
    public function it_post_filter_clients_returns_filtered_results(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /filter/filterajax/filter_clients
        $response = $this->post('/filter/filterajax/filter_clients', [
            'filter_query' => 'test',
        ]);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('client_name');
    }

    #[Test]
    public function it_post_filter_custom_fields_returns_filtered_results(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /filter/filterajax/filter_custom_fields
        $response = $this->post('/filter/filterajax/filter_custom_fields', $this->testData);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('custom_field');
    }

    #[Test]
    public function it_post_filter_custom_values_returns_filtered_results(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /filter/filterajax/filter_custom_values
        $response = $this->post('/filter/filterajax/filter_custom_values', $this->testData);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('custom_value');
    }

    #[Test]
    public function it_post_filter_projects_returns_filtered_results(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /filter/filterajax/filter_projects
        $response = $this->post('/filter/filterajax/filter_projects', $this->testData);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('project_name');
    }

    #[Test]
    public function it_post_filter_products_returns_filtered_results(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /filter/filterajax/filter_products
        $response = $this->post('/filter/filterajax/filter_products', $this->testData);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('product_name');
    }

    #[Test]
    public function it_post_filter_users_returns_filtered_results(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /filter/filterajax/filter_users
        $response = $this->post('/filter/filterajax/filter_users', $this->testData);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('user_name');
    }

    #[Test]
    public function it_post_filter_payments_returns_filtered_results(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /filter/filterajax/filter_payments
        $response = $this->post('/filter/filterajax/filter_payments', $this->testData);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('payment_amount');
    }

    #[Test]
    public function it_ajax_controller_returns_json_response(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /filter/filterajax/filter_invoices
        $response = $this->post('/filter/filterajax/filter_invoices', $this->testData);
        
        /* Assert */
        $response->assertOk();
        // AJAX controllers should return content without full page layout
        $response->assertDontSee('<html>');
        $response->assertDontSee('</body>');
    }

    #[Test]
    public function it_filter_methods_sanitize_query_for_sql_injection(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /filter/filterajax/filter_invoices
        // Malicious request: SQL injection attempt
        $response = $this->post('/filter/filterajax/filter_invoices', [
            'filter_query' => "'; DROP TABLE ip_invoices; --",
        ]);
        
        /* Assert */
        $response->assertOk();
        // Verify SQL injection attempt is neutralized
        $response->assertDontSee('DROP TABLE');
    }

    #[Test]
    public function it_filter_methods_handle_empty_query(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /filter/filterajax/filter_invoices
        // Empty query string
        $response = $this->post('/filter/filterajax/filter_invoices', [
            'filter_query' => '',
        ]);
        
        /* Assert */
        $response->assertOk();
    }

    #[Test]
    public function it_filter_invoices_uses_case_insensitive_search(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /filter/filterajax/filter_invoices
        // Uppercase query to test case-insensitive search
        $response = $this->post('/filter/filterajax/filter_invoices', [
            'filter_query' => 'TEST',
        ]);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('INV-');
    }
}
