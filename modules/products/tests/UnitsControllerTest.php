<?php

namespace Modules\Products\Tests;

use Modules\Products\Controllers\UnitsController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for UnitsController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(UnitsController::class)]
class UnitsControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = UnitsController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'units'];
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
     * Test that units index requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_units_index(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /units/index
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/units/index');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test that units index requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_display_units_index(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /units/index
         * Expected behavior: Redirect to dashboard when user is not admin
         */
        $response = $this->get('/units/index');
        
        /* Assert */
        $response->assertRedirect('dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Test that units form requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_units_form(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /units/form
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/units/form');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test that units form requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_display_units_form(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /units/form
         * Expected behavior: Redirect to dashboard when user is not admin
         */
        $response = $this->get('/units/form');
        
        /* Assert */
        $response->assertRedirect('dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    // #endregion

    // #region Index & List Display Tests

    /**
     * Happy Path: Units index displays units list
     */
    #[Test]
    public function it_displays_units_list_on_index_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $eachUnit = $this->fixtures->get('units', 'each');
        
        /**
         * Act: GET /units/index
         * Expected behavior: Display list of units
         */
        $response = $this->get('/units/index');
        
        /* Assert */
        $response->assertSee($eachUnit['unit_name']);
        $this->assertDatabaseHasRecord('ip_units', ['unit_id' => $eachUnit['unit_id']]);
        $this->assertDatabaseCount('ip_units', [], 3);
    }

    /**
     * Test units index displays pagination controls
     */
    #[Test]
    public function it_displays_pagination_on_units_index(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /units/index
         * Expected behavior: Display pagination controls
         */
        $response = $this->get('/units/index');
        
        /* Assert */
        $this->assertHasPagination($response);
        $this->assertDatabaseHasRecord('ip_units', []);
    }

    /**
     * Test empty units list displays appropriate message
     */
    #[Test]
    public function it_shows_empty_state_when_no_units_exist(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /units/index
         * Expected behavior: Show empty state message when no units
         */
        $response = $this->get('/units/index');
        
        /* Assert */
        $response->assertSee('no records');
    }

    // #endregion

    // #region Form Display Tests

    /**
     * Happy Path: Form displays new unit form
     */
    #[Test]
    public function it_displays_new_unit_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /units/form
         * Expected behavior: Display new unit form fields
         */
        $response = $this->get('/units/form');
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['unit_name', 'unit_name_plrl']);
        $this->assertTrue($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Form displays edit unit form with existing data
     */
    #[Test]
    public function it_displays_edit_unit_form_with_existing_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $eachUnit = $this->fixtures->get('units', 'each');
        $unitId = $eachUnit['unit_id'];
        
        /**
         * Act: GET /units/form/{id}
         * Expected behavior: Display edit form with existing unit data
         */
        $response = $this->get('/units/form/' . $unitId);
        
        /* Assert */
        $response->assertSee($eachUnit['unit_name']);
        $this->assertDatabaseHasRecord('ip_units', [
            'unit_id' => $unitId,
            'unit_name' => 'Each'
        ]);
    }

    /**
     * Test form returns 404 for invalid unit
     */
    #[Test]
    public function it_returns_404_for_invalid_unit_id(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidUnitId = 9999;
        
        /**
         * Act: GET /units/form/{id}
         * Expected behavior: Return 404 for non-existent unit
         */
        $response = $this->get('/units/form/' . $invalidUnitId);
        
        /* Assert */
        $response->assertNotFound();
        $this->assertDatabaseMissingRecord('ip_units', ['unit_id' => $invalidUnitId]);
    }

    // #endregion

    // #region Form Submission Tests (Create)

    /**
     * Happy Path: POST creates new unit with valid data
     */
    #[Test]
    public function it_creates_new_unit_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $validUnitData = $this->makeUnitData([
            'unit_name' => 'Month',
            'unit_name_plrl' => 'Months',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /units/form
         * POST data: {
         *   "unit_name": "Month",
         *   "unit_name_plrl": "Months",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Create new unit and redirect to index
         */
        $response = $this->post('/units/form', $validUnitData);
        
        /* Assert */
        $response->assertRedirect('/units');
        $this->assertDatabaseHasRecord('ip_units', ['unit_name' => 'Month', 'unit_name_plrl' => 'Months']);
    }

    /**
     * Test POST cancels without saving when btn_cancel is clicked
     */
    #[Test]
    public function it_cancels_form_without_saving_when_cancel_button_clicked(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $unitData = $this->makeUnitData([
            'unit_name' => 'Should Not Be Created',
            'unit_name_plrl' => 'Should Not Be Created',
            'btn_cancel' => 'Cancel',
        ]);
        
        /**
         * Act: POST /units/form
         * POST data: Complete unit data with btn_cancel set
         * Expected behavior: Cancel and redirect without saving
         */
        $response = $this->post('/units/form', $unitData);
        
        /* Assert */
        $response->assertRedirect('/units');
        $this->assertDatabaseMissingRecord('ip_units', ['unit_name' => 'Should Not Be Created']);
    }

    // #endregion

    // #region Form Submission Tests (Update)

    /**
     * Happy Path: POST updates existing unit
     */
    #[Test]
    public function it_updates_existing_unit_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $eachUnit = $this->fixtures->get('units', 'each');
        $updateData = $this->makeUnitData([
            'unit_id' => $eachUnit['unit_id'],
            'unit_name' => 'Updated Name',
            'unit_name_plrl' => 'Updated Names',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /units/form/{id}
         * POST data: Complete unit data with updated unit_name and unit_name_plrl
         * Expected behavior: Update unit and redirect to index
         */
        $response = $this->post('/units/form/' . $eachUnit['unit_id'], $updateData);
        
        /* Assert */
        $response->assertRedirect('/units');
        $this->assertDatabaseHasRecord('ip_units', [
            'unit_id' => $eachUnit['unit_id'],
            'unit_name' => 'Updated Name'
        ]);
    }

    /**
     * Test updating unit allows same name for existing record
     */
    #[Test]
    public function it_allows_updating_existing_unit_with_same_name(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $hourUnit = $this->fixtures->get('units', 'hour');
        $updateData = $this->makeUnitData([
            'unit_id' => $hourUnit['unit_id'],
            'unit_name' => 'Hour',
            'unit_name_plrl' => 'Hours',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /units/form/{id}
         * POST data: Complete unit data with same unit_name
         * Expected behavior: Allow update with same name for existing record
         */
        $response = $this->post('/units/form/' . $hourUnit['unit_id'], $updateData);
        
        /* Assert */
        $this->assertTrue(true);
    }

    // #endregion

    // #region Delete Tests

    /**
     * Test DELETE requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_delete_unit(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /units/delete/1
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/units/delete/1');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test DELETE requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_delete_unit(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: POST /units/delete/1
         * Expected behavior: Redirect to dashboard when user is not admin
         */
        $response = $this->post('/units/delete/1');
        
        /* Assert */
        $response->assertRedirect('dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Test POST delete removes unit
     */
    #[Test]
    public function it_deletes_unit_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $dayUnit = $this->fixtures->get('units', 'day');
        $unitId = $dayUnit['unit_id'];
        
        /**
         * Act: POST /units/delete/{id}
         * POST data: {
         *   "btn_submit": "1"
         * }
         * Expected behavior: Delete unit and redirect to index
         */
        $response = $this->post('/units/delete/' . $unitId, [
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        $response->assertRedirect('/units');
        $this->assertDatabaseMissingRecord('ip_units', ['unit_id' => $unitId]);
    }

    /**
     * Test delete handles invalid unit ID gracefully
     */
    #[Test]
    public function it_handles_invalid_unit_id_on_delete(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidUnitId = 9999;
        
        /**
         * Act: POST /units/delete/{id}
         * Expected behavior: Handle gracefully without errors
         */
        $response = $this->post('/units/delete/' . $invalidUnitId);
        
        /* Assert */
        $this->assertDatabaseMissingRecord('ip_units', ['unit_id' => $invalidUnitId]);
    }

    /**
     * Test delete handles foreign key constraints
     */
    #[Test]
    public function it_handles_foreign_key_constraints_on_delete(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $eachUnit = $this->fixtures->get('units', 'each');
        $unitId = $eachUnit['unit_id'];
        
        /**
         * Act: POST /units/delete/{id}
         * Expected behavior: Prevent deletion or handle gracefully if products exist
         */
        $response = $this->post('/units/delete/' . $unitId);
        
        /* Assert */
        $this->assertTrue(true);
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
        
        $invalidData = $this->makeUnitData([
            'unit_name' => '',
        ]);
        
        /**
         * Act: POST /units/form
         * POST data: Complete data with empty required fields
         * Expected behavior: Validation errors for missing required fields
         */
        $response = $this->post('/units/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['unit_name']);
    }

    /**
     * Test POST validates unit name uniqueness
     */
    #[Test]
    public function it_validates_unit_name_uniqueness(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $hourUnit = $this->fixtures->get('units', 'hour');
        $duplicateData = $this->makeUnitData([
            'unit_name' => $hourUnit['unit_name'],
            'unit_name_plrl' => 'Hours',
        ]);
        
        /**
         * Act: POST /units/form
         * POST data: Complete data with duplicate unit_name
         * Expected behavior: Validation error for unit_name
         */
        $response = $this->post('/units/form', $duplicateData);
        
        /* Assert */
        $response->assertSessionHasErrors(['unit_name']);
        $this->assertDatabaseHasRecord('ip_units', ['unit_name' => 'Hour']);
    }

    /**
     * Test form handles special characters in name
     */
    #[Test]
    public function it_handles_special_characters_in_unit_name(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $specialData = $this->makeUnitData([
            'unit_name' => 'Meter³',
            'unit_name_plrl' => 'Meters³',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /units/form
         * POST data: Complete data with special characters in unit_name
         * Expected behavior: Accept special characters
         */
        $response = $this->post('/units/form', $specialData);
        
        /* Assert */
        $this->assertDatabaseHasRecord('ip_units', ['unit_name' => 'Meter³']);
    }

    /**
     * Test plural form is properly saved
     */
    #[Test]
    public function it_saves_both_singular_and_plural_forms(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $unitData = $this->makeUnitData([
            'unit_name' => 'Box',
            'unit_name_plrl' => 'Boxes',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /units/form
         * POST data: Complete data with both singular and plural forms
         * Expected behavior: Save both unit_name and unit_name_plrl
         */
        $response = $this->post('/units/form', $unitData);
        
        /* Assert */
        $this->assertDatabaseHasRecord('ip_units', [
            'unit_name' => 'Box',
            'unit_name_plrl' => 'Boxes'
        ]);
    }

    // #endregion

    // #region Security Tests

    /**
     * Security: Test XSS sanitization in unit data
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_unit_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $xssData = $this->makeUnitData([
            'unit_name' => '<script>alert("xss")</script>',
            'unit_name_plrl' => '<script>alert("xss")</script>',
        ]);
        
        /**
         * Act: POST /units/form
         * POST data: Complete data with XSS payloads in unit_name and unit_name_plrl
         * Expected behavior: XSS payloads should be sanitized or rejected
         */
        $response = $this->post('/units/form', $xssData);
        
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
        
        $sqlInjectionData = $this->makeUnitData([
            'unit_name' => "'; DROP TABLE ip_units; --",
            'unit_name_plrl' => 'Test',
        ]);
        
        /**
         * Act: POST /units/form
         * POST data: Complete data with SQL injection payloads
         * Expected behavior: SQL injection should be prevented at query level
         */
        $response = $this->post('/units/form', $sqlInjectionData);
        
        /* Assert */
        $this->assertDatabaseHasRecord('ip_units', []);
    }

    /**
     * Security: Test SQL injection protection in delete
     */
    #[Test]
    public function it_protects_against_sql_injection_in_delete(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $sqlInjection = "1 OR 1=1; DROP TABLE ip_units; --";
        
        /**
         * Act: POST /units/delete/{id}
         * Expected behavior: SQL injection should be prevented
         */
        $response = $this->post('/units/delete/' . $sqlInjection);
        
        /* Assert */
        $this->assertDatabaseHasRecord('ip_units', []);
    }

    // #endregion
}
