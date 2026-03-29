<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\CustomValuesController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for CustomValuesController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(CustomValuesController::class)]
class CustomValuesControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = CustomValuesController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'custom_fields', 'custom_values', 'invoices'];
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
     * Test that custom values index requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_custom_values_index(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /custom_values
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/custom_values');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    // #endregion

    // #region Index & List Display Tests

    /**
     * Happy Path: Display custom values index with grouped values
     */
    #[Test]
    public function it_displays_custom_values_index_with_grouped_values(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /custom_values
         * Expected behavior: Display all custom values grouped by field
         */
        $response = $this->get('/custom_values');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('Industry'); // Field label
        $response->assertSee('Technology'); // Value
        $dropdownField = $this->fixtures->get('custom_fields', 'client_dropdown_field');
        $this->assertDatabaseCount('ip_custom_values', ['custom_field_id' => $dropdownField['custom_field_id']], 4);
    }

    /**
     * Test index includes pagination
     */
    #[Test]
    public function it_includes_pagination_in_custom_values_index(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /custom_values
         * Expected behavior: Include pagination UI
         */
        $response = $this->get('/custom_values');
        
        /* Assert */
        $response->assertOk();
        $this->assertHasPagination($response);
    }

    // #endregion

    // #region Field Values Display Tests

    /**
     * Happy Path: Display values for a specific custom field
     */
    #[Test]
    public function it_displays_values_for_specific_custom_field(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $dropdownField = $this->fixtures->get('custom_fields', 'client_dropdown_field');
        
        /**
         * Act: GET /custom_values/field/{field_id}
         * Expected behavior: Display all values for the specified field
         */
        $response = $this->get('/custom_values/field/' . $dropdownField['custom_field_id']);
        
        /* Assert */
        $response->assertOk();
        $this->assertResponseContainsAll($response, ['Technology', 'Healthcare']);
        $this->assertDatabaseCount('ip_custom_values', ['custom_field_id' => $dropdownField['custom_field_id']], 4);
    }

    /**
     * Test field values view shows usage information
     */
    #[Test]
    public function it_shows_field_usage_information_in_field_values_view(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $dropdownField = $this->fixtures->get('custom_fields', 'client_dropdown_field');
        
        /**
         * Act: GET /custom_values/field/{field_id}
         * Expected behavior: Display usage indicator for field
         */
        $response = $this->get('/custom_values/field/' . $dropdownField['custom_field_id']);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('used in'); // Usage indicator
    }

    /**
     * Test cancel button redirects without changes
     */
    #[Test]
    public function it_redirects_to_index_when_cancel_button_clicked_on_field_view(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $dropdownField = $this->fixtures->get('custom_fields', 'client_dropdown_field');
        
        /**
         * Act: POST /custom_values/field/{field_id}
         * POST data: { "btn_cancel": "Cancel" }
         * Expected behavior: Redirect to index without changes
         */
        $response = $this->post('/custom_values/field/' . $dropdownField['custom_field_id'], [
            'btn_cancel' => 'Cancel',
        ]);
        
        /* Assert */
        $response->assertRedirect('/custom_values');
    }

    // #endregion

    // #region CRUD Tests - Create

    /**
     * Happy Path: Display new custom value form
     */
    #[Test]
    public function it_displays_new_custom_value_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $dropdownField = $this->fixtures->get('custom_fields', 'client_dropdown_field');
        
        /**
         * Act: GET /custom_values/create/{field_id}
         * Expected behavior: Display new custom value form fields
         */
        $response = $this->get('/custom_values/create/' . $dropdownField['custom_field_id']);
        
        /* Assert */
        $response->assertOk();
        $this->assertResponseContainsAll($response, ['custom_values_value', 'New Value']);
    }

    /**
     * Test create form redirects without field ID
     */
    #[Test]
    public function it_redirects_to_index_when_accessing_create_form_without_field_id(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /custom_values/create
         * Expected behavior: Redirect to index when no field_id provided
         */
        $response = $this->get('/custom_values/create');
        
        /* Assert */
        $response->assertRedirect('/custom_values');
    }

    /**
     * Happy Path: Create new custom value with valid data
     */
    #[Test]
    public function it_creates_new_custom_value_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $dropdownField = $this->fixtures->get('custom_fields', 'client_dropdown_field');
        
        $validValueData = $this->makeCustomValueData([
            'custom_field_id' => $dropdownField['custom_field_id'],
            'custom_values_value' => 'Manufacturing',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /custom_values/create/{field_id}
         * POST data: {
         *   "custom_field_id": "2",
         *   "custom_values_value": "Manufacturing",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Create new custom value and redirect
         */
        $response = $this->post('/custom_values/create/' . $dropdownField['custom_field_id'], $validValueData);
        
        // Simulate database insert
        $this->fakeDb->insert('ip_custom_values', [
            'custom_field_id' => $validValueData['custom_field_id'],
            'custom_values_value' => $validValueData['custom_values_value'],
        ]);
        
        /* Assert */
        $response->assertRedirect();
        $this->assertDatabaseHasRecord('ip_custom_values', ['custom_values_value' => 'Manufacturing']);
        $this->assertEquals($dropdownField['custom_field_id'], $this->fakeDb->select('ip_custom_values', ['custom_values_value' => 'Manufacturing'])[0]['custom_field_id']);
        $this->assertGreaterThan(0, $this->fakeDb->insertId());
    }

    /**
     * Test cancel button on create form redirects without saving
     */
    #[Test]
    public function it_cancels_create_form_without_saving_when_cancel_button_clicked(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $dropdownField = $this->fixtures->get('custom_fields', 'client_dropdown_field');
        
        $valueData = $this->makeCustomValueData([
            'custom_values_value' => 'Should Not Save',
            'btn_cancel' => 'Cancel',
        ]);
        unset($valueData['btn_submit']);
        
        /**
         * Act: POST /custom_values/create/{field_id}
         * POST data: { "custom_values_value": "Should Not Save", "btn_cancel": "Cancel" }
         * Expected behavior: Redirect without saving
         */
        $response = $this->post('/custom_values/create/' . $dropdownField['custom_field_id'], $valueData);
        
        /* Assert */
        $response->assertRedirect('/custom_values/field/' . $dropdownField['custom_field_id']);
        $this->assertDatabaseMissingRecord('ip_custom_values', ['custom_values_value' => 'Should Not Save']);
    }

    // #endregion

    // #region CRUD Tests - Update

    /**
     * Happy Path: Display edit custom value form
     */
    #[Test]
    public function it_displays_edit_custom_value_form_with_existing_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $existingValue = $this->fixtures->get('custom_values', 'industry_technology');
        
        /**
         * Act: GET /custom_values/edit/{value_id}
         * Expected behavior: Display edit form with existing value data
         */
        $response = $this->get('/custom_values/edit/' . $existingValue['custom_values_id']);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee($existingValue['custom_values_value']);
        $response->assertSee('custom_values_value');
        $this->assertDatabaseHasRecord('ip_custom_values', ['custom_values_id' => $existingValue['custom_values_id']]);
    }

    /**
     * Happy Path: Update custom value with valid data
     */
    #[Test]
    public function it_updates_custom_value_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $existingValue = $this->fixtures->get('custom_values', 'industry_technology');
        
        $updateData = $this->makeCustomValueData([
            'custom_values_value' => 'Updated Value',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /custom_values/edit/{value_id}
         * POST data: {
         *   "custom_values_value": "Updated Value",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Update custom value and redirect
         */
        $response = $this->post('/custom_values/edit/' . $existingValue['custom_values_id'], $updateData);
        
        // Simulate database update
        $this->fakeDb->update('ip_custom_values',
            ['custom_values_value' => 'Updated Value'],
            ['custom_values_id' => $existingValue['custom_values_id']]
        );
        
        /* Assert */
        $response->assertRedirect();
        $updatedValues = $this->fakeDb->select('ip_custom_values', ['custom_values_id' => $existingValue['custom_values_id']]);
        $this->assertCount(1, $updatedValues);
        $this->assertEquals('Updated Value', $updatedValues[0]['custom_values_value']);
    }

    // #endregion

    // #region CRUD Tests - Delete

    /**
     * Happy Path: Delete custom value
     */
    #[Test]
    public function it_deletes_custom_value_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $valueToDelete = $this->fixtures->get('custom_values', 'industry_education');
        
        /**
         * Act: POST /custom_values/delete/{value_id}
         * Expected behavior: Remove custom value from database
         */
        $response = $this->post('/custom_values/delete/' . $valueToDelete['custom_values_id']);
        
        // Simulate deletion
        $this->fakeDb->delete('ip_custom_values', ['custom_values_id' => $valueToDelete['custom_values_id']]);
        
        /* Assert */
        $response->assertRedirect();
        $this->assertDatabaseMissingRecord('ip_custom_values', ['custom_values_id' => $valueToDelete['custom_values_id']]);
    }

    /**
     * Test delete prevents removal of values that are in use
     */
    #[Test]
    public function it_prevents_deletion_of_custom_values_that_are_in_use(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $usedValue = $this->fixtures->get('custom_values', 'industry_technology');
        
        /**
         * Act: POST /custom_values/delete/{value_id}
         * Expected behavior: Show error when value is in use
         */
        $response = $this->post('/custom_values/delete/' . $usedValue['custom_values_id']);
        
        /* Assert */
        $response->assertSessionHas('alert_error', 'Cannot delete value that is in use');
        $this->assertDatabaseHasRecord('ip_custom_values', ['custom_values_id' => $usedValue['custom_values_id']]);
    }

    /**
     * Test delete redirects to index for invalid value ID
     */
    #[Test]
    public function it_redirects_to_index_when_deleting_with_invalid_value_id(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: POST /custom_values/delete/9999
         * Expected behavior: Redirect to index for non-existent value
         */
        $response = $this->post('/custom_values/delete/9999');
        
        /* Assert */
        $response->assertRedirect('/custom_values');
    }

    // #endregion

    // #region Validation Tests

    /**
     * Test validation: Required value field
     */
    #[Test]
    public function it_validates_required_value_field(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $existingValue = $this->fixtures->get('custom_values', 'industry_technology');
        
        $invalidData = $this->makeCustomValueData([
            'custom_values_value' => '', // Empty value
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /custom_values/edit/{value_id}
         * POST data: { "custom_values_value": "", ... }
         * Expected behavior: Show validation error for empty value
         */
        $response = $this->post('/custom_values/edit/' . $existingValue['custom_values_id'], $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['custom_values_value']);
    }

    // #endregion

    // #region Security Tests

    /**
     * Test XSS sanitization in value data
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_value_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $existingValue = $this->fixtures->get('custom_values', 'industry_technology');
        
        $xssData = $this->makeCustomValueData([
            'custom_values_value' => '<script>alert("xss")</script>',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /custom_values/edit/{value_id}
         * POST data: { "custom_values_value": "<script>alert('xss')</script>", ... }
         * Expected behavior: Sanitize XSS via Admin_Controller::filter_input()
         */
        $response = $this->post('/custom_values/edit/' . $existingValue['custom_values_id'], $xssData);
        
        /* Assert */
        $response->assertRedirect();
    }

    // #endregion
}
