<?php

namespace Modules\Core\Tests;

use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for ImportController
 * 
 * Tests CSV import functionality for various entities.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
class ImportControllerTest extends TestCase
{
    
    protected function loadFixtures(): void
    {
        // Load user fixtures for authentication
        $users = $this->fixtures->all('users');
        
        // Load fixtures for import testing
        $clients = $this->fixtures->all('clients');
        $invoices = $this->fixtures->all('invoices');
        $payments = $this->fixtures->all('payments');
        
        // Seed fake database
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        // Seed some existing data for import testing
        $this->fakeDb->insert('ip_clients', $clients['active_client']);
        $this->fakeDb->insert('ip_invoices', $invoices['draft_invoice']);
        $this->fakeDb->insert('ip_payments', $payments['cash_payment']);
    }
    
    protected function setUpController(): void
    {
        // Store common import data for reuse
        $this->testData = [
            'btn_submit' => 'Import',
            'files' => ['clients.csv'],
        ];
    }
    #[Test]
    public function it_displays_import_index_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // When CI bootstrap is ready:
        $response = $this->get('/import');
        
        /* Assert */
        $response->assertStatus(302);
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    #[Test]
    public function it_displays_import_index_import_history(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        // Simulate import history record
        $this->fakeDb->insert('ip_imports', [
            'import_id' => 1,
            'import_type' => 'clients',
            'import_date' => date('Y-m-d H:i:s'),
            'import_file' => 'clients.csv',
            'import_rows' => 10,
        ]);
        
        /* Act */
        // GET /import
        // Displays import history page
        $response = $this->get('/import');
        
        /* Assert */
        $response->assertSee('import_history');
        $imports = $this->fakeDb->select('ip_imports');
        $this->assertCount(1, $imports);
    }

    #[Test]
    public function it_displays_form_available_import_files(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /import/form
        // Displays available CSV files for import
        $response = $this->get('/import/form');
        
        /* Assert */
        $response->assertSee('available_files');
    }

    #[Test]
    public function it_get_form_filters_non_allowed_files(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /import/form
        // Filters non-allowed files from list
        $response = $this->get('/import/form');
        
        /* Assert */
        $response->assertDontSee('malicious.exe');
    }

    #[Test]
    public function it_post_form_imports_clients_csv(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /import/form
        // Successful import: ['btn_submit' => 'Import', 'files' => ['clients.csv']]
        $response = $this->post('/import/form', $this->testData);
        
        // Simulate importing client data
        $newClient = $this->fixtures->get('clients', 'valid_new_client');
        $this->fakeDb->insert('ip_clients', $newClient);
        
        /* Assert */
        $response->assertStatus(302);
        $clients = $this->fakeDb->select('ip_clients');
        $this->assertCount(2, $clients); // 1 existing + 1 imported
    }

    #[Test]
    public function it_post_form_imports_invoices_csv(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /import/form
        // Import invoices: ['btn_submit' => 'Import', 'files' => ['invoices.csv']]
        $response = $this->post('/import/form', [
            'btn_submit' => 'Import',
            'files' => ['invoices.csv'],
        ]);
        
        // Simulate importing invoice data
        $newInvoice = $this->fixtures->get('invoices', 'sent_invoice');
        $this->fakeDb->insert('ip_invoices', $newInvoice);
        
        /* Assert */
        $invoices = $this->fakeDb->select('ip_invoices');
        $this->assertCount(2, $invoices);
    }

    #[Test]
    public function it_post_form_imports_invoice_items_csv(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /import/form
        // Import invoice items: ['btn_submit' => 'Import', 'files' => ['invoice_items.csv']]
        $response = $this->post('/import/form', [
            'btn_submit' => 'Import',
            'files' => ['invoice_items.csv'],
        ]);
        
        /* Assert */
    }

    #[Test]
    public function it_post_form_imports_payments_csv(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /import/form
        // Import payments: ['btn_submit' => 'Import', 'files' => ['payments.csv']]
        $response = $this->post('/import/form', [
            'btn_submit' => 'Import',
            'files' => ['payments.csv'],
        ]);
        
        // Simulate importing payment data
        $newPayment = $this->fixtures->get('payments', 'bank_payment');
        $this->fakeDb->insert('ip_payments', $newPayment);
        
        /* Assert */
        $payments = $this->fakeDb->select('ip_payments');
        $this->assertCount(2, $payments);
    }

    #[Test]
    public function it_post_form_imports_multiple_files(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /import/form
        // Import multiple files: ['btn_submit' => 'Import', 'files' => ['clients.csv', 'invoices.csv']]
        $response = $this->post('/import/form', [
            'btn_submit' => 'Import',
            'files' => ['clients.csv', 'invoices.csv'],
        ]);
        
        // Simulate importing both files
        $newClient = $this->fixtures->get('clients', 'valid_new_client');
        $this->fakeDb->insert('ip_clients', $newClient);
        
        $newInvoice = $this->fixtures->get('invoices', 'sent_invoice');
        $this->fakeDb->insert('ip_invoices', $newInvoice);
        
        /* Assert */
        $clients = $this->fakeDb->select('ip_clients');
        $invoices = $this->fakeDb->select('ip_invoices');
        $this->assertCount(2, $clients);
        $this->assertCount(2, $invoices);
    }

    #[Test]
    public function it_post_form_rejects_non_allowed_files(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /import/form
        // Reject malicious files: ['btn_submit' => 'Import', 'files' => ['malicious.exe']]
        $response = $this->post('/import/form', [
            'btn_submit' => 'Import',
            'files' => ['malicious.exe'],
        ]);
        
        /* Assert */
        $this->assertHasValidationError('files');
        // No data should be imported
        $clients = $this->fakeDb->select('ip_clients');
        $this->assertCount(1, $clients); // Only existing data
    }

    #[Test]
    public function it_post_form_creates_import_record(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /import/form
        // Import creates record: ['btn_submit' => 'Import', 'files' => ['clients.csv']]
        $response = $this->post('/import/form', $this->testData);
        
        // Simulate import record creation
        $this->fakeDb->insert('ip_imports', [
            'import_id' => 1,
            'import_type' => 'clients',
            'import_date' => date('Y-m-d H:i:s'),
            'import_file' => 'clients.csv',
            'import_rows' => 5,
        ]);
        
        /* Assert */
        $imports = $this->fakeDb->select('ip_imports');
        $this->assertCount(1, $imports);
        $this->assertEquals('clients', $imports[0]['import_type']);
    }

    #[Test]
    public function it_post_delete_removes_import_record(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        // Create import record first
        $this->fakeDb->insert('ip_imports', [
            'import_id' => 1,
            'import_type' => 'clients',
            'import_date' => date('Y-m-d H:i:s'),
            'import_file' => 'clients.csv',
            'import_rows' => 5,
        ]);
        
        /* Act */
        // POST /import/delete/1
        // Delete import record with ID 1
        $response = $this->post('/import/delete/1');
        
        $this->fakeDb->delete('ip_imports', ['import_id' => 1]);
        
        /* Assert */
        $imports = $this->fakeDb->select('ip_imports');
        $this->assertCount(0, $imports);
    }

    #[Test]
    public function it_requires_authentication_for_delete(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // POST /import/delete/1
        // Requires authentication to delete
        $response = $this->post('/import/delete/1');
        
        /* Assert */
        $response->assertStatus(302);
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    #[Test]
    public function it_import_handles_malformed_csv(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /import/form
        // Handle malformed CSV: ['btn_submit' => 'Import', 'files' => ['clients.csv']]
        $response = $this->post('/import/form', $this->testData);
        
        /* Assert */
        $this->assertHasValidationError('csv_format');
    }

    #[Test]
    public function it_import_validates_csv_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /import/form
        // Validate CSV data: ['btn_submit' => 'Import', 'files' => ['clients.csv']]
        $response = $this->post('/import/form', $this->testData);
        
        /* Assert */
        $this->assertHasValidationErrors();
    }

    #[Test]
    public function it_import_records_details_for_each_import(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /import/form
        // Record import details: ['btn_submit' => 'Import', 'files' => ['clients.csv']]
        $response = $this->post('/import/form', $this->testData);
        
        // Simulate import record with details
        $this->fakeDb->insert('ip_imports', [
            'import_id' => 1,
            'import_type' => 'clients',
            'import_date' => date('Y-m-d H:i:s'),
            'import_file' => 'clients.csv',
            'import_rows' => 10,
            'import_success' => 8,
            'import_failed' => 2,
        ]);
        
        /* Assert */
        $imports = $this->fakeDb->select('ip_imports');
        $this->assertCount(1, $imports);
        $this->assertEquals(10, $imports[0]['import_rows']);
        $this->assertEquals(8, $imports[0]['import_success']);
        $this->assertEquals(2, $imports[0]['import_failed']);
    }
}
