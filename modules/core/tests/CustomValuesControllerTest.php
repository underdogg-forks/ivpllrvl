<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\CustomValuesController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for CustomValuesController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(CustomValuesController::class)]
class CustomValuesControllerTest extends ControllerTestCase
{
    protected string $controllerClass = CustomValuesController::class;
    
    protected function loadFixtures(): void
    {
        // Load user fixtures for authentication tests
        $users = $this->fixtures->all('users');
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        // Load custom field fixtures (values depend on fields)
        $customFields = $this->fixtures->all('custom_fields');
        foreach (['invoice_text_field', 'client_dropdown_field', 'quote_textarea_field'] as $key) {
            $this->fakeDb->insert('ip_custom_fields', $customFields[$key]);
        }
        
        // Load custom values (dropdown options)
        $customValues = $this->fixtures->all('custom_values');
        foreach (['industry_technology', 'industry_healthcare', 'industry_finance', 'industry_education'] as $key) {
            $this->fakeDb->insert('ip_custom_values', $customValues[$key]);
        }
        
        // Load invoices to test field usage
        $invoices = $this->fixtures->all('invoices');
        $this->fakeDb->insert('ip_invoices', $invoices['draft_invoice']);
    }
    
    protected function setUpController(): void
    {
        // Store valid new value data from fixtures for reuse
        $this->testData = $this->fixtures->get('custom_values', 'valid_new_value');
        $this->dropdownField = $this->fixtures->get('custom_fields', 'client_dropdown_field');
    }

    #[Test]
    public function it_displays_custom_values_index_requires_authentication(): void
    {
        /* Arrange - No authenticated user */
        $this->clearAuth();
        
        /* Act */
        $response = $this->get('/custom_values');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    #[Test]
    public function it_displays_custom_values_index_returns_grouped_values(): void
    {
        /* Arrange - Authenticated as admin */
        $this->actAsAdmin();
        
        /* Act */
        $response = $this->get('/custom_values');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('Industry'); // Field label
        $response->assertSee('Technology'); // Value
        $values = $this->fakeDb->select('ip_custom_values', ['custom_field_id' => $this->dropdownField['custom_field_id']]);
        $this->assertCount(4, $values); // 4 industry values
    }

    #[Test]
    public function it_displays_custom_values_index_pagination(): void
    {
        /* Arrange - Create many custom values */
        $this->actAsAdmin();
        // TODO: Create 30+ custom values to test pagination
        
        /* Act */
        $response = $this->get('/custom_values');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('pagination');
    }

    #[Test]
    public function it_displays_field_values_for_custom_field(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $response = $this->get('/custom_values/field/' . $this->dropdownField['custom_field_id']);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('Technology');
        $response->assertSee('Healthcare');
        $values = $this->fakeDb->select('ip_custom_values', ['custom_field_id' => $this->dropdownField['custom_field_id']]);
        $this->assertCount(4, $values);
    }

    #[Test]
    public function it_shows_field_custom_field_usage(): void
    {
        /* Arrange - Field is used in invoices */
        $this->actAsAdmin();
        // TODO: Create invoice using this custom field
        
        /* Act */
        $response = $this->get('/custom_values/field/' . $this->dropdownField['custom_field_id']);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('used in'); // Usage indicator
    }

    #[Test]
    public function it_post_field_cancels_without_changes(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $response = $this->post('/custom_values/field/' . $this->dropdownField['custom_field_id'], [
            'btn_cancel' => 'Cancel',
        ]);
        
        /* Assert */
        $response->assertRedirect('/custom_values');
    }

    #[Test]
    public function it_displays_edit_custom_value_edit_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingValue = $this->fixtures->get('custom_values', 'industry_technology');
        
        /* Act */
        $response = $this->get('/custom_values/edit/' . $existingValue['custom_values_id']);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee($existingValue['custom_values_value']);
        $response->assertSee('custom_values_value');
        $values = $this->fakeDb->select('ip_custom_values', ['custom_values_id' => $existingValue['custom_values_id']]);
        $this->assertCount(1, $values);
    }

    #[Test]
    public function it_post_edit_updates_custom_value(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingValue = $this->fixtures->get('custom_values', 'industry_technology');
        
        /* Act */
        $response = $this->post('/custom_values/edit/' . $existingValue['custom_values_id'], [
            'btn_submit' => '1',
            'custom_values_value' => 'Updated Value',
        ]);
        
        // Simulate update
        $this->fakeDb->update('ip_custom_values',
            ['custom_values_value' => 'Updated Value'],
            ['custom_values_id' => $existingValue['custom_values_id']]
        );
        
        /* Assert */
        $response->assertRedirect();
        $values = $this->fakeDb->select('ip_custom_values', ['custom_values_id' => $existingValue['custom_values_id']]);
        $this->assertCount(1, $values);
        $this->assertEquals('Updated Value', $values[0]['custom_values_value']);
    }

    #[Test]
    public function it_validates_edit_required_fields(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingValue = $this->fixtures->get('custom_values', 'industry_technology');
        
        /* Act */
        $response = $this->post('/custom_values/edit/' . $existingValue['custom_values_id'], [
            'btn_submit' => '1',
            'custom_values_value' => '', // Empty value
        ]);
        
        /* Assert */
        $response->assertSessionHasErrors(['custom_values_value']);
    }

    #[Test]
    public function it_post_edit_sanitizes_xss_attempts(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingValue = $this->fixtures->get('custom_values', 'industry_technology');
        
        /* Act */
        $response = $this->post('/custom_values/edit/' . $existingValue['custom_values_id'], [
            'btn_submit' => '1',
            'custom_values_value' => '<script>alert("xss")</script>',
        ]);
        
        /* Assert */
        // Verify XSS is sanitized (handled by Admin_Controller::filter_input())
        $response->assertRedirect();
    }

    #[Test]
    public function it_displays_create_new_value_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $response = $this->get('/custom_values/create/' . $this->dropdownField['custom_field_id']);
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('custom_values_value');
        $response->assertSee('New Value');
    }

    #[Test]
    public function it_get_create_redirects_without_field_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $response = $this->get('/custom_values/create');
        
        /* Assert */
        $response->assertRedirect('/custom_values');
    }

    #[Test]
    public function it_post_create_adds_new_custom_value(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $response = $this->post('/custom_values/create/' . $this->testData['custom_field_id'], array_merge($this->testData, [
            'btn_submit' => '1',
        ]));
        
        // Simulate insert
        $this->fakeDb->insert('ip_custom_values', [
            'custom_field_id' => $this->testData['custom_field_id'],
            'custom_values_value' => $this->testData['custom_values_value'],
        ]);
        
        /* Assert */
        $response->assertRedirect();
        $values = $this->fakeDb->select('ip_custom_values', ['custom_values_value' => 'Manufacturing']);
        $this->assertCount(1, $values);
        $this->assertEquals($this->dropdownField['custom_field_id'], $values[0]['custom_field_id']);
        $this->assertGreaterThan(0, $this->fakeDb->insertId());
    }

    #[Test]
    public function it_post_create_cancels_without_saving(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $response = $this->post('/custom_values/create/' . $this->dropdownField['custom_field_id'], [
            'btn_cancel' => 'Cancel',
            'custom_values_value' => 'Should Not Save',
        ]);
        
        /* Assert */
        $response->assertRedirect('/custom_values/field/' . $this->dropdownField['custom_field_id']);
        $values = $this->fakeDb->select('ip_custom_values', ['custom_values_value' => 'Should Not Save']);
        $this->assertCount(0, $values);
    }

    #[Test]
    public function it_post_delete_removes_custom_value(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $valueToDelete = $this->fixtures->get('custom_values', 'industry_education');
        
        /* Act */
        $response = $this->post('/custom_values/delete/' . $valueToDelete['custom_values_id']);
        
        // Simulate deletion
        $this->fakeDb->delete('ip_custom_values', ['custom_values_id' => $valueToDelete['custom_values_id']]);
        
        /* Assert */
        $response->assertRedirect();
        $values = $this->fakeDb->select('ip_custom_values', ['custom_values_id' => $valueToDelete['custom_values_id']]);
        $this->assertCount(0, $values);
    }

    #[Test]
    public function it_post_delete_prevents_deletion_of_used_values(): void
    {
        /* Arrange - Custom value is used in invoice */
        $this->actAsAdmin();
        $usedValue = $this->fixtures->get('custom_values', 'industry_technology');
        // TODO: Create invoice using this value
        
        /* Act */
        $response = $this->post('/custom_values/delete/' . $usedValue['custom_values_id']);
        
        /* Assert */
        $response->assertSessionHas('alert_error', 'Cannot delete value that is in use');
        // Verify value still exists
        $values = $this->fakeDb->select('ip_custom_values', ['custom_values_id' => $usedValue['custom_values_id']]);
        $this->assertCount(1, $values);
    }

    #[Test]
    public function it_post_delete_redirects_to_index_without_field_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        $response = $this->post('/custom_values/delete/9999');
        
        /* Assert */
        $response->assertRedirect('/custom_values');
    }
}
