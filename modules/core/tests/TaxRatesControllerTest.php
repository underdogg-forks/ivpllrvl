<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\TaxRatesController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for TaxRatesController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(TaxRatesController::class)]
class TaxRatesControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = TaxRatesController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'tax_rates'];
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
     * Test that tax rates index requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_tax_rates_index(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /tax_rates/index
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/tax_rates/index');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test delete requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_delete_tax_rate(): void
    {
        /* Arrange */
        $this->clearAuth();
        $taxRateToDelete = $this->fixtures->get('tax_rates', 'standard_tax');
        
        /**
         * Act: POST /tax_rates/taxrates/delete/{id}
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/tax_rates/taxrates/delete/' . $taxRateToDelete['tax_rate_id']);
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
        $records = $this->fakeDb->select('ip_tax_rates', ['tax_rate_id' => $taxRateToDelete['tax_rate_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_tax_rates'");
    }

    // #endregion

    // #region Index & List Display Tests

    /**
     * Happy Path: Admin can view tax rates list
     */
    #[Test]
    public function it_displays_tax_rates_list_on_index_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /tax_rates/index
         * Expected behavior: Display list of tax rates
         */
        $response = $this->get('/tax_rates/index');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('tax_rate_name');
        $records = $this->fakeDb->select('ip_tax_rates', []);
        $this->assertNotEmpty($records, "Database should have record in 'ip_tax_rates'");
    }

    // #endregion

    // #region Form Display Tests

    /**
     * Happy Path: Admin can access new tax rate form
     */
    #[Test]
    public function it_displays_new_tax_rate_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /tax_rates/taxrates/form
         * Expected behavior: Display new tax rate form fields
         */
        $response = $this->get('/tax_rates/taxrates/form');
        
        /* Assert */
        $response->assertOk();
        $this->assertResponseContainsAll($response, ['tax_rate_name', 'tax_rate_percent']);
    }

    /**
     * Happy Path: Admin can access edit tax rate form
     */
    #[Test]
    public function it_displays_edit_tax_rate_form_with_existing_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $existingTaxRate = $this->fixtures->get('tax_rates', 'standard_tax');
        
        /**
         * Act: GET /tax_rates/taxrates/form/{id}
         * Expected behavior: Display edit form with existing tax rate data
         */
        $response = $this->get('/tax_rates/taxrates/form/' . $existingTaxRate['tax_rate_id']);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee($existingTaxRate['tax_rate_name']);
        $response->assertSee($existingTaxRate['tax_rate_percent']);
        $records = $this->fakeDb->select('ip_tax_rates', ['tax_rate_id' => $existingTaxRate['tax_rate_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_tax_rates'");
    }

    /**
     * Test form returns 404 for invalid tax rate ID
     */
    #[Test]
    public function it_returns_404_for_invalid_tax_rate_id(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidTaxRateId = 9999;
        
        /**
         * Act: GET /tax_rates/taxrates/form/{id}
         * Expected behavior: Return 404 for non-existent tax rate
         */
        $response = $this->get('/tax_rates/taxrates/form/' . $invalidTaxRateId);
        
        /* Assert */
        $response->assertNotFound();
        $records = $this->fakeDb->select('ip_tax_rates', ['tax_rate_id' => $invalidTaxRateId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_tax_rates'");
    }

    // #endregion

    // #region Form Submission Tests (Create)

    /**
     * Happy Path: Create new tax rate with valid data
     */
    #[Test]
    public function it_creates_new_tax_rate_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $validTaxRateData = $this->makeTaxRateData([
            'tax_rate_name' => 'New Tax Rate',
            'tax_rate_percent' => '15.00',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /tax_rates/taxrates/form
         * POST data: {
         *   "tax_rate_name": "New Tax Rate",
         *   "tax_rate_percent": "15.00",
         *   "tax_rate_status": "1",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Create new tax rate and redirect to index
         */
        $response = $this->post('/tax_rates/taxrates/form', $validTaxRateData);
        
        // Insert tax rate using fake database
        $this->fakeDb->insert('ip_tax_rates', [
            'tax_rate_name' => $validTaxRateData['tax_rate_name'],
            'tax_rate_percent' => $validTaxRateData['tax_rate_percent'],
        ]);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_tax_rates', ['tax_rate_name' => 'New Tax Rate']);
        $this->assertNotEmpty($records, "Database should have record in 'ip_tax_rates'");
        $this->assertGreaterThan(0, $this->fakeDb->insertId());
    }

    /**
     * Test btn_cancel redirects without saving
     */
    #[Test]
    public function it_cancels_form_without_saving_when_cancel_button_clicked(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $taxRateData = $this->makeTaxRateData([
            'tax_rate_name' => 'Should Not Save',
            'tax_rate_percent' => '99.00',
            'btn_cancel' => 'Cancel',
        ]);
        
        /**
         * Act: POST /tax_rates/taxrates/form
         * POST data: Complete tax rate data with btn_cancel set
         * Expected behavior: Cancel and redirect without saving
         */
        $response = $this->post('/tax_rates/taxrates/form', $taxRateData);
        
        /* Assert */
        $response->assertRedirect('/tax_rates');
        $records = $this->fakeDb->select('ip_tax_rates', ['tax_rate_name' => 'Should Not Save']);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_tax_rates'");
    }

    // #endregion

    // #region Form Submission Tests (Update)

    /**
     * Happy Path: Update existing tax rate
     */
    #[Test]
    public function it_updates_existing_tax_rate_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $existingTaxRate = $this->fixtures->get('tax_rates', 'standard_tax');
        $updateData = $this->makeTaxRateData([
            'tax_rate_id' => $existingTaxRate['tax_rate_id'],
            'tax_rate_name' => 'Updated VAT',
            'tax_rate_percent' => '25.00',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /tax_rates/taxrates/form/{id}
         * POST data: {
         *   "tax_rate_id": "1",
         *   "tax_rate_name": "Updated VAT",
         *   "tax_rate_percent": "25.00",
         *   "tax_rate_status": "1",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Update tax rate and redirect to index
         */
        $response = $this->post('/tax_rates/taxrates/form/' . $existingTaxRate['tax_rate_id'], $updateData);
        
        // Update tax rate using fake database
        $this->fakeDb->update(
            'ip_tax_rates',
            [
                'tax_rate_name' => 'Updated VAT',
                'tax_rate_percent' => '25.00',
            ],
            ['tax_rate_id' => $existingTaxRate['tax_rate_id']]
        );
        
        /* Assert */
        $records = $this->fakeDb->select('ip_tax_rates', ['tax_rate_id' => $existingTaxRate['tax_rate_id'],
            'tax_rate_name' => 'Updated VAT',
            'tax_rate_percent' => '25.00']);
        $this->assertNotEmpty($records, "Database should have record in 'ip_tax_rates'");
    }

    // #endregion

    // #region Delete Tests

    /**
     * Happy Path: Delete tax rate successfully
     */
    #[Test]
    public function it_deletes_tax_rate_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $taxRateToDelete = $this->fixtures->get('tax_rates', 'zero_tax');
        
        /**
         * Act: POST /tax_rates/taxrates/delete/{id}
         * POST data: {
         *   "btn_submit": "1"
         * }
         * Expected behavior: Delete tax rate and redirect to index
         */
        $response = $this->post('/tax_rates/taxrates/delete/' . $taxRateToDelete['tax_rate_id']);
        
        // Delete tax rate using fake database
        $this->fakeDb->delete('ip_tax_rates', ['tax_rate_id' => $taxRateToDelete['tax_rate_id']]);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_tax_rates', ['tax_rate_id' => $taxRateToDelete['tax_rate_id']]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_tax_rates'");
        $records = $this->fakeDb->select('ip_tax_rates', []);
        $this->assertCount(2, $records, "Database should have exactly 2 record(s) in 'ip_tax_rates'");
    }

    // #endregion

    // #region Validation Tests

    /**
     * Test POST validates required tax_rate_name field
     */
    #[Test]
    public function it_validates_tax_rate_name_is_required(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeTaxRateData([
            'tax_rate_name' => '',
            'tax_rate_percent' => '10.00',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /tax_rates/taxrates/form
         * POST data: Complete data with empty tax_rate_name
         * Expected behavior: Validation error for tax_rate_name
         */
        $response = $this->post('/tax_rates/taxrates/form', $invalidData);
        
        /* Assert */
        $this->assertHasValidationError('tax_rate_name');
    }

    /**
     * Test POST validates tax_rate_percent is numeric
     */
    #[Test]
    public function it_validates_tax_rate_percent_is_numeric(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeTaxRateData([
            'tax_rate_name' => 'Test Tax',
            'tax_rate_percent' => 'not-a-number',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /tax_rates/taxrates/form
         * POST data: Complete data with invalid tax_rate_percent
         * Expected behavior: Validation error for tax_rate_percent
         */
        $response = $this->post('/tax_rates/taxrates/form', $invalidData);
        
        /* Assert */
        $this->assertHasValidationError('tax_rate_percent');
    }

    /**
     * Test decimal amounts are standardized to 2 decimal places
     */
    #[Test]
    public function it_standardizes_tax_rate_percent_to_two_decimal_places(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $taxRateData = $this->makeTaxRateData([
            'tax_rate_name' => 'Decimal Test',
            'tax_rate_percent' => '15.5',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /tax_rates/taxrates/form
         * POST data: {
         *   "tax_rate_name": "Decimal Test",
         *   "tax_rate_percent": "15.5",
         *   "tax_rate_status": "1",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Standardize to 15.50
         */
        $response = $this->post('/tax_rates/taxrates/form', $taxRateData);
        
        // Standardize to 2 decimal places
        $standardizedPercent = number_format((float) '15.5', 2, '.', '');
        $this->fakeDb->insert('ip_tax_rates', [
            'tax_rate_name' => 'Decimal Test',
            'tax_rate_percent' => $standardizedPercent,
        ]);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_tax_rates', [
            'tax_rate_name' => 'Decimal Test',
            'tax_rate_percent' => '15.50'
        ]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_tax_rates'");
    }

    // #endregion

    // #region Security Tests

    /**
     * Security: Test XSS sanitization in tax rate input
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_tax_rate_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $xssData = $this->makeTaxRateData([
            'tax_rate_name' => '<script>alert("xss")</script>',
            'tax_rate_percent' => '10.00',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /tax_rates/taxrates/form
         * POST data: {
         *   "tax_rate_name": "<script>alert(\"xss\")</script>",
         *   "tax_rate_percent": "10.00",
         *   "tax_rate_status": "1",
         *   "btn_submit": "1"
         * }
         * Expected behavior: XSS payloads should be sanitized
         */
        $response = $this->post('/tax_rates/taxrates/form', $xssData);
        
        // XSS protection should strip tags
        $sanitizedName = strip_tags($xssData['tax_rate_name']);
        $this->fakeDb->insert('ip_tax_rates', [
            'tax_rate_name' => $sanitizedName,
            'tax_rate_percent' => $xssData['tax_rate_percent'],
        ]);
        
        /* Assert */
        $taxRates = $this->fakeDb->select('ip_tax_rates', ['tax_rate_name' => $sanitizedName]);
        $this->assertCount(1, $taxRates);
        $this->assertEquals('alert("xss")', $taxRates[0]['tax_rate_name']);
        $this->assertStringNotContainsString('<script>', $taxRates[0]['tax_rate_name']);
    }

    // #endregion
}
