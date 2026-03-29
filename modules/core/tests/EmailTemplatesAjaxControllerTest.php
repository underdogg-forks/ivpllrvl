<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\EmailTemplatesAjaxController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for EmailTemplatesAjaxController
 * 
 * Tests AJAX functionality for email template content retrieval.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(EmailTemplatesAjaxController::class)]
class EmailTemplatesAjaxControllerTest extends ControllerTestCase
{
    use LoadsFixtures, ProvidesTestData, ProvidesAssertions;
    
    protected string $controllerClass = EmailTemplatesAjaxController::class;
    
    protected function fixtureTypes(): array
    {
        return ['users', 'email_templates'];
    }
    
    protected function loadFixtures(): void
    {
        $this->loadAllFixtures();
    }
    
    protected function setUpController(): void
    {
        // Store template for testing AJAX requests
        $this->testTemplate = $this->fixtures->get('email_templates', 'invoice_template');
    }
    
    // #region Authentication

    #[Test]
    public function it_requires_authentication_for_get_content(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /email_templates/emailtemplatesajax/get_content
         * POST data: {
         *   "email_template_id": "1"
         * }
         */
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => $this->testTemplate['email_template_id'],
        ]);
        
        /* Assert */
        $this->assertUnauthorized($response);
    }
    
    // #endregion
    
    // #region AJAX Endpoints

    /**
     * Happy Path: Get content returns template JSON
     */
    #[Test]
    public function it_returns_template_json_via_get_content(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /email_templates/emailtemplatesajax/get_content
         * POST data: {
         *   "email_template_id": "1"
         * }
         * Expected JSON response: {
         *   "email_template_title": "Invoice Email Template",
         *   "email_template_subject": "Invoice {invoice_number} from {company_name}",
         *   "email_template_body": "<p>Dear {client_name},</p>..."
         * }
         */
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => $this->testTemplate['email_template_id'],
        ]);
        
        /* Assert - Response Structure */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        
        /* Assert - JSON Data */
        $jsonData = json_decode($response->getContent(), true);
        $this->assertIsArray($jsonData, 'Response should be valid JSON array');
        $this->assertArrayHasKey('email_template_title', $jsonData, 'Response should contain template title');
        $this->assertArrayHasKey('email_template_subject', $jsonData, 'Response should contain template subject');
        $this->assertArrayHasKey('email_template_body', $jsonData, 'Response should contain template body');
        
        /* Assert - Data Content Matches Fixture */
        $this->assertEquals($this->testTemplate['email_template_title'], $jsonData['email_template_title']);
        $this->assertEquals($this->testTemplate['email_template_subject'], $jsonData['email_template_subject']);
        $this->assertEquals($this->testTemplate['email_template_body'], $jsonData['email_template_body']);
        
        /* Assert - Database State Unchanged */
        $records = $this->fakeDb->select('ip_email_templates', ['email_template_id' => $this->testTemplate['email_template_id']]);
        $this->assertNotEmpty($records, "Template should still exist in database");
    }

    /**
     * Test get content returns empty for invalid ID
     */
    #[Test]
    public function it_returns_empty_for_invalid_id_via_get_content(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /email_templates/emailtemplatesajax/get_content
         * POST data: {
         *   "email_template_id": 999999
         * }
         * Expected JSON response: {} (empty object or minimal structure)
         */
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => 999999,
        ]);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - JSON Structure */
        $jsonData = json_decode($response->getContent(), true);
        $this->assertIsArray($jsonData, 'Response should be valid JSON');
        
        /* Assert - No Template Data */
        $this->assertArrayNotHasKey('email_template_title', $jsonData, 'Response should NOT contain title for invalid ID');
        $this->assertArrayNotHasKey('email_template_subject', $jsonData, 'Response should NOT contain subject for invalid ID');
        $this->assertArrayNotHasKey('email_template_body', $jsonData, 'Response should NOT contain body for invalid ID');
        
        /* Assert - Database Verification */
        $records = $this->fakeDb->select('ip_email_templates', ['email_template_id' => 999999]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_email_templates'");
    }

    /**
     * Test get content returns correct content type
     */
    #[Test]
    public function it_returns_correct_content_type_via_get_content(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /email_templates/emailtemplatesajax/get_content
         * POST data: {
         *   "email_template_id": "1"
         * }
         * Expected JSON response: {
         *   "email_template_title": "Invoice Email Template",
         *   "email_template_subject": "Invoice {invoice_number} from {company_name}",
         *   "email_template_body": "<p>Dear {client_name},</p>..."
         * }
         */
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => $this->testTemplate['email_template_id'],
        ]);
        
        /* Assert - Response Headers */
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        
        /* Assert - Valid JSON Structure */
        $jsonData = json_decode($response->getContent(), true);
        $this->assertIsArray($jsonData, 'Response should be valid JSON array');
        $this->assertNotEmpty($jsonData, 'JSON response should not be empty');
        
        /* Assert - Required Fields Present */
        $this->assertArrayHasKey('email_template_title', $jsonData);
        $this->assertArrayHasKey('email_template_subject', $jsonData);
        $this->assertArrayHasKey('email_template_body', $jsonData);
    }

    /**
     * Test get content includes all template fields
     */
    #[Test]
    public function it_includes_all_template_fields_via_get_content(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /email_templates/emailtemplatesajax/get_content
         * POST data: {
         *   "email_template_id": "1"
         * }
         * Expected JSON response: {
         *   "email_template_title": "...",
         *   "email_template_subject": "...",
         *   "email_template_body": "...",
         *   "email_template_from_name": "...",
         *   "email_template_from_email": "...",
         *   "email_template_cc": "...",
         *   "email_template_bcc": "...",
         *   "email_template_pdf_template": "..."
         * }
         */
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => $this->testTemplate['email_template_id'],
        ]);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - JSON Structure */
        $jsonData = json_decode($response->getContent(), true);
        $this->assertIsArray($jsonData, 'Response should be valid JSON array');
        
        /* Assert - All Required Fields Present */
        $requiredFields = [
            'email_template_title',
            'email_template_subject',
            'email_template_body',
            'email_template_from_name',
            'email_template_from_email',
            'email_template_cc',
            'email_template_bcc',
            'email_template_pdf_template',
        ];
        
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $jsonData, "Response should contain field: {$field}");
        }
        
        /* Assert - Database Verification */
        $records = $this->fakeDb->select('ip_email_templates', ['email_template_id' => $this->testTemplate['email_template_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_email_templates'");
    }

    /**
     * Test get content handles special characters
     */
    #[Test]
    public function it_handles_special_characters_via_get_content(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $specialTemplate = $this->fixtures->get('email_templates', 'invoice_template');
        
        /**
         * Act: POST /email_templates/emailtemplatesajax/get_content
         * POST data: {
         *   "email_template_id": "1"
         * }
         * Expected JSON response: Template with {{ }} placeholders intact
         */
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => $specialTemplate['email_template_id'],
        ]);
        
        /* Assert - Response Status */
        $response->assertOk();
        
        /* Assert - JSON Structure */
        $jsonData = json_decode($response->getContent(), true);
        $this->assertIsArray($jsonData, 'Response should be valid JSON');
        $this->assertArrayHasKey('email_template_body', $jsonData, 'Response should contain template body');
        
        /* Assert - Special Characters Preserved */
        $this->assertStringContainsString('{{', $jsonData['email_template_body'], 'Template body should contain opening placeholder delimiters');
        $this->assertStringContainsString('}}', $jsonData['email_template_body'], 'Template body should contain closing placeholder delimiters');
        
        /* Assert - Database Verification */
        $records = $this->fakeDb->select('ip_email_templates', ['email_template_id' => $specialTemplate['email_template_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_email_templates'");
    }
    
    // #endregion
    
    // #region Validation

    /**
     * Test get content handles missing template ID
     */
    #[Test]
    public function it_handles_missing_template_id_via_get_content(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /email_templates/emailtemplatesajax/get_content
         * POST data: {}
         */
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', []);
        
        /* Assert */
        $response->assertStatus(400);
    }
    
    // #endregion
    
    // #region Security

    /**
     * Test get content protects against SQL injection
     */
    #[Test]
    public function it_protects_against_sql_injection_via_get_content(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /**
         * Act: POST /email_templates/emailtemplatesajax/get_content
         * POST data: {
         *   "email_template_id": "1 OR 1=1; DROP TABLE ip_email_templates; --"
         * }
         */
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => "1 OR 1=1; DROP TABLE ip_email_templates; --",
        ]);
        
        /* Assert */
        // Verify SQL injection is prevented (Query Builder should parameterize)
        $records = $this->fakeDb->select('ip_email_templates', []);
        $this->assertCount(3, $records, "Database should have exactly 3 record(s) in 'ip_email_templates'");
    }

    /**
     * Test that AJAX controller flag is set
     */
    #[Test]
    public function it_has_ajax_controller_flag_set(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $reflection = new \ReflectionClass(EmailTemplatesAjaxController::class);
        $instance = $reflection->newInstance();
        $property = $reflection->getProperty('is_ajax');
        $property->setAccessible(true);
        
        /* Assert */
        $this->assertTrue($property->getValue($instance));
    }
    
    // #endregion
}
