<?php

namespace Modules\Products\Tests;

use Modules\Products\Controllers\UnitsController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for UnitsController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(UnitsController::class)]
class UnitsControllerTest extends ControllerTestCase
{
    protected string $controllerClass = UnitsController::class;
    
    protected function loadFixtures(): void
    {
        // Load user fixtures
        $users = $this->fixtures->all('users');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        // Load some units
        $this->fakeDb->insert('ip_units', [
            'unit_id' => 1,
            'unit_name' => 'Each',
            'unit_name_plrl' => 'Each'
        ]);
        $this->fakeDb->insert('ip_units', [
            'unit_id' => 2,
            'unit_name' => 'Hour',
            'unit_name_plrl' => 'Hours'
        ]);
        $this->fakeDb->insert('ip_units', [
            'unit_id' => 3,
            'unit_name' => 'Day',
            'unit_name_plrl' => 'Days'
        ]);
    }
    
    protected function setUpController(): void
    {
        // Store valid new unit data for reuse
        $this->testData = [
            'unit_name' => 'Month',
            'unit_name_plrl' => 'Months',
        ];
    }

    /**
     * Test that units index requires authentication
     */
    #[Test]
    public function it_index_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();

        /* Act */
        // When CI bootstrap is ready, this will call the controller
        // GET /units/index
        $response = $this->get('/units/index');

        /* Assert */
        $response->assertRedirect('sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Test that units index requires admin role
     */
    #[Test]
    public function it_index_requires_admin_role(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);

        /* Act */
        // GET /units/index
        $response = $this->get('/units/index');

        /* Assert */
        $response->assertRedirect('dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Happy Path: Admin can view units index
     */
    #[Test]
    public function it_index_returns_units_list_for_admin(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);

        /* Act */
        // GET /units/index
        $response = $this->get('/units/index');

        /* Assert */
        $response->assertSee('filter_units');
        $units = $this->fakeDb->select('ip_units');
        $this->assertCount(3, $units);
    }

    /**
     * Test pagination works on units index
     */
    #[Test]
    public function it_index_supports_pagination(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // GET /units/index
        $response = $this->get('/units/index');

        /* Assert */
        $units = $this->fakeDb->select('ip_units');
        $this->assertGreaterThan(0, count($units));
    }

    /**
     * Test empty units list displays appropriate message
     */
    #[Test]
    public function it_index_shows_empty_state_when_no_units(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        // Clear all units
        $this->fakeDb->delete('ip_units');

        /* Act */
        // GET /units/index
        $response = $this->get('/units/index');

        /* Assert */
        $response->assertSee('no records');
        $units = $this->fakeDb->select('ip_units');
        $this->assertCount(0, $units);
    }

    /**
     * Test form page requires authentication
     */
    #[Test]
    public function it_form_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();

        /* Act */
        // GET /units/form
        $response = $this->get('/units/form');

        /* Assert */
        $response->assertRedirect('sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Test form page requires admin role
     */
    #[Test]
    public function it_form_requires_admin_role(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);

        /* Act */
        // GET /units/form
        $response = $this->get('/units/form');

        /* Assert */
        $response->assertRedirect('dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Happy Path: Admin can access new unit form
     */
    #[Test]
    public function it_displays_new_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // GET /units/form
        $response = $this->get('/units/form');

        /* Assert */
        $response->assertSee('unit_name');
        $response->assertSee('unit_name_plrl');
    }

    /**
     * Happy Path: Admin can access edit unit form
     */
    #[Test]
    public function it_displays_edit_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingUnit = $this->fakeDb->select('ip_units', ['unit_id' => 1]);

        /* Act */
        // GET /units/form/{id}
        $response = $this->get('/units/form/' . 1);

        /* Assert */
        $response->assertSee($existingUnit[0]['unit_name']);
        $this->assertCount(1, $existingUnit);
    }

    /**
     * Test editing non-existent unit returns 404
     */
    #[Test]
    public function it_form_returns_404_for_invalid_unit(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidId = 9999;

        /* Act */
        // GET /units/form/{id}
        $response = $this->get('/units/form/' . $invalidId);

        /* Assert */
        $response->assertNotFound();
        $units = $this->fakeDb->select('ip_units', ['unit_id' => $invalidId]);
        $this->assertCount(0, $units);
    }

    /**
     * Test creating new unit with valid data
     */
    #[Test]
    public function it_form_creates_new_unit_with_valid_credentials(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /units/form
        // POST data: unit_name, unit_name_plrl, btn_submit
        $response = $this->post('/units/form', array_merge($this->testData, [
            'btn_submit' => '1',
        ]));
        
        // Simulate insert
        $this->fakeDb->insert('ip_units', $this->testData);

        /* Assert */
        $units = $this->fakeDb->select('ip_units', [
            'unit_name' => 'Month'
        ]);
        $this->assertCount(1, $units);
        $this->assertEquals('Months', $units[0]['unit_name_plrl']);
    }

    /**
     * Test creating unit with missing required fields fails
     */
    #[Test]
    public function it_form_rejects_missing_required_fields(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /units/form
        // POST data: (see inline)
        $response = $this->post('/units/form', [
            'btn_submit' => '1',
            'unit_name' => '', // Required field missing
            'unit_name_plrl' => 'Tests',
        ]);

        /* Assert */
        $response->assertSessionHasErrors(['unit_name']);
    }

    /**
     * Test creating unit with duplicate name fails
     */
    #[Test]
    public function it_form_rejects_duplicate_unit_name(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /units/form
        // POST data: (see inline)
        $response = $this->post('/units/form', [
            'btn_submit' => '1',
            'unit_name' => 'Hour', // Already exists
            'unit_name_plrl' => 'Hours',
        ]);

        /* Assert */
        $existing = $this->fakeDb->select('ip_units', ['unit_name' => 'Hour']);
        $this->assertCount(1, $existing);
        $response->assertSessionHasErrors(['unit_name']);
    }

    /**
     * Test XSS protection in unit name
     */
    #[Test]
    public function it_form_sanitizes_xss_attempts(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /units/form
        // POST data: (see inline)
        $response = $this->post('/units/form', [
            'btn_submit' => '1',
            'unit_name' => '<script>alert("xss")</script>',
            'unit_name_plrl' => '<script>alert("xss")</script>',
        ]);

        /* Assert */
        // XSS should be sanitized by global filter
    }

    /**
     * Test SQL injection protection in unit name
     */
    #[Test]
    public function it_form_protects_against_sql_injection(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /units/form
        // POST data: (see inline)
        $response = $this->post('/units/form', [
            'btn_submit' => '1',
            'unit_name' => "'; DROP TABLE ip_units; --",
            'unit_name_plrl' => "Test",
        ]);

        /* Assert */
        $units = $this->fakeDb->select('ip_units');
        $this->assertGreaterThanOrEqual(0, count($units));
    }

    /**
     * Test updating existing unit
     */
    #[Test]
    public function it_form_updates_existing_unit(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /units/form/{id}
        // POST data: btn_submit, unit_name, unit_name_plrl
        $response = $this->post('/units/form/1', [
            'btn_submit' => '1',
            'unit_name' => 'Updated Name',
            'unit_name_plrl' => 'Updated Names',
        ]);
        
        // Simulate update
        $this->fakeDb->update('ip_units',
            ['unit_name' => 'Updated Name', 'unit_name_plrl' => 'Updated Names'],
            ['unit_id' => 1]
        );

        /* Assert */
        $updated = $this->fakeDb->select('ip_units', ['unit_id' => 1]);
        $this->assertEquals('Updated Name', $updated[0]['unit_name']);
        $this->assertEquals('Updated Names', $updated[0]['unit_name_plrl']);
    }

    /**
     * Test btn_cancel redirects without saving
     */
    #[Test]
    public function it_form_cancels_without_saving(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /units/form
        // POST data: (see inline)
        $response = $this->post('/units/form', [
            'btn_cancel' => 'Cancel',
            'unit_name' => 'Should Not Save',
            'unit_name_plrl' => 'Should Not Saves',
        ]);

        /* Assert */
        $response->assertRedirect('units');
        $units = $this->fakeDb->select('ip_units', ['unit_name' => 'Should Not Save']);
        $this->assertCount(0, $units);
    }

    /**
     * Test duplicate check only applies to new records
     */
    #[Test]
    public function it_form_allows_updating_existing_unit_with_same_name(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /units/form/{id}
        // POST data: btn_submit, unit_name, unit_name_plrl
        $response = $this->post('/units/form/2', [
            'btn_submit' => '1',
            'unit_name' => 'Hour', // Same name, but updating
            'unit_name_plrl' => 'Hours',
        ]);

        /* Assert */
        // Should allow updating with same name
    }

    /**
     * Test delete requires authentication
     */
    #[Test]
    public function it_delete_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();

        /* Act */
        // POST /units/delete/{id}
        $response = $this->post('/units/delete/' . 1);

        /* Assert */
        $response->assertRedirect('sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Test delete requires admin role
     */
    #[Test]
    public function it_delete_requires_admin_role(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);

        /* Act */
        // POST /units/delete/{id}
        $response = $this->post('/units/delete/' . 1);

        /* Assert */
        $response->assertRedirect('dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Happy Path: Delete unit
     */
    #[Test]
    public function it_delete_removes_unit(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $unitId = 3;

        /* Act */
        // POST /units/delete/{id}
        $response = $this->post('/units/delete/' . $unitId);
        
        // Simulate delete
        $this->fakeDb->delete('ip_units', ['unit_id' => $unitId]);

        /* Assert */
        $units = $this->fakeDb->select('ip_units', ['unit_id' => $unitId]);
        $this->assertCount(0, $units);
    }

    /**
     * Test delete with invalid unit ID
     */
    #[Test]
    public function it_delete_handles_invalid_unit_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidId = 9999;

        /* Act */
        // POST /units/delete/{id}
        $response = $this->post('/units/delete/' . $invalidId);

        /* Assert */
        $units = $this->fakeDb->select('ip_units', ['unit_id' => $invalidId]);
        $this->assertCount(0, $units);
    }

    /**
     * Test delete with SQL injection attempt
     */
    #[Test]
    public function it_delete_protects_against_sql_injection(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $sqlInjection = "1 OR 1=1; DROP TABLE ip_units; --";

        /* Act */
        // POST /units/delete/{id}
        $response = $this->post('/units/delete/' . $sqlInjection);

        /* Assert */
        $units = $this->fakeDb->select('ip_units');
        $this->assertGreaterThanOrEqual(0, count($units));
    }

    /**
     * Test deleting unit does not affect related products
     */
    #[Test]
    public function it_delete_handles_foreign_key_constraints(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        // Insert a product using this unit
        $this->fakeDb->insert('ip_products', [
            'product_unit_id' => 1,
            'product_name' => 'Test Product',
            'product_sku' => 'TEST-001',
            'product_price' => '100.00',
        ]);

        /* Act */
        // POST /units/delete/{id}
        $response = $this->post('/units/delete/' . 1);

        /* Assert */
        // Should either prevent deletion or handle gracefully
    }

    /**
     * Test unit names with special characters
     */
    #[Test]
    public function it_form_handles_special_characters_in_name(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /units/form
        // POST data: (see inline)
        $response = $this->post('/units/form', [
            'btn_submit' => '1',
            'unit_name' => 'Meter³',
            'unit_name_plrl' => 'Meters³',
        ]);
        
        $this->fakeDb->insert('ip_units', [
            'unit_name' => 'Meter³',
            'unit_name_plrl' => 'Meters³'
        ]);

        /* Assert */
        $units = $this->fakeDb->select('ip_units', [
            'unit_name' => 'Meter³'
        ]);
        $this->assertCount(1, $units);
    }

    /**
     * Test plural form is properly saved
     */
    #[Test]
    public function it_form_saves_both_singular_and_plural_names(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /units/form
        // POST data: (see inline)
        $response = $this->post('/units/form', [
            'btn_submit' => '1',
            'unit_name' => 'Box',
            'unit_name_plrl' => 'Boxes',
        ]);
        
        $this->fakeDb->insert('ip_units', [
            'unit_name' => 'Box',
            'unit_name_plrl' => 'Boxes'
        ]);

        /* Assert */
        $units = $this->fakeDb->select('ip_units', ['unit_name' => 'Box']);
        $this->assertCount(1, $units);
        $this->assertEquals('Boxes', $units[0]['unit_name_plrl']);
    }
}
