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
         */
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => $this->testTemplate['email_template_id'],
        ]);
        
        /* Assert */
        $this->assertJsonResponse($response);
        $response->assertJson([
            'email_template_title' => $this->testTemplate['email_template_title'],
            'email_template_subject' => $this->testTemplate['email_template_subject'],
            'email_template_body' => $this->testTemplate['email_template_body'],
        ]);
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
         */
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => 999999,
        ]);
        
        /* Assert */
        $this->assertSuccessful($response);
        $response->assertJsonMissing(['email_template_title']);
        $this->assertDatabaseMissingRecord('ip_email_templates', ['email_template_id' => 999999]);
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
         */
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => $this->testTemplate['email_template_id'],
        ]);
        
        /* Assert */
        $this->assertJsonResponse($response);
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
         */
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => $this->testTemplate['email_template_id'],
        ]);
        
        /* Assert */
        $this->assertSuccessful($response);
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
        
        $this->assertDatabaseHasRecord('ip_email_templates', [
            'email_template_id' => $this->testTemplate['email_template_id']
        ]);
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
         */
        $response = $this->post('/email_templates/emailtemplatesajax/get_content', [
            'email_template_id' => $specialTemplate['email_template_id'],
        ]);
        
        /* Assert */
        $this->assertSuccessful($response);
        $response->assertSee('{{', false);
        $response->assertSee('}}', false);
        
        $this->assertDatabaseHasRecord('ip_email_templates', [
            'email_template_id' => $specialTemplate['email_template_id']
        ]);
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
        $this->assertDatabaseCount('ip_email_templates', [], 3);
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
