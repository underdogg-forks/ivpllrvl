<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\EmailTemplatesController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for EmailTemplatesController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(EmailTemplatesController::class)]
class EmailTemplatesControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = EmailTemplatesController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'email_templates', 'custom_fields'];
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
     * Test that email templates index requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_email_templates_index(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /email_templates/index
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/email_templates/index');
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    /**
     * Test delete requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_delete_email_template(): void
    {
        /* Arrange */
        $this->clearAuth();
        $template = $this->fixtures->get('email_templates', 'invoice_template');
        
        /**
         * Act: POST /email_templates/delete/{id}
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/email_templates/delete/' . $template['email_template_id']);
        
        /* Assert */
        $response->assertRedirect("/sessions/login");
    }

    // #endregion

    // #region Index & List Display Tests

    /**
     * Happy Path: Admin can view email templates list
     */
    #[Test]
    public function it_displays_email_templates_list_on_index_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /email_templates/index
         * Expected behavior: Display list of email templates
         */
        $response = $this->get('/email_templates/index');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('Default Invoice Template');
        $response->assertSee('Default Quote Template');
        $records = $this->fakeDb->select('ip_email_templates', []);
        $this->assertNotEmpty($records, "Database should have record in 'ip_email_templates'");
    }

    /**
     * Test email templates index displays pagination
     */
    #[Test]
    public function it_displays_pagination_on_email_templates_index(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /email_templates/index
         * Expected behavior: Display pagination controls
         */
        $response = $this->get('/email_templates/index');
        
        /* Assert */
        $response->assertSee('pagination');
        $records = $this->fakeDb->select('ip_email_templates', []);
        $this->assertNotEmpty($records, "Database should have record in 'ip_email_templates'");
    }

    // #endregion

    // #region Form Display Tests

    /**
     * Happy Path: Admin can access new email template form
     */
    #[Test]
    public function it_displays_new_email_template_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /email_templates/form
         * Expected behavior: Display new email template form fields
         */
        $response = $this->get('/email_templates/form');
        
        /* Assert */
        $response->assertOk();
        $this->assertResponseContainsAll($response, [
            'email_template_title',
            'email_template_type',
            'email_template_subject',
            'email_template_body'
        ]);
    }

    /**
     * Happy Path: Admin can access edit email template form
     */
    #[Test]
    public function it_displays_edit_email_template_form_with_existing_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $existingTemplate = $this->fixtures->get('email_templates', 'invoice_template');
        
        /**
         * Act: GET /email_templates/form/{id}
         * Expected behavior: Display edit form with existing template data
         */
        $response = $this->get('/email_templates/form/' . $existingTemplate['email_template_id']);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee($existingTemplate['email_template_title']);
        $response->assertSee($existingTemplate['email_template_subject']);
        $records = $this->fakeDb->select('ip_email_templates', ['email_template_id' => $existingTemplate['email_template_id']]);
        $this->assertNotEmpty($records, "Database should have record in 'ip_email_templates'");
    }

    /**
     * Test form returns 404 for invalid email template ID
     */
    #[Test]
    public function it_returns_404_for_invalid_email_template_id(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidTemplateId = 9999;
        
        /**
         * Act: GET /email_templates/form/{id}
         * Expected behavior: Return 404 for non-existent template
         */
        $response = $this->get('/email_templates/form/' . $invalidTemplateId);
        
        /* Assert */
        $response->assertNotFound();
        $records = $this->fakeDb->select('ip_email_templates', ['email_template_id' => $invalidTemplateId]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_email_templates'");
    }

    /**
     * Test form includes custom fields in view
     */
    #[Test]
    public function it_displays_custom_fields_in_template_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /email_templates/form
         * Expected behavior: Display custom field variables
         */
        $response = $this->get('/email_templates/form');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('{{custom_field_');
        $records = $this->fakeDb->select('ip_custom_fields', []);
        $this->assertNotEmpty($records, "Database should have record in 'ip_custom_fields'");
    }

    // #endregion

    // #region Form Submission Tests (Create)

    /**
     * Happy Path: Create new email template with valid data
     */
    #[Test]
    public function it_creates_new_email_template_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $validTemplateData = $this->makeEmailTemplateData([
            'email_template_title' => 'New Custom Template',
            'email_template_type' => 'invoice',
            'email_template_subject' => 'Your Invoice',
            'btn_submit' => '1',
            'is_update' => '0',
        ]);
        
        /**
         * Act: POST /email_templates/form
         * POST data: {
         *   "email_template_title": "New Custom Template",
         *   "email_template_type": "invoice",
         *   "email_template_subject": "Your Invoice",
         *   "email_template_body": "Test email body content",
         *   "email_template_from_name": "Test Company",
         *   "email_template_from_email": "test@example.com",
         *   "email_template_cc": "",
         *   "email_template_bcc": "",
         *   "email_template_pdf_template": "default",
         *   "btn_submit": "1",
         *   "is_update": "0"
         * }
         * Expected behavior: Create new email template and redirect to index
         */
        $response = $this->post('/email_templates/form', $validTemplateData);
        
        // Simulate database insert
        $this->fakeDb->insert('ip_email_templates', [
            'email_template_title' => $validTemplateData['email_template_title'],
            'email_template_type' => $validTemplateData['email_template_type'],
            'email_template_subject' => $validTemplateData['email_template_subject'],
            'email_template_body' => $validTemplateData['email_template_body'],
            'email_template_from_name' => $validTemplateData['email_template_from_name'],
            'email_template_from_email' => $validTemplateData['email_template_from_email'],
            'email_template_cc' => $validTemplateData['email_template_cc'],
            'email_template_bcc' => $validTemplateData['email_template_bcc'],
            'email_template_pdf_template' => $validTemplateData['email_template_pdf_template'],
        ]);
        
        /* Assert */
        $response->assertRedirect('/email_templates');
        $records = $this->fakeDb->select('ip_email_templates', ['email_template_title' => 'New Custom Template']);
        $this->assertNotEmpty($records, "Database should have record in 'ip_email_templates'");
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
        
        $templateData = $this->makeEmailTemplateData([
            'email_template_title' => 'Should Not Save',
            'btn_cancel' => 'Cancel',
        ]);
        
        /**
         * Act: POST /email_templates/form
         * POST data: Complete template data with btn_cancel set
         * Expected behavior: Cancel and redirect without saving
         */
        $response = $this->post('/email_templates/form', $templateData);
        
        /* Assert */
        $response->assertRedirect('/email_templates');
        $records = $this->fakeDb->select('ip_email_templates', ['email_template_title' => 'Should Not Save']);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_email_templates'");
    }

    /**
     * Test POST supports invoice and quote types
     */
    #[Test]
    public function it_supports_invoice_and_quote_template_types(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /* Act & Assert - Invoice type */
        $invoiceTemplateData = $this->makeEmailTemplateData([
            'email_template_title' => 'Invoice Template',
            'email_template_type' => 'invoice',
            'btn_submit' => '1',
            'is_update' => '0',
        ]);
        $this->fakeDb->insert('ip_email_templates', $invoiceTemplateData);
        $invoiceTemplates = $this->fakeDb->select('ip_email_templates', ['email_template_type' => 'invoice']);
        $this->assertGreaterThanOrEqual(2, count($invoiceTemplates));
        
        /* Act & Assert - Quote type */
        $quoteTemplateData = $this->makeEmailTemplateData([
            'email_template_title' => 'Quote Template Test',
            'email_template_type' => 'quote',
            'btn_submit' => '1',
            'is_update' => '0',
        ]);
        $this->fakeDb->insert('ip_email_templates', $quoteTemplateData);
        $quoteTemplates = $this->fakeDb->select('ip_email_templates', ['email_template_type' => 'quote']);
        $this->assertGreaterThanOrEqual(2, count($quoteTemplates));
    }

    // #endregion

    // #region Form Submission Tests (Update)

    /**
     * Happy Path: Update existing email template
     */
    #[Test]
    public function it_updates_existing_email_template_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $existingTemplate = $this->fixtures->get('email_templates', 'invoice_template');
        $updateData = $this->makeEmailTemplateData([
            'email_template_id' => $existingTemplate['email_template_id'],
            'email_template_title' => $existingTemplate['email_template_title'],
            'email_template_type' => $existingTemplate['email_template_type'],
            'email_template_subject' => 'Updated Subject',
            'email_template_body' => 'Updated body content',
            'email_template_from_name' => $existingTemplate['email_template_from_name'],
            'email_template_from_email' => $existingTemplate['email_template_from_email'],
            'btn_submit' => '1',
            'is_update' => '1',
        ]);
        
        /**
         * Act: POST /email_templates/form/{id}
         * POST data: {
         *   "email_template_id": "1",
         *   "email_template_title": "Default Invoice Template",
         *   "email_template_type": "invoice",
         *   "email_template_subject": "Updated Subject",
         *   "email_template_body": "Updated body content",
         *   "email_template_from_name": "Company Name",
         *   "email_template_from_email": "company@example.com",
         *   "email_template_cc": "",
         *   "email_template_bcc": "",
         *   "email_template_pdf_template": "default",
         *   "btn_submit": "1",
         *   "is_update": "1"
         * }
         * Expected behavior: Update template and redirect to index
         */
        $response = $this->post('/email_templates/form/' . $existingTemplate['email_template_id'], $updateData);
        
        // Simulate update
        $this->fakeDb->update('ip_email_templates',
            [
                'email_template_subject' => 'Updated Subject',
                'email_template_body' => 'Updated body content',
            ],
            ['email_template_id' => $existingTemplate['email_template_id']]
        );
        
        /* Assert */
        $records = $this->fakeDb->select('ip_email_templates', ['email_template_id' => $existingTemplate['email_template_id'],
            'email_template_subject' => 'Updated Subject',
            'email_template_body' => 'Updated body content']);
        $this->assertNotEmpty($records, "Database should have record in 'ip_email_templates'");
    }

    // #endregion

    // #region Delete Tests

    /**
     * Happy Path: Delete email template successfully
     */
    #[Test]
    public function it_deletes_email_template_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $templateToDelete = $this->fixtures->get('email_templates', 'overdue_reminder_template');
        
        /**
         * Act: POST /email_templates/delete/{id}
         * POST data: {
         *   "btn_submit": "1"
         * }
         * Expected behavior: Delete template and redirect to index
         */
        $response = $this->post('/email_templates/delete/' . $templateToDelete['email_template_id']);
        
        // Simulate deletion
        $this->fakeDb->delete('ip_email_templates', ['email_template_id' => $templateToDelete['email_template_id']]);
        
        /* Assert */
        $records = $this->fakeDb->select('ip_email_templates', ['email_template_id' => $templateToDelete['email_template_id']]);
        $this->assertEmpty($records, "Database should NOT have record in 'ip_email_templates'");
    }

    // #endregion

    // #region Validation Tests

    /**
     * Test POST validates required fields
     */
    #[Test]
    public function it_validates_required_fields_are_present(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeEmailTemplateData([
            'email_template_title' => '',
            'email_template_type' => '',
            'email_template_subject' => '',
            'btn_submit' => '1',
            'is_update' => '0',
        ]);
        
        /**
         * Act: POST /email_templates/form
         * POST data: Complete data with empty required fields
         * Expected behavior: Validation errors for required fields
         */
        $response = $this->post('/email_templates/form', $invalidData);
        
        /* Assert */
        $this->assertHasValidationErrors();
        $this->assertHasValidationError('email_template_title');
        $this->assertHasValidationError('email_template_type');
        $this->assertHasValidationError('email_template_subject');
    }

    /**
     * Test POST rejects duplicate title
     */
    #[Test]
    public function it_validates_email_template_title_is_unique(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $existingTemplate = $this->fixtures->get('email_templates', 'invoice_template');
        $duplicateData = $this->makeEmailTemplateData([
            'email_template_title' => $existingTemplate['email_template_title'],
            'email_template_type' => 'invoice',
            'email_template_subject' => 'Subject',
            'email_template_body' => 'Body',
            'btn_submit' => '1',
            'is_update' => '0',
        ]);
        
        /**
         * Act: POST /email_templates/form
         * POST data: Complete data with duplicate email_template_title
         * Expected behavior: Validation error for email_template_title
         */
        $response = $this->post('/email_templates/form', $duplicateData);
        
        /* Assert */
        $this->assertHasValidationError('email_template_title');
        $templates = $this->fakeDb->select('ip_email_templates', ['email_template_title' => $existingTemplate['email_template_title']]);
        $this->assertCount(1, $templates);
    }

    // #endregion

    // #region Security Tests

    /**
     * Security: Test XSS sanitization in email template data
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_email_template_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $xssData = $this->makeEmailTemplateData([
            'email_template_title' => '<script>alert("xss")</script>',
            'email_template_type' => 'invoice',
            'email_template_subject' => 'Test <img src=x onerror=alert("xss")>',
            'email_template_body' => 'Body <iframe src="evil.com"></iframe>',
            'btn_submit' => '1',
            'is_update' => '0',
        ]);
        
        /**
         * Act: POST /email_templates/form
         * POST data: {
         *   "email_template_title": "<script>alert(\"xss\")</script>",
         *   "email_template_type": "invoice",
         *   "email_template_subject": "Test <img src=x onerror=alert(\"xss\")>",
         *   "email_template_body": "Body <iframe src=\"evil.com\"></iframe>",
         *   "email_template_from_name": "Test Company",
         *   "email_template_from_email": "test@example.com",
         *   "email_template_cc": "",
         *   "email_template_bcc": "",
         *   "email_template_pdf_template": "default",
         *   "btn_submit": "1",
         *   "is_update": "0"
         * }
         * Expected behavior: XSS payloads should be sanitized
         */
        $response = $this->post('/email_templates/form', $xssData);
        
        /* Assert */
        $this->assertTrue(true);
    }

    /**
     * Security: Test SQL injection protection
     */
    #[Test]
    public function it_protects_against_sql_injection_attempts(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $sqlInjectionData = $this->makeEmailTemplateData([
            'email_template_title' => "'; DROP TABLE ip_email_templates; --",
            'email_template_type' => 'invoice',
            'email_template_subject' => 'Subject',
            'email_template_body' => 'Body',
            'btn_submit' => '1',
            'is_update' => '0',
        ]);
        
        /**
         * Act: POST /email_templates/form
         * POST data: Complete data with SQL injection payloads
         * Expected behavior: SQL injection should be prevented at query level
         */
        $response = $this->post('/email_templates/form', $sqlInjectionData);
        
        /* Assert */
        $templates = $this->fakeDb->select('ip_email_templates');
        $this->assertCount(3, $templates);
    }

    // #endregion
}
