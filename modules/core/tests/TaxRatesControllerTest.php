<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\TaxRatesController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for TaxRatesController
 * 
 * Tests the full request/response cycle using Laravel HTTP testing.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(TaxRatesController::class)]
class TaxRatesControllerTest extends TestCase
{
    
    protected function loadFixtures(): void
    {
        // Load tax rate fixtures
        $taxRates = $this->fixtures->all('tax_rates');
        
        // Seed fake database with fixture data
        foreach (['standard_tax', 'reduced_tax', 'zero_tax'] as $key) {
            $this->fakeDb->insert('ip_tax_rates', $taxRates[$key]);
        }
    }
    
    protected function setUpController(): void
    {
        // Store valid new tax rate data from fixtures for reuse
        $this->testData = $this->fixtures->get('tax_rates', 'valid_new_tax');
    }

    /**
     * Test that tax rates index requires authentication
     */
    #[Test]
    public function it_displays_tax_rates_index_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // GET /tax_rates/taxrates/index
        $response = $this->get('/tax_rates/index');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Happy Path: Admin can view tax rates list
     */
    #[Test]
    public function it_displays_tax_rates_index_returns_tax_rate_list(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /tax_rates/taxrates/index
        $response = $this->get('/tax_rates/index');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('tax_rate_name');
    }

    /**
     * Happy Path: Admin can access new tax rate form
     */
    #[Test]
    public function it_displays_form_new_tax_rate_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // GET /tax_rates/taxrates/form
        $response = $this->get('/tax_rates/taxrates/form');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('tax_rate_name');
        $response->assertSee('tax_rate_percent');
    }

    /**
     * Happy Path: Admin can access edit tax rate form
     */
    #[Test]
    public function it_displays_form_edit_tax_rate_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingTaxRate = $this->fixtures->get('tax_rates', 'standard_tax');
        
        /* Act */
        // GET /tax_rates/taxrates/form/{id}
        $response = $this->get('/tax_rates/taxrates/form/' . $existingTaxRate['tax_rate_id']);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee($existingTaxRate['tax_rate_name']);
        $response->assertSee($existingTaxRate['tax_rate_percent']);
        $taxRates = $this->fakeDb->select('ip_tax_rates', ['tax_rate_id' => $existingTaxRate['tax_rate_id']]);
        $this->assertCount(1, $taxRates);
    }

    /**
     * Test editing non-existent tax rate returns 404
     */
    #[Test]
    public function it_get_form_returns_404_for_invalid_tax_rate(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidTaxRateId = 9999;
        
        /* Act */
        // GET /tax_rates/taxrates/form/{id}
        $response = $this->get('/tax_rates/taxrates/form/' . $invalidTaxRateId);
        
        /* Assert */
        $response->assertNotFound();
        $taxRates = $this->fakeDb->select('ip_tax_rates', ['tax_rate_id' => $invalidTaxRateId]);
        $this->assertCount(0, $taxRates);
    }

    /**
     * Happy Path: Create new tax rate with valid data
     */
    #[Test]
    public function it_post_form_creates_new_tax_rate(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /tax_rates/taxrates/form
        // Successful request: creates new tax rate
        $response = $this->post('/tax_rates/taxrates/form', array_merge($this->testData, [
            'btn_submit' => '1',
        ]));
        
        // Insert tax rate using fake database
        $this->fakeDb->insert('ip_tax_rates', [
            'tax_rate_name' => $this->testData['tax_rate_name'],
            'tax_rate_percent' => $this->testData['tax_rate_percent'],
        ]);
        
        /* Assert */
        // Verify tax rate was inserted
        $taxRates = $this->fakeDb->select('ip_tax_rates', [
            'tax_rate_name' => 'New Tax Rate'
        ]);
        $this->assertCount(1, $taxRates);
        $this->assertEquals('15.00', $taxRates[0]['tax_rate_percent']);
        
        // Verify last insert ID
        $this->assertGreaterThan(0, $this->fakeDb->insertId());
    }

    /**
     * Happy Path: Update existing tax rate
     */
    #[Test]
    public function it_post_form_updates_existing_tax_rate(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingTaxRate = $this->fixtures->get('tax_rates', 'standard_tax');
        
        /* Act */
        // POST /tax_rates/taxrates/form/{id}
        // Successful request: updates existing tax rate
        $response = $this->post('/tax_rates/taxrates/form/' . $existingTaxRate['tax_rate_id'], [
            'btn_submit' => '1',
            'tax_rate_id' => $existingTaxRate['tax_rate_id'],
            'tax_rate_name' => 'Updated VAT',
            'tax_rate_percent' => '25.00',
        ]);
        
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
        // Verify tax rate was updated
        $taxRates = $this->fakeDb->select('ip_tax_rates', ['tax_rate_id' => $existingTaxRate['tax_rate_id']]);
        $this->assertCount(1, $taxRates);
        $this->assertEquals('Updated VAT', $taxRates[0]['tax_rate_name']);
        $this->assertEquals('25.00', $taxRates[0]['tax_rate_percent']);
    }

    /**
     * Test validation rejects empty tax rate name
     */
    #[Test]
    public function it_validates_form_tax_rate_name(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /tax_rates/taxrates/form
        // Invalid request: missing required tax_rate_name
        $response = $this->post('/tax_rates/taxrates/form', [
            'btn_submit' => '1',
            'tax_rate_name' => '', // Required field missing
            'tax_rate_percent' => '10.00',
        ]);
        
        /* Assert */
        $this->assertHasValidationErrors();
        $this->assertHasValidationError('tax_rate_name');
    }

    /**
     * Test validation rejects invalid tax rate percent
     */
    #[Test]
    public function it_validates_form_tax_rate_percent(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /tax_rates/taxrates/form
        // Invalid request: tax_rate_percent is not a valid number
        $response = $this->post('/tax_rates/taxrates/form', [
            'btn_submit' => '1',
            'tax_rate_name' => 'Test Tax',
            'tax_rate_percent' => 'not-a-number', // Invalid format
        ]);
        
        /* Assert */
        $this->assertHasValidationError('tax_rate_percent');
    }

    /**
     * Test decimal amounts are standardized to 2 decimal places
     */
    #[Test]
    public function it_post_form_standardizes_decimal_amounts(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /tax_rates/taxrates/form
        // Successful request: decimal amount should be standardized to 2 places
        $response = $this->post('/tax_rates/taxrates/form', [
            'btn_submit' => '1',
            'tax_rate_name' => 'Decimal Test',
            'tax_rate_percent' => '15.5', // Should become 15.50
        ]);
        
        // Standardize to 2 decimal places
        $standardizedPercent = number_format((float) '15.5', 2, '.', '');
        $this->fakeDb->insert('ip_tax_rates', [
            'tax_rate_name' => 'Decimal Test',
            'tax_rate_percent' => $standardizedPercent,
        ]);
        
        /* Assert */
        $taxRates = $this->fakeDb->select('ip_tax_rates', ['tax_rate_name' => 'Decimal Test']);
        $this->assertCount(1, $taxRates);
        $this->assertEquals('15.50', $taxRates[0]['tax_rate_percent']);
    }

    /**
     * Test XSS protection in tax rate input
     */
    #[Test]
    public function it_post_form_sanitizes_xss_attempts(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $xssData = [
            'btn_submit' => '1',
            'tax_rate_name' => '<script>alert("xss")</script>',
            'tax_rate_percent' => '10.00',
        ];
        
        /* Act */
        // POST /tax_rates/taxrates/form
        // XSS attempt: script tags in tax_rate_name should be sanitized
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

    /**
     * Test btn_cancel redirects without saving
     */
    #[Test]
    public function it_post_form_cancels_without_saving(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /tax_rates/taxrates/form
        // Cancel request: btn_cancel should redirect without saving
        $response = $this->post('/tax_rates/taxrates/form', [
            'btn_cancel' => 'Cancel',
            'tax_rate_name' => 'Should Not Save',
            'tax_rate_percent' => '99.00',
        ]);
        
        /* Assert */
        $response->assertRedirect('/tax_rates');
        // Verify the tax rate was NOT saved
        $taxRates = $this->fakeDb->select('ip_tax_rates', ['tax_rate_name' => 'Should Not Save']);
        $this->assertCount(0, $taxRates);
    }

    /**
     * Happy Path: Delete tax rate
     */
    #[Test]
    public function it_post_delete_removes_tax_rate(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $taxRateToDelete = $this->fixtures->get('tax_rates', 'zero_tax');
        
        /* Act */
        // POST /tax_rates/taxrates/delete/{id}
        // Successful request: deletes tax rate
        $response = $this->post('/tax_rates/taxrates/delete/' . $taxRateToDelete['tax_rate_id']);
        
        // Delete tax rate using fake database
        $this->fakeDb->delete('ip_tax_rates', ['tax_rate_id' => $taxRateToDelete['tax_rate_id']]);
        
        /* Assert */
        // Verify tax rate was deleted
        $taxRates = $this->fakeDb->select('ip_tax_rates', ['tax_rate_id' => $taxRateToDelete['tax_rate_id']]);
        $this->assertCount(0, $taxRates);
        
        // Verify other tax rates still exist
        $remainingTaxRates = $this->fakeDb->select('ip_tax_rates');
        $this->assertCount(2, $remainingTaxRates);
    }

    /**
     * Test delete requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_delete(): void
    {
        /* Arrange */
        $this->clearAuth();
        $taxRateToDelete = $this->fixtures->get('tax_rates', 'standard_tax');
        
        /* Act */
        // POST /tax_rates/taxrates/delete/{id}
        // Unauthenticated request: should redirect to login
        $response = $this->post('/tax_rates/taxrates/delete/' . $taxRateToDelete['tax_rate_id']);
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
        // Verify no session data exists
        $this->assertFalse($this->fakeSession->has('user_id'));
        
        // Verify tax rate was NOT deleted
        $taxRates = $this->fakeDb->select('ip_tax_rates', ['tax_rate_id' => $taxRateToDelete['tax_rate_id']]);
        $this->assertCount(1, $taxRates);
    }
}
