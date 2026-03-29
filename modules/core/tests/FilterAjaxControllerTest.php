<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\FilterAjaxController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for FilterAjaxController
 * 
 * Tests AJAX filtering functionality across multiple entities.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(FilterAjaxController::class)]
class FilterAjaxControllerTest extends ControllerTestCase
{
    use LoadsFixtures, ProvidesTestData, ProvidesAssertions;
    
    protected string $controllerClass = FilterAjaxController::class;
    
    protected function fixtureTypes(): array
    {
        return ['users', 'clients', 'invoices', 'quotes', 'products', 'projects'];
    }
    
    protected function loadFixtures(): void
    {
        // Load user fixtures for authentication
        $users = $this->fixtures->all('users');
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        // Load entity fixtures for filtering tests
        $clients = $this->fixtures->all('clients');
        foreach (['active_client'] as $key) {
            $this->fakeDb->insert('ip_clients', $clients[$key]);
        }
    }
    
    protected function setUpController(): void
    {
        // Store common filter data for reuse
        $this->testData = $this->makeFilterData();
    }
    
    #region Authentication
    
    #[Test]
    public function it_requires_authentication_for_filter_invoices(): void
    {
        /* Arrange */
        // Not authenticated
        $this->clearAuth();
        
        /* Act */
        /**
         * POST /filter/filterajax/filter_invoices
         * Complete POST data: ['filter_query' => 'Test']
         */
        $response = $this->post('/filter/filterajax/filter_invoices', $this->makeFilterData());
        
        /* Assert */
        $this->assertUnauthorized($response);
    }
    
    #endregion
    
    #region AJAX Endpoints

    
    #[Test]
    public function it_filters_invoices_successfully(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        /**
         * POST /filter/filterajax/filter_invoices
         * Complete POST data: ['filter_query' => 'Test']
         */
        $response = $this->post('/filter/filterajax/filter_invoices', $this->makeFilterData());
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - AJAX Content */
        $response->assertHeader('Content-Type');
        $response->assertSee('INV-');
    }

    #[Test]
    public function it_searches_invoices_across_multiple_fields(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        /**
         * POST /filter/filterajax/filter_invoices
         * Complete POST data: ['filter_query' => 'special']
         */
        $response = $this->post('/filter/filterajax/filter_invoices', $this->makeFilterData(['filter_query' => 'special']));
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - AJAX Content */
        $response->assertHeader('Content-Type');
        $response->assertSee('invoice_number');
        $response->assertSee('client_name');
    }

    #[Test]
    public function it_handles_multiple_keywords_in_invoice_filter(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        /**
         * POST /filter/filterajax/filter_invoices
         * Complete POST data: ['filter_query' => 'INV test']
         */
        $response = $this->post('/filter/filterajax/filter_invoices', $this->makeFilterData(['filter_query' => 'INV test']));
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - AJAX Content */
        $response->assertHeader('Content-Type');
        $response->assertSee('INV-');
    }

    #[Test]
    public function it_filters_quotes_successfully(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        /**
         * POST /filter/filterajax/filter_quotes
         * Complete POST data: ['filter_query' => 'Test']
         */
        $response = $this->post('/filter/filterajax/filter_quotes', $this->makeFilterData());
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - AJAX Content */
        $response->assertHeader('Content-Type');
        $response->assertSee('QUO-');
    }

    #[Test]
    public function it_filters_clients_successfully(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        /**
         * POST /filter/filterajax/filter_clients
         * Complete POST data: ['filter_query' => 'test']
         */
        $response = $this->post('/filter/filterajax/filter_clients', $this->makeFilterData(['filter_query' => 'test']));
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - AJAX Content */
        $response->assertHeader('Content-Type');
        $response->assertSee('client_name');
    }

    #[Test]
    public function it_filters_custom_fields_successfully(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        /**
         * POST /filter/filterajax/filter_custom_fields
         * Complete POST data: ['filter_query' => 'Test']
         */
        $response = $this->post('/filter/filterajax/filter_custom_fields', $this->makeFilterData());
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - AJAX Content */
        $response->assertHeader('Content-Type');
        $response->assertSee('custom_field');
    }

    #[Test]
    public function it_filters_custom_values_successfully(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        /**
         * POST /filter/filterajax/filter_custom_values
         * Complete POST data: ['filter_query' => 'Test']
         */
        $response = $this->post('/filter/filterajax/filter_custom_values', $this->makeFilterData());
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - AJAX Content */
        $response->assertHeader('Content-Type');
        $response->assertSee('custom_value');
    }

    #[Test]
    public function it_filters_projects_successfully(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        /**
         * POST /filter/filterajax/filter_projects
         * Complete POST data: ['filter_query' => 'Test']
         */
        $response = $this->post('/filter/filterajax/filter_projects', $this->makeFilterData());
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - AJAX Content */
        $response->assertHeader('Content-Type');
        $response->assertSee('project_name');
    }

    #[Test]
    public function it_filters_products_successfully(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        /**
         * POST /filter/filterajax/filter_products
         * Complete POST data: ['filter_query' => 'Test']
         */
        $response = $this->post('/filter/filterajax/filter_products', $this->makeFilterData());
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - AJAX Content */
        $response->assertHeader('Content-Type');
        $response->assertSee('product_name');
    }

    #[Test]
    public function it_filters_users_successfully(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        /**
         * POST /filter/filterajax/filter_users
         * Complete POST data: ['filter_query' => 'Test']
         */
        $response = $this->post('/filter/filterajax/filter_users', $this->makeFilterData());
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - AJAX Content */
        $response->assertHeader('Content-Type');
        $response->assertSee('user_name');
    }

    #[Test]
    public function it_filters_payments_successfully(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        /**
         * POST /filter/filterajax/filter_payments
         * Complete POST data: ['filter_query' => 'Test']
         */
        $response = $this->post('/filter/filterajax/filter_payments', $this->makeFilterData());
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - AJAX Content */
        $response->assertHeader('Content-Type');
        $response->assertSee('payment_amount');
    }

    #[Test]
    public function it_returns_json_response_without_layout(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        /**
         * POST /filter/filterajax/filter_invoices
         * Complete POST data: ['filter_query' => 'Test']
         */
        $response = $this->post('/filter/filterajax/filter_invoices', $this->makeFilterData());
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - AJAX Content */
        $response->assertHeader('Content-Type');
        $response->assertDontSee('<html>');
        $response->assertDontSee('</body>');
    }
    
    #endregion
    
    #region Security

    #[Test]
    public function it_sanitizes_sql_injection_attempts(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        /**
         * POST /filter/filterajax/filter_invoices
         * Malicious POST data: ['filter_query' => "'; DROP TABLE ip_invoices; --"]
         */
        $response = $this->post('/filter/filterajax/filter_invoices', $this->makeFilterData([
            'filter_query' => "'; DROP TABLE ip_invoices; --"
        ]));
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - AJAX Content */
        $response->assertHeader('Content-Type');
        $response->assertDontSee('DROP TABLE');
    }
    
    #endregion
    
    #region Validation

    #[Test]
    public function it_handles_empty_query_gracefully(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        /**
         * POST /filter/filterajax/filter_invoices
         * Complete POST data: ['filter_query' => '']
         */
        $response = $this->post('/filter/filterajax/filter_invoices', $this->makeFilterData(['filter_query' => '']));
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - AJAX Content */
        $response->assertHeader('Content-Type');
    }

    #[Test]
    public function it_performs_case_insensitive_search(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        /**
         * POST /filter/filterajax/filter_invoices
         * Complete POST data: ['filter_query' => 'TEST']
         */
        $response = $this->post('/filter/filterajax/filter_invoices', $this->makeFilterData(['filter_query' => 'TEST']));
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - AJAX Content */
        $response->assertHeader('Content-Type');
        $response->assertSee('INV-');
    }
    
    #endregion
}
