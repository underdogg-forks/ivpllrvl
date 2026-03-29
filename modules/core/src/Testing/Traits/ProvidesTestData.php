<?php

namespace Modules\Core\Testing\Traits;

/**
 * ProvidesTestData Trait
 * 
 * Provides a SOLID, DRY way to build complete test data for POST/PUT requests.
 * Follows the principle: "POST complete forms, fail on one field".
 * 
 * Usage:
 *   $completeData = $this->makeUserData(['user_email' => 'invalid']);
 *   $response = $this->post('/users/form', $completeData);
 */
trait ProvidesTestData
{
    /**
     * Build complete user data for POST/PUT requests
     * 
     * @param array<string, mixed> $overrides Override specific fields
     * @return array<string, mixed> Complete user data
     */
    protected function makeUserData(array $overrides = []): array
    {
        $defaults = [
            'user_type' => '2',
            'user_name' => 'Test User',
            'user_company' => 'Test Company',
            'user_email' => 'testuser@example.com',
            'user_password' => 'SecurePass123!',
            'user_passwordv' => 'SecurePass123!',
            'user_language' => 'english',
            'user_timezone' => 'UTC',
            'user_vat_id' => '',
            'user_tax_code' => '',
            'user_phone' => '+1234567890',
            'user_fax' => '',
            'user_mobile' => '',
            'user_web' => '',
            'user_address_1' => '123 Test Street',
            'user_address_2' => '',
            'user_city' => 'Test City',
            'user_state' => 'TS',
            'user_zip' => '12345',
            'user_country' => 'US',
        ];
        
        return array_merge($defaults, $overrides);
    }
    
    /**
     * Build complete client data for POST/PUT requests
     * 
     * @param array<string, mixed> $overrides Override specific fields
     * @return array<string, mixed> Complete client data
     */
    protected function makeClientData(array $overrides = []): array
    {
        $defaults = [
            'client_name' => 'Test Client',
            'client_surname' => '',
            'client_email' => 'client@example.com',
            'client_phone' => '+1234567890',
            'client_mobile' => '',
            'client_fax' => '',
            'client_web' => 'https://example.com',
            'client_vat_id' => 'VAT123456',
            'client_tax_code' => 'TAX123',
            'client_address_1' => '456 Client Ave',
            'client_address_2' => '',
            'client_city' => 'Client City',
            'client_state' => 'CS',
            'client_zip' => '67890',
            'client_country' => 'US',
            'client_language' => 'english',
            'client_active' => '1',
            'client_birthdate' => '',
        ];
        
        return array_merge($defaults, $overrides);
    }
    
    /**
     * Build complete invoice data for POST/PUT requests
     * 
     * @param array<string, mixed> $overrides Override specific fields
     * @return array<string, mixed> Complete invoice data
     */
    protected function makeInvoiceData(array $overrides = []): array
    {
        $defaults = [
            'client_id' => '1',
            'invoice_date_created' => date('Y-m-d'),
            'invoice_date_due' => date('Y-m-d', strtotime('+30 days')),
            'invoice_status_id' => '1',
            'invoice_number' => 'INV-' . date('Ymd') . '-001',
            'invoice_terms' => 'Net 30',
            'invoice_discount_percent' => '0.00',
            'invoice_discount_amount' => '0.00',
            'invoice_currency' => 'USD',
            'invoice_password' => '',
            'invoice_url_key' => '',
            'user_id' => '1',
        ];
        
        return array_merge($defaults, $overrides);
    }
    
    /**
     * Build complete quote data for POST/PUT requests
     * 
     * @param array<string, mixed> $overrides Override specific fields
     * @return array<string, mixed> Complete quote data
     */
    protected function makeQuoteData(array $overrides = []): array
    {
        $defaults = [
            'client_id' => '1',
            'quote_date_created' => date('Y-m-d'),
            'quote_date_expires' => date('Y-m-d', strtotime('+30 days')),
            'quote_status_id' => '1',
            'quote_number' => 'QUO-' . date('Ymd') . '-001',
            'quote_discount_percent' => '0.00',
            'quote_discount_amount' => '0.00',
            'quote_currency' => 'USD',
            'quote_password' => '',
            'quote_url_key' => '',
            'user_id' => '1',
        ];
        
        return array_merge($defaults, $overrides);
    }
    
    /**
     * Build complete project data for POST/PUT requests
     * 
     * @param array<string, mixed> $overrides Override specific fields
     * @return array<string, mixed> Complete project data
     */
    protected function makeProjectData(array $overrides = []): array
    {
        $defaults = [
            'client_id' => '1',
            'project_name' => 'Test Project',
            'project_description' => 'Test project description',
            'project_status_id' => '1',
            'project_date_start' => date('Y-m-d'),
            'project_date_due' => date('Y-m-d', strtotime('+60 days')),
            'project_budget' => '10000.00',
            'project_currency' => 'USD',
        ];
        
        return array_merge($defaults, $overrides);
    }
    
    /**
     * Build complete product data for POST/PUT requests
     * 
     * @param array<string, mixed> $overrides Override specific fields
     * @return array<string, mixed> Complete product data
     */
    protected function makeProductData(array $overrides = []): array
    {
        $defaults = [
            'product_name' => 'Test Product',
            'product_description' => 'Test product description',
            'product_price' => '100.00',
            'product_sku' => 'SKU-' . uniqid(),
            'product_unit_id' => '1',
            'family_id' => '1',
            'tax_rate_id' => '1',
            'product_tariff' => '',
            'purchase_price' => '50.00',
        ];
        
        return array_merge($defaults, $overrides);
    }
    
    /**
     * Build complete payment data for POST/PUT requests
     * 
     * @param array<string, mixed> $overrides Override specific fields
     * @return array<string, mixed> Complete payment data
     */
    protected function makePaymentData(array $overrides = []): array
    {
        $defaults = [
            'invoice_id' => '1',
            'payment_method_id' => '1',
            'payment_amount' => '100.00',
            'payment_date' => date('Y-m-d'),
            'payment_note' => '',
        ];
        
        return array_merge($defaults, $overrides);
    }
    
    /**
     * Build complete payment method data for POST/PUT requests
     * 
     * @param array<string, mixed> $overrides Override specific fields
     * @return array<string, mixed> Complete payment method data
     */
    protected function makePaymentMethodData(array $overrides = []): array
    {
        $defaults = [
            'payment_method_name' => 'Test Payment Method',
        ];
        
        return array_merge($defaults, $overrides);
    }
    
    /**
     * Build complete task data for POST/PUT requests
     * 
     * @param array<string, mixed> $overrides Override specific fields
     * @return array<string, mixed> Complete task data
     */
    protected function makeTaskData(array $overrides = []): array
    {
        $defaults = [
            'project_id' => '1',
            'task_name' => 'Test Task',
            'task_description' => 'Test task description',
            'task_status' => '1',
            'task_date_start' => date('Y-m-d'),
            'task_date_due' => date('Y-m-d', strtotime('+7 days')),
            'task_price' => '500.00',
            'task_finish_date' => '',
        ];
        
        return array_merge($defaults, $overrides);
    }
    
    /**
     * Build complete tax rate data for POST/PUT requests
     * 
     * @param array<string, mixed> $overrides Override specific fields
     * @return array<string, mixed> Complete tax rate data
     */
    protected function makeTaxRateData(array $overrides = []): array
    {
        $defaults = [
            'tax_rate_name' => 'Test Tax Rate',
            'tax_rate_percent' => '10.00',
        ];
        
        return array_merge($defaults, $overrides);
    }
}
