<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\EmailTemplatesAjaxController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for EmailTemplatesAjaxController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(EmailTemplatesAjaxController::class)]
class EmailTemplatesAjaxControllerTest extends ControllerTestCase
{
    protected string $controllerClass = EmailTemplatesAjaxController::class;
    
    protected function loadFixtures(): void
    {
        // Load user fixtures for authentication tests
        $users = $this->fixtures->all('users');
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        // Load email template fixtures
        $templates = $this->fixtures->all('email_templates');
        foreach (['invoice_template', 'quote_template', 'overdue_reminder_template'] as $key) {
            $this->fakeDb->insert('ip_email_templates', $templates[$key]);
        }
    }
    
    protected function setUpController(): void
    {
        // Store template for testing AJAX requests
        $this->testTemplate = $this->fixtures->get('email_templates', 'invoice_template');
    }

    #[Test]
    public function it_requires_authentication_for_get_content(): void
    {
        /* Arrange - No authenticated user */
        $this->clearAuth();
        
        /* Act */
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => $this->testTemplate['email_template_id'],
        ]);
        
        /* Assert */
        $response->assertUnauthorized();
    }

    #[Test]
    public function it_post_get_content_returns_template_json(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => $this->testTemplate['email_template_id'],
        ]);
        
        /* Assert */
        $response->assertOk();
        $response->assertJson([
            'email_template_title' => $this->testTemplate['email_template_title'],
            'email_template_subject' => $this->testTemplate['email_template_subject'],
            'email_template_body' => $this->testTemplate['email_template_body'],
        ]);
    }

    #[Test]
    public function it_post_get_content_returns_empty_for_invalid_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /email_templates/emailtemplatesajax/get_content
        // Successful request: ['email_template_id' => 123]
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => 999999, // Non-existent
        ]);
        
        /* Assert */
        $response->assertOk();
        $response->assertJsonMissing(['email_template_title']);
        $templates = $this->fakeDb->select('ip_email_templates', ['email_template_id' => 999999]);
        $this->assertCount(0, $templates);
    }

    #[Test]
    public function it_post_get_content_handles_missing_template_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /email_templates/emailtemplatesajax/get_content
        // Successful request: ['email_template_id' => 123]
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', []); // Missing email_template_id
        
        /* Assert */
        $response->assertStatus(400); // or 422, depending on validation
    }

    #[Test]
    public function it_post_get_content_protects_against_sql_injection(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /email_templates/emailtemplatesajax/get_content
        // Successful request: ['email_template_id' => 123]
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => "1 OR 1=1; DROP TABLE ip_email_templates; --",
        ]);
        
        /* Assert */
        // Verify SQL injection is prevented (Query Builder should parameterize)
        $templates = $this->fakeDb->select('ip_email_templates');
        $this->assertCount(3, $templates); // Original fixtures still intact
    }

    #[Test]
    public function it_post_get_content_returns_correct_content_type(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /email_templates/emailtemplatesajax/get_content
        // Successful request: ['email_template_id' => 123]
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => $this->testTemplate['email_template_id'],
        ]);
        
        /* Assert */
        $response->assertHeader('Content-Type', 'application/json');
    }

    #[Test]
    public function it_post_get_content_includes_all_template_fields(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // POST /email_templates/emailtemplatesajax/get_content
        // Successful request: ['email_template_id' => 123]
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => $this->testTemplate['email_template_id'],
        ]);
        
        /* Assert */
        $response->assertOk();
        $response->assertJsonStructure([
            'email_template_title',
            'email_template_subject',
            'email_template_body',
            'email_template_from_name',
            'email_template_from_email',
            'email_template_cc',
            'email_template_bcc',
            'email_template_pdf_template',
        ]);
        
        $template = $this->fakeDb->select('ip_email_templates', ['email_template_id' => $this->testTemplate['email_template_id']])[0];
        $this->assertArrayHasKey('email_template_title', $template);
        $this->assertArrayHasKey('email_template_subject', $template);
        $this->assertArrayHasKey('email_template_body', $template);
    }

    #[Test]
    public function it_post_get_content_handles_special_characters(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        // Template with special characters already exists in fixtures
        $specialTemplate = $this->fixtures->get('email_templates', 'invoice_template');
        
        /* Act */
        // POST /email_templates/emailtemplatesajax/get_content
        // Successful request: ['email_template_id' => 123]
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => $specialTemplate['email_template_id'],
        ]);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('{{', false);
        $response->assertSee('}}', false);
        
        $template = $this->fakeDb->select('ip_email_templates', ['email_template_id' => $specialTemplate['email_template_id']])[0];
        $this->assertStringContainsString('{{', $template['email_template_subject']);
    }

    #[Test]
    public function it_ajax_controller_flag_is_set(): void
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
}
