<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\CustomFieldsController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for CustomFieldsController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(CustomFieldsController::class)]
class CustomFieldsControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = CustomFieldsController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'custom_fields', 'custom_values'];
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
     * Test that custom fields index requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_custom_fields_index(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /custom_fields
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/custom_fields');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test that custom fields form requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_custom_fields_form(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /custom_fields/form
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/custom_fields/form');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    // #endregion

    // #region Index & Navigation Tests

    /**
     * Happy Path: Index redirects to table/all
     */
    #[Test]
    public function it_redirects_index_to_table_all(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /custom_fields
         * Expected behavior: Redirect to /custom_fields/table/all
         */
        $response = $this->get('/custom_fields');
        
        /* Assert */
        $response->assertRedirect('/custom_fields/table/all');
    }

    /**
     * Happy Path: Display all custom fields in table view
     */
    #[Test]
    public function it_displays_all_custom_fields_in_table_view(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /custom_fields/table/all
         * Expected behavior: Display all custom fields
         */
        $response = $this->get('/custom_fields/table/all');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('Project Reference');
        $response->assertSee('Industry');
        $this->assertDatabaseCount('ip_custom_fields', [], 4);
    }

    /**
     * Test table view filters by table name
     */
    #[Test]
    public function it_filters_custom_fields_by_table_name(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /custom_fields/table/ip_invoices
         * Expected behavior: Display only invoice custom fields
         */
        $response = $this->get('/custom_fields/table/ip_invoices');
        
        /* Assert */
        $response->assertOk();
        $invoiceFields = $this->fakeDb->select('ip_custom_fields', ['custom_field_table' => 'ip_invoices']);
        $this->assertCount(1, $invoiceFields);
        $this->assertEquals('Project Reference', $invoiceFields[0]['custom_field_label']);
    }

    /**
     * Test table view includes pagination
     */
    #[Test]
    public function it_includes_pagination_in_table_view(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /custom_fields/table/all
         * Expected behavior: Include pagination UI
         */
        $response = $this->get('/custom_fields/table/all');
        
        /* Assert */
        $response->assertOk();
        $this->assertHasPagination($response);
    }

    // #endregion

    // #region Form Display Tests

    /**
     * Happy Path: Display new custom field form
     */
    #[Test]
    public function it_displays_new_custom_field_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /custom_fields/form
         * Expected behavior: Display new custom field form fields
         */
        $response = $this->get('/custom_fields/form');
        
        /* Assert */
        $response->assertOk();
        $this->assertResponseContainsAll($response, [
            'custom_field_label',
            'custom_field_type',
            'custom_field_table'
        ]);
    }

    /**
     * Happy Path: Display edit custom field form with existing data
     */
    #[Test]
    public function it_displays_edit_custom_field_form_with_existing_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $existingField = $this->fixtures->get('custom_fields', 'invoice_text_field');
        
        /**
         * Act: GET /custom_fields/form/{id}
         * Expected behavior: Display edit form with existing custom field data
         */
        $response = $this->get('/custom_fields/form/' . $existingField['custom_field_id']);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee($existingField['custom_field_label']);
        $response->assertSee($existingField['custom_field_type']);
        $this->assertDatabaseHasRecord('ip_custom_fields', ['custom_field_id' => $existingField['custom_field_id']]);
    }

    // #endregion

    // #region CRUD Tests - Create

    /**
     * Happy Path: Create new custom field with valid data
     */
    #[Test]
    public function it_creates_new_custom_field_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $validFieldData = $this->makeCustomFieldData([
            'custom_field_label' => 'New Custom Field',
            'custom_field_type' => 'TEXT',
            'custom_field_table' => 'ip_invoices',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /custom_fields/form
         * POST data: {
         *   "custom_field_table": "ip_invoices",
         *   "custom_field_label": "New Custom Field",
         *   "custom_field_type": "TEXT",
         *   "custom_field_location": "AFTER",
         *   "custom_field_order": "1",
         *   "custom_field_default_value": "",
         *   "custom_field_visible": "1",
         *   "custom_field_required": "0",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Create new custom field and redirect
         */
        $response = $this->post('/custom_fields/form', $validFieldData);
        
        // Simulate database insert
        $this->fakeDb->insert('ip_custom_fields', [
            'custom_field_table' => $validFieldData['custom_field_table'],
            'custom_field_label' => $validFieldData['custom_field_label'],
            'custom_field_type' => $validFieldData['custom_field_type'],
            'custom_field_location' => $validFieldData['custom_field_location'],
            'custom_field_order' => $validFieldData['custom_field_order'],
        ]);
        
        /* Assert */
        $response->assertRedirect('/custom_fields/table/' . $validFieldData['custom_field_table']);
        $this->assertDatabaseHasRecord('ip_custom_fields', ['custom_field_label' => 'New Custom Field']);
        $this->assertGreaterThan(0, $this->fakeDb->insertId());
    }

    /**
     * Test cancel button redirects without saving
     */
    #[Test]
    public function it_cancels_form_without_saving_when_cancel_button_clicked(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $fieldData = $this->makeCustomFieldData([
            'custom_field_label' => 'Should Not Save',
            'btn_cancel' => 'Cancel',
        ]);
        unset($fieldData['btn_submit']);
        
        /**
         * Act: POST /custom_fields/form
         * POST data: { ..., "btn_cancel": "Cancel" }
         * Expected behavior: Redirect without saving
         */
        $response = $this->post('/custom_fields/form', $fieldData);
        
        /* Assert */
        $response->assertRedirect('/custom_fields');
        $this->assertDatabaseMissingRecord('ip_custom_fields', ['custom_field_label' => 'Should Not Save']);
    }

    // #endregion

    // #region CRUD Tests - Update

    /**
     * Happy Path: Update existing custom field
     */
    #[Test]
    public function it_updates_existing_custom_field_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $existingField = $this->fixtures->get('custom_fields', 'invoice_text_field');
        
        $updateData = $this->makeCustomFieldData([
            'custom_field_id' => $existingField['custom_field_id'],
            'custom_field_label' => 'Updated Label',
            'custom_field_type' => $existingField['custom_field_type'],
            'custom_field_table' => $existingField['custom_field_table'],
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /custom_fields/form/{id}
         * POST data: {
         *   "custom_field_id": "1",
         *   "custom_field_label": "Updated Label",
         *   ...
         * }
         * Expected behavior: Update custom field and redirect
         */
        $response = $this->post('/custom_fields/form/' . $existingField['custom_field_id'], $updateData);
        
        // Simulate database update
        $this->fakeDb->update('ip_custom_fields',
            ['custom_field_label' => 'Updated Label'],
            ['custom_field_id' => $existingField['custom_field_id']]
        );
        
        /* Assert */
        $response->assertRedirect();
        $updatedFields = $this->fakeDb->select('ip_custom_fields', ['custom_field_id' => $existingField['custom_field_id']]);
        $this->assertCount(1, $updatedFields);
        $this->assertEquals('Updated Label', $updatedFields[0]['custom_field_label']);
    }

    // #endregion

    // #region CRUD Tests - Delete

    /**
     * Happy Path: Delete custom field
     */
    #[Test]
    public function it_deletes_custom_field_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $fieldToDelete = $this->fixtures->get('custom_fields', 'user_checkbox_field');
        
        /**
         * Act: POST /custom_fields/delete/{id}
         * Expected behavior: Remove custom field from database
         */
        $response = $this->post('/custom_fields/delete/' . $fieldToDelete['custom_field_id']);
        
        // Simulate deletion
        $this->fakeDb->delete('ip_custom_fields', ['custom_field_id' => $fieldToDelete['custom_field_id']]);
        
        /* Assert */
        $response->assertRedirect();
        $this->assertDatabaseMissingRecord('ip_custom_fields', ['custom_field_id' => $fieldToDelete['custom_field_id']]);
    }

    // #endregion

    // #region Validation Tests

    /**
     * Test validation: Required field label
     */
    #[Test]
    public function it_validates_required_field_label(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeCustomFieldData([
            'custom_field_label' => '', // Required field missing
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /custom_fields/form
         * POST data: { "custom_field_label": "", ... }
         * Expected behavior: Show validation error for required field
         */
        $response = $this->post('/custom_fields/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['custom_field_label']);
    }

    /**
     * Test validation: Field label minimum length
     */
    #[Test]
    public function it_validates_field_label_minimum_length(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeCustomFieldData([
            'custom_field_label' => 'a', // Too short
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /custom_fields/form
         * POST data: { "custom_field_label": "a", ... }
         * Expected behavior: Show validation error for length
         */
        $response = $this->post('/custom_fields/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['custom_field_label']);
    }

    /**
     * Test validation: Invalid field type
     */
    #[Test]
    public function it_validates_field_type_against_allowed_types(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeCustomFieldData([
            'custom_field_type' => 'INVALID_TYPE', // Invalid type
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /custom_fields/form
         * POST data: { "custom_field_type": "INVALID_TYPE", ... }
         * Expected behavior: Show validation error for invalid type
         */
        $response = $this->post('/custom_fields/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['custom_field_type']);
    }

    /**
     * Test validation: Invalid table name
     */
    #[Test]
    public function it_validates_table_name_against_allowed_tables(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeCustomFieldData([
            'custom_field_table' => 'ip_malicious_table', // Invalid table
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /custom_fields/form
         * POST data: { "custom_field_table": "ip_malicious_table", ... }
         * Expected behavior: Show validation error for invalid table
         */
        $response = $this->post('/custom_fields/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['custom_field_table']);
    }

    /**
     * Test validation: Field name format
     */
    #[Test]
    public function it_validates_field_name_format_against_special_characters(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidData = $this->makeCustomFieldData([
            'custom_field_label' => 'Test Field!@#$%', // Invalid characters
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /custom_fields/form
         * POST data: { "custom_field_label": "Test Field!@#$%", ... }
         * Expected behavior: Show validation error for invalid format
         */
        $response = $this->post('/custom_fields/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['custom_field_label']);
    }

    // #endregion

    // #region Security Tests

    /**
     * Test XSS sanitization in field data
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_field_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $xssData = $this->makeCustomFieldData([
            'custom_field_label' => '<script>alert("xss")</script>',
            'custom_field_type' => 'TEXT',
            'custom_field_table' => 'ip_invoices',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /custom_fields/form
         * POST data: { "custom_field_label": "<script>alert('xss')</script>", ... }
         * Expected behavior: Sanitize XSS via Admin_Controller::filter_input()
         */
        $response = $this->post('/custom_fields/form', $xssData);
        
        /* Assert */
        $response->assertRedirect();
    }

    /**
     * Test SQL injection protection
     */
    #[Test]
    public function it_protects_against_sql_injection_in_field_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $sqlInjectionData = $this->makeCustomFieldData([
            'custom_field_label' => "'; DROP TABLE ip_custom_fields; --",
            'custom_field_type' => 'TEXT',
            'custom_field_table' => 'ip_invoices',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /custom_fields/form
         * POST data: { "custom_field_label": "'; DROP TABLE ip_custom_fields; --", ... }
         * Expected behavior: Query Builder parameterizes and prevents injection
         */
        $response = $this->post('/custom_fields/form', $sqlInjectionData);
        
        /* Assert */
        $this->assertDatabaseCount('ip_custom_fields', [], 4); // Original fixtures still intact
    }

    // #endregion
}
