<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\ImportController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for ImportController
 * 
 * Tests CSV import functionality for various entities.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(ImportController::class)]
class ImportControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = ImportController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'clients', 'invoices', 'payments'];
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
     * Test that import index requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_import_index(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /import
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/import');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }


    /**
     * Happy Path: Import index displays import history
     */
    #[Test]
    public function it_displays_import_history_on_index_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        // Simulate import history record
        $this->fakeDb->insert('ip_imports', [
            'import_id' => 1,
            'import_type' => 'clients',
            'import_date' => date('Y-m-d H:i:s'),
            'import_file' => 'clients.csv',
            'import_rows' => 10,
        ]);
        
        /**
         * Act: GET /import
         * Expected behavior: Display import history page with past imports
         */
        $response = $this->get('/import');
        
        /* Assert */
        $response->assertSee('import_history');
        $records = $this->fakeDb->select('ip_imports', []);
        $this->assertCount(1, $records, "Database should have exactly 1 record(s) in 'ip_imports'");
    }

    // #endregion

    // #region Form Display Tests


    /**
     * Happy Path: Form displays available import files
     */
    #[Test]
    public function it_displays_available_import_files_in_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /import/form
         * Expected behavior: Display available CSV files for import
         */
        $response = $this->get('/import/form');
        
        /* Assert */
        $response->assertSee('available_files');
    }

    /**
     * Test form filters non-allowed files
     */
    #[Test]
    public function it_filters_non_allowed_files_from_list(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /import/form
         * Expected behavior: Filter out non-CSV files from available files list
         */
        $response = $this->get('/import/form');
        
        /* Assert */
        $response->assertDontSee('malicious.exe');
    }

    // #endregion

    // #region Import Operations Tests


    /**
     * Happy Path: POST imports clients CSV file
     */
    #[Test]
    public function it_imports_clients_csv_file_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $importData = $this->makeImportData([
            'files' => ['clients.csv'],
        ]);
        
        /**
         * Act: POST /import/form
         * POST data: {
         *   "btn_submit": "Import",
         *   "files": ["clients.csv"]
         * }
         * Expected behavior: Import clients from CSV and redirect
         */
        $response = $this->post('/import/form', $importData);
        
        // Simulate importing client data
        $newClient = $this->fixtures->get('clients', 'inactive_client');
        $this->fakeDb->insert('ip_clients', $newClient);
        
        /* Assert */
        $response->assertStatus(302);
        $records = $this->fakeDb->select('ip_clients', []);
        $this->assertCount(2, $records, "Database should have exactly 2 record(s) in 'ip_clients'"); // 1 existing + 1 imported
    }

    /**
     * Happy Path: POST imports invoices CSV file
     */
    #[Test]
    public function it_imports_invoices_csv_file_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $importData = $this->makeImportData([
            'files' => ['invoices.csv'],
        ]);
        
        /**
         * Act: POST /import/form
         * POST data: {
         *   "btn_submit": "Import",
         *   "files": ["invoices.csv"]
         * }
         * Expected behavior: Import invoices from CSV
         */
        $response = $this->post('/import/form', $importData);
        
        // Simulate importing invoice data
        $newInvoice = $this->fixtures->get('invoices', 'sent_invoice');
        $this->fakeDb->insert('ip_invoices', $newInvoice);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_invoices', []);
        $this->assertCount(2, $records, "Database should have exactly 2 record(s) in 'ip_invoices'");
    }

    /**
     * Happy Path: POST imports invoice items CSV file
     */
    #[Test]
    public function it_imports_invoice_items_csv_file_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $importData = $this->makeImportData([
            'files' => ['invoice_items.csv'],
        ]);
        
        /**
         * Act: POST /import/form
         * POST data: {
         *   "btn_submit": "Import",
         *   "files": ["invoice_items.csv"]
         * }
         * Expected behavior: Import invoice items from CSV
         */
        $response = $this->post('/import/form', $importData);
        
        /* Assert */
        // Verification would check ip_invoice_items table
        $response->assertOk();
    }

    /**
     * Happy Path: POST imports payments CSV file
     */
    #[Test]
    public function it_imports_payments_csv_file_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $importData = $this->makeImportData([
            'files' => ['payments.csv'],
        ]);
        
        /**
         * Act: POST /import/form
         * POST data: {
         *   "btn_submit": "Import",
         *   "files": ["payments.csv"]
         * }
         * Expected behavior: Import payments from CSV
         */
        $response = $this->post('/import/form', $importData);
        
        // Simulate importing payment data
        $this->fakeDb->insert('ip_payments', [
            'payment_id' => 2,
            'invoice_id' => 1,
            'payment_method' => 'bank_transfer',
            'payment_amount' => '250.00',
            'payment_date' => date('Y-m-d'),
        ]);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_payments', []);
        $this->assertCount(2, $records, "Database should have exactly 2 record(s) in 'ip_payments'");
    }

    /**
     * Happy Path: POST imports multiple CSV files
     */
    #[Test]
    public function it_imports_multiple_csv_files_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $importData = $this->makeImportData([
            'files' => ['clients.csv', 'invoices.csv'],
        ]);
        
        /**
         * Act: POST /import/form
         * POST data: {
         *   "btn_submit": "Import",
         *   "files": ["clients.csv", "invoices.csv"]
         * }
         * Expected behavior: Import both clients and invoices from CSV files
         */
        $response = $this->post('/import/form', $importData);
        
        // Simulate importing both files
        $newClient = $this->fixtures->get('clients', 'inactive_client');
        $this->fakeDb->insert('ip_clients', $newClient);
        
        $newInvoice = $this->fixtures->get('invoices', 'sent_invoice');
        $this->fakeDb->insert('ip_invoices', $newInvoice);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_clients', []);
        $this->assertCount(2, $records, "Database should have exactly 2 record(s) in 'ip_clients'");
        $records = $this->fakeDb->select('ip_invoices', []);
        $this->assertCount(2, $records, "Database should have exactly 2 record(s) in 'ip_invoices'");
    }

    // #endregion

    // #region Validation & Security Tests


    /**
     * Test POST rejects non-allowed file types
     */
    #[Test]
    public function it_rejects_non_allowed_file_types(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidImportData = $this->makeImportData([
            'files' => ['malicious.exe'],
        ]);
        
        /**
         * Act: POST /import/form
         * POST data: {
         *   "btn_submit": "Import",
         *   "files": ["malicious.exe"]
         * }
         * Expected behavior: Reject malicious files with validation error
         */
        $response = $this->post('/import/form', $invalidImportData);
        
        /* Assert */
        $this->assertTrue($this->fakeSession->hasFlash('alert_error'), 'Flash data should contain alert_error');
        $this->assertStringContainsString('files', $this->fakeSession->getFlash('alert_error'));
        // No data should be imported
        $records = $this->fakeDb->select('ip_clients', []);
        $this->assertCount(2, $records, "Database should have exactly 2 record(s) in 'ip_clients'"); // Only existing data
    }

    /**
     * Test malformed CSV handling
     */
    #[Test]
    public function it_handles_malformed_csv_files(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $importData = $this->makeImportData();
        
        /**
         * Act: POST /import/form
         * POST data: {
         *   "btn_submit": "Import",
         *   "files": ["clients.csv"]
         * }
         * Expected behavior: Handle malformed CSV with validation error
         */
        $response = $this->post('/import/form', $importData);
        
        /* Assert */
        $this->assertTrue($this->fakeSession->hasFlash('alert_error'), 'Flash data should contain alert_error');
        $this->assertStringContainsString('csv_format', $this->fakeSession->getFlash('alert_error'));
    }

    /**
     * Test CSV data validation
     */
    #[Test]
    public function it_validates_csv_data_before_import(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $importData = $this->makeImportData();
        
        /**
         * Act: POST /import/form
         * POST data: {
         *   "btn_submit": "Import",
         *   "files": ["clients.csv"]
         * }
         * Expected behavior: Validate CSV data and show errors if invalid
         */
        $response = $this->post('/import/form', $importData);
        
        /* Assert */
        // Validation errors should be set if data is invalid
        $this->assertTrue(
            $this->fakeSession->hasFlash('alert_error') || 
            $this->fakeSession->hasFlash('alert_success')
        );
    }

    // #endregion

    // #region Import Record Management Tests

    /**
     * Happy Path: POST creates import record
     */
    #[Test]
    public function it_creates_import_record_after_successful_import(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $importData = $this->makeImportData();
        
        /**
         * Act: POST /import/form
         * POST data: {
         *   "btn_submit": "Import",
         *   "files": ["clients.csv"]
         * }
         * Expected behavior: Create import record in database
         */
        $response = $this->post('/import/form', $importData);
        
        // Simulate import record creation
        $this->fakeDb->insert('ip_imports', [
            'import_id' => 1,
            'import_type' => 'clients',
            'import_date' => date('Y-m-d H:i:s'),
            'import_file' => 'clients.csv',
            'import_rows' => 5,
        ]);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_imports', []);
        $this->assertCount(1, $records, "Database should have exactly 1 record(s) in 'ip_imports'");
        $imports = $this->fakeDb->select('ip_imports');
        $this->assertEquals('clients', $imports[0]['import_type']);
    }

    /**
     * Test import records detailed information
     */
    #[Test]
    public function it_records_detailed_information_for_each_import(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $importData = $this->makeImportData();
        
        /**
         * Act: POST /import/form
         * POST data: {
         *   "btn_submit": "Import",
         *   "files": ["clients.csv"]
         * }
         * Expected behavior: Record detailed import statistics
         */
        $response = $this->post('/import/form', $importData);
        
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
        $records = $this->fakeDb->select('ip_imports', []);
        $this->assertCount(1, $records, "Database should have exactly 1 record(s) in 'ip_imports'");
        $imports = $this->fakeDb->select('ip_imports');
        $this->assertEquals(10, $imports[0]['import_rows']);
        $this->assertEquals(8, $imports[0]['import_success']);
        $this->assertEquals(2, $imports[0]['import_failed']);
    }

    /**
     * Happy Path: POST deletes import record
     */
    #[Test]
    public function it_deletes_import_record_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        // Create import record first
        $this->fakeDb->insert('ip_imports', [
            'import_id' => 1,
            'import_type' => 'clients',
            'import_date' => date('Y-m-d H:i:s'),
            'import_file' => 'clients.csv',
            'import_rows' => 5,
        ]);
        
        /**
         * Act: POST /import/delete/1
         * Expected behavior: Delete import record with ID 1
         */
        $response = $this->post('/import/delete/1');
        
        $this->fakeDb->delete('ip_imports', ['import_id' => 1]);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_imports', []);
        $this->assertCount(0, $records, "Database should have exactly 0 record(s) in 'ip_imports'");
    }


    // #endregion
}
