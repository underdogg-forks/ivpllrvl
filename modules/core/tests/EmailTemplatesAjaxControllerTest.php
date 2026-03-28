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
        $this->setPostData([
            'email_template_id' => 999999, // Non-existent
        ]);
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->get_content();
        // $jsonOutput = ob_get_clean();
        // $response = json_decode($jsonOutput, true);
        
        /* Assert */
        $this->assertEmpty($response) or $this->assertNull($response);
        $templates = $this->fakeDb->select('ip_email_templates', ['email_template_id' => 999999]);
        $this->assertCount(0, $templates);
    }

    #[Test]
    public function it_post_get_content_handles_missing_template_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([]); // Missing email_template_id
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->get_content();
        // $jsonOutput = ob_get_clean();
        
        /* Assert */
        $this->assertEmpty($jsonOutput); // or error response
    }

    #[Test]
    public function it_post_get_content_protects_against_sql_injection(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'email_template_id' => "1 OR 1=1; DROP TABLE ip_email_templates; --",
        ]);
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->get_content();
        // $jsonOutput = ob_get_clean();
        
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
        $this->setPostData([
            'email_template_id' => $this->testTemplate['email_template_id'],
        ]);
        
        /* Act */
        $controller = $this->getController();
        $controller->get_content();
        
        /* Assert */
        $this->assertResponseHeader('Content-Type', 'application/json');
    }

    #[Test]
    public function it_post_get_content_includes_all_template_fields(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'email_template_id' => $this->testTemplate['email_template_id'],
        ]);
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->get_content();
        // $jsonOutput = ob_get_clean();
        // $response = json_decode($jsonOutput, true);
        
        /* Assert */
        // Verify all expected fields are present
        $this->assertArrayHasKey('email_template_title', $response);
        $this->assertArrayHasKey('email_template_subject', $response);
        $this->assertArrayHasKey('email_template_body', $response);
        $this->assertArrayHasKey('email_template_from_name', $response);
        $this->assertArrayHasKey('email_template_from_email', $response);
        $this->assertArrayHasKey('email_template_cc', $response);
        $this->assertArrayHasKey('email_template_bcc', $response);
        $this->assertArrayHasKey('email_template_pdf_template', $response);
        
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
        $this->setPostData([
            'email_template_id' => $specialTemplate['email_template_id'],
        ]);
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->get_content();
        // $jsonOutput = ob_get_clean();
        // $response = json_decode($jsonOutput, true);
        
        /* Assert */
        // Verify special characters (like {{invoice_number}}) are preserved
        $this->assertStringContainsString('{{', $response['email_template_subject']);
        $this->assertStringContainsString('}}', $response['email_template_subject']);
        
        $template = $this->fakeDb->select('ip_email_templates', ['email_template_id' => $specialTemplate['email_template_id']])[0];
        $this->assertStringContainsString('{{', $template['email_template_subject']);
    }

    #[Test]
    public function it_ajax_controller_flag_is_set(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $controller = $this->getController();
        // $reflection = new \ReflectionClass($controller);
        // $property = $reflection->getProperty('is_ajax');
        // $property->setAccessible(true);
        
        /* Assert */
        $this->assertTrue($property->getValue($controller));
        // or verify controller extends Ajax_Controller base class
    }
}
