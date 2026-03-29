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
    
    /**
     * Build complete login data for POST requests
     * 
     * @param array<string, mixed> $overrides Override specific fields
     * @return array<string, mixed> Complete login data
     */
    protected function makeLoginData(array $overrides = []): array
    {
        $defaults = [
            'email' => 'admin@example.com',
            'password' => 'AdminPass123!',
            'remember_me' => '0',
            'btn_login' => '1',
        ];
        
        return array_merge($defaults, $overrides);
    }
    
    /**
     * Build complete password reset data for POST requests
     * 
     * @param array<string, mixed> $overrides Override specific fields
     * @return array<string, mixed> Complete password reset data
     */
    protected function makePasswordResetData(array $overrides = []): array
    {
        $defaults = [
            'email' => 'admin@example.com',
            'password' => 'NewSecurePass123!',
            'passwordv' => 'NewSecurePass123!',
            'token' => '',
            'btn_reset' => '1',
        ];
        
        return array_merge($defaults, $overrides);
    }
    
    /**
     * Build complete application settings data for POST requests
     * 
     * @param array<string, mixed> $overrides Override specific fields
     * @return array<string, mixed> Complete settings data (50+ fields)
     */
    protected function makeSettingsData(array $overrides = []): array
    {
        $defaults = [
            // Company Information
            'company_name' => 'Test Company Inc.',
            'company_address' => '123 Business Street',
            'company_city' => 'Business City',
            'company_state' => 'BC',
            'company_zip' => '12345',
            'company_country' => 'US',
            'company_phone' => '+1234567890',
            'company_fax' => '+1234567891',
            'company_web' => 'https://testcompany.com',
            
            // Default Settings
            'default_language' => 'english',
            'default_currency' => 'USD',
            'default_date_format' => 'm/d/Y',
            'default_invoice_template' => 'default',
            'default_quote_template' => 'default',
            'default_email_template' => 'default',
            'default_pdf_template' => 'default',
            'default_time_zone' => 'UTC',
            'default_list_limit' => '25',
            'default_country' => 'US',
            
            // Tax & Number Format Settings
            'tax_rate_decimal_places' => '2',
            'tax_rate_default' => '1',
            'currency_symbol_placement' => 'before',
            'thousands_separator' => ',',
            'decimal_point' => '.',
            'amount_decimal_places' => '2',
            
            // Invoice Settings
            'invoice_default_terms' => 'Net 30',
            'invoice_logo' => '',
            'invoice_pre_password' => '',
            'invoices_due_after' => '30',
            'invoice_number_prefix' => 'INV-',
            'invoice_number_next' => '1',
            'invoice_group_id' => '1',
            'invoice_default_payment_method' => '1',
            
            // Quote Settings
            'quote_default_terms' => 'Quote valid for 30 days',
            'quotes_expire_after' => '30',
            'quote_number_prefix' => 'QUO-',
            'quote_number_next' => '1',
            'quote_group_id' => '1',
            
            // Email Settings
            'email_send_method' => 'smtp',
            'smtp_host' => 'smtp.example.com',
            'smtp_user' => 'test@example.com',
            'smtp_password' => 'secure_password',
            'smtp_port' => '587',
            'smtp_encryption' => 'tls',
            'email_from_name' => 'Test Company',
            'email_from_email' => 'noreply@example.com',
            'email_bcc' => '',
            
            // PDF Settings
            'pdf_invoice_footer' => 'Thank you for your business',
            'pdf_quote_footer' => 'We appreciate your consideration',
            'pdf_page_format' => 'A4',
            'pdf_orientation' => 'portrait',
            
            // Payment Gateway Settings
            'gateway_name' => 'stripe',
            'merchant_email' => 'merchant@example.com',
            'gateway_api_key' => '',
            'gateway_secret_key' => '',
            'gateway_test_mode' => '1',
            
            // System Settings
            'disable_setup' => '1',
            'enable_invoice_deletion' => '0',
            'online_payment_method' => '1',
            'public_invoice_template' => 'default',
            'cron_key' => '',
            
            // Form Control Fields
            'btn_submit' => '1',
        ];
        
        return array_merge($defaults, $overrides);
    }
}
