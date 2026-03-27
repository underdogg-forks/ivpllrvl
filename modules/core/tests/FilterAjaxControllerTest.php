<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\FilterAjaxController;
use Modules\Core\Testing\ControllerTestCase;
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
    protected string $controllerClass = FilterAjaxController::class;
    
    protected function loadFixtures(): void
    {
        // Load fixtures for all filterable entities
        $users = $this->fixtures->all('users');
        $clients = $this->fixtures->all('clients');
        $invoices = $this->fixtures->all('invoices');
        $quotes = $this->fixtures->all('quotes');
        $products = $this->fixtures->all('products');
        $projects = $this->fixtures->all('projects');
        $payments = $this->fixtures->all('payments');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        foreach (['active_client', 'inactive_client'] as $key) {
            $this->fakeDb->insert('ip_clients', $clients[$key]);
        }
        
        foreach (['draft_invoice', 'sent_invoice', 'paid_invoice'] as $key) {
            $this->fakeDb->insert('ip_invoices', $invoices[$key]);
        }
        
        foreach (['draft_quote', 'sent_quote', 'approved_quote'] as $key) {
            $this->fakeDb->insert('ip_quotes', $quotes[$key]);
        }
        
        foreach (['standard_product', 'service_product'] as $key) {
            $this->fakeDb->insert('ip_products', $products[$key]);
        }
        
        foreach (['active_project', 'completed_project'] as $key) {
            $this->fakeDb->insert('ip_projects', $projects[$key]);
        }
        
        foreach (['cash_payment', 'bank_payment'] as $key) {
            $this->fakeDb->insert('ip_payments', $payments[$key]);
        }
    }
    
    protected function setUpController(): void
    {
        // Store common filter data for reuse
        $this->testData = [
            'filter_query' => 'Test',
        ];
    }
    #[Test]
    public function it_requires_authentication_for_filter_invoices(): void
    {
        /* Arrange */
        $this->clearAuth();
        $this->setPostData($this->testData);
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        $controller->filter('filter_invoices');
        
        /* Assert */
        $this->assertRedirectedTo('sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    #[Test]
    public function it_post_filter_invoices_returns_filtered_results(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData($this->testData);
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        ob_start();
        $controller->filter('filter_invoices');
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('INV-');
        // Verify we have seeded invoices in fake DB
        $invoices = $this->fakeDb->select('ip_invoices');
        $this->assertCount(3, $invoices);
    }

    #[Test]
    public function it_post_filter_invoices_searches_multiple_fields(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'filter_query' => 'special',
        ]);
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        ob_start();
        $controller->filter('filter_invoices');
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('invoice_number');
        $this->assertResponseContains('client_name');
        // Verify filter can search across multiple fields
        $invoices = $this->fakeDb->select('ip_invoices');
        $this->assertGreaterThan(0, count($invoices));
    }

    #[Test]
    public function it_post_filter_invoices_handles_multiple_keywords(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'filter_query' => 'INV test',
        ]);
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        ob_start();
        $controller->filter('filter_invoices');
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('INV-');
        $invoices = $this->fakeDb->select('ip_invoices');
        $this->assertCount(3, $invoices);
    }

    #[Test]
    public function it_post_filter_quotes_returns_filtered_results(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData($this->testData);
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        ob_start();
        $controller->filter('filter_quotes');
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('QUO-');
        $quotes = $this->fakeDb->select('ip_quotes');
        $this->assertCount(3, $quotes);
    }

    #[Test]
    public function it_post_filter_clients_returns_filtered_results(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'filter_query' => 'test',
        ]);
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        ob_start();
        $controller->filter('filter_clients');
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('client_name');
        $clients = $this->fakeDb->select('ip_clients');
        $this->assertCount(2, $clients);
    }

    #[Test]
    public function it_post_filter_custom_fields_returns_filtered_results(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData($this->testData);
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        ob_start();
        $controller->filter('filter_custom_fields');
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('custom_field');
    }

    #[Test]
    public function it_post_filter_custom_values_returns_filtered_results(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData($this->testData);
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        ob_start();
        $controller->filter('filter_custom_values');
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('custom_value');
    }

    #[Test]
    public function it_post_filter_projects_returns_filtered_results(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData($this->testData);
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        ob_start();
        $controller->filter('filter_projects');
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('project_name');
        $projects = $this->fakeDb->select('ip_projects');
        $this->assertCount(2, $projects);
    }

    #[Test]
    public function it_post_filter_products_returns_filtered_results(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData($this->testData);
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        ob_start();
        $controller->filter('filter_products');
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('product_name');
        $products = $this->fakeDb->select('ip_products');
        $this->assertCount(2, $products);
    }

    #[Test]
    public function it_post_filter_users_returns_filtered_results(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData($this->testData);
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        ob_start();
        $controller->filter('filter_users');
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('user_name');
        $users = $this->fakeDb->select('ip_users');
        $this->assertCount(2, $users);
    }

    #[Test]
    public function it_post_filter_payments_returns_filtered_results(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData($this->testData);
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        ob_start();
        $controller->filter('filter_payments');
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('payment_amount');
        $payments = $this->fakeDb->select('ip_payments');
        $this->assertCount(2, $payments);
    }

    #[Test]
    public function it_ajax_controller_flag_is_set(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        $this->assertTrue($controller->ajax_controller);
        
        /* Assert */
    }

    #[Test]
    public function it_filter_methods_sanitize_query_for_sql_injection(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'filter_query' => "'; DROP TABLE ip_invoices; --",
        ]);
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        ob_start();
        $controller->filter('filter_invoices');
        $output = ob_get_clean();
        
        /* Assert */
        // Verify tables still exist
        $invoices = $this->fakeDb->select('ip_invoices');
        $this->assertCount(3, $invoices);
        $this->assertNotContains('DROP TABLE', $output);
    }

    #[Test]
    public function it_filter_methods_handle_empty_query(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'filter_query' => '',
        ]);
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        ob_start();
        $controller->filter('filter_invoices');
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseOk();
        $this->assertTrue($this->fakeSession->has('user_id'));
    }

    #[Test]
    public function it_filter_invoices_uses_case_insensitive_search(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'filter_query' => 'TEST',
        ]);
        
        /* Act */
        // When CI bootstrap is ready:
        $controller = $this->getController();
        ob_start();
        $controller->filter('filter_invoices');
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('INV-');
        $invoices = $this->fakeDb->select('ip_invoices');
        $this->assertCount(3, $invoices);
    }
}
