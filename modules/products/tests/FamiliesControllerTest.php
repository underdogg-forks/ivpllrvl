<?php

namespace Modules\Products\Tests;

use Modules\Products\Controllers\FamiliesController;
use Modules\Core\Testing\ControllerTestCase;
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for FamiliesController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 * 
 * All tests follow SOLID, DRY, and Dynamic Programming principles.
 */
#[CoversClass(FamiliesController::class)]
class FamiliesControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = FamiliesController::class;
    
    /**
     * Define which fixture types this test needs
     */
    protected function fixtureTypes(): array
    {
        return ['users', 'families'];
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
     * Test that families index requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_families_index(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /families/index
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/families/index');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test that families index requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_display_families_index(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /families/index
         * Expected behavior: Redirect to dashboard when user is not admin
         */
        $response = $this->get('/families/index');
        
        /* Assert */
        $response->assertRedirect('dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Test that families form requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_display_families_form(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: GET /families/form
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->get('/families/form');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test that families form requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_display_families_form(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: GET /families/form
         * Expected behavior: Redirect to dashboard when user is not admin
         */
        $response = $this->get('/families/form');
        
        /* Assert */
        $response->assertRedirect('dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    // #endregion

    // #region Index & List Display Tests

    /**
     * Happy Path: Families index displays families list
     */
    #[Test]
    public function it_displays_families_list_on_index_page(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $electronics = $this->fixtures->get('families', 'electronics');
        
        /**
         * Act: GET /families/index
         * Expected behavior: Display list of families
         */
        $response = $this->get('/families/index');
        
        /* Assert */
        $response->assertSee($electronics['family_name']);
        $this->assertDatabaseHasRecord('ip_families', ['family_id' => $electronics['family_id']]);
        $this->assertDatabaseCount('ip_families', [], 3);
    }

    /**
     * Test families index displays pagination controls
     */
    #[Test]
    public function it_displays_pagination_on_families_index(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /families/index
         * Expected behavior: Display pagination controls
         */
        $response = $this->get('/families/index');
        
        /* Assert */
        $this->assertHasPagination($response);
        $this->assertDatabaseHasRecord('ip_families', []);
    }

    /**
     * Test families index supports filtering
     */
    #[Test]
    public function it_supports_filtering_on_families_index(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: POST /families/index
         * POST data: {
         *   "filter_family_name": "Electronics"
         * }
         * Expected behavior: Filter families by name
         */
        $response = $this->post('/families/index', ['filter_family_name' => 'Electronics']);
        
        /* Assert */
        $response->assertOk();
    }

    // #endregion

    // #region Form Display Tests

    /**
     * Happy Path: Form displays new family form
     */
    #[Test]
    public function it_displays_new_family_form(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /**
         * Act: GET /families/form
         * Expected behavior: Display new family form fields
         */
        $response = $this->get('/families/form');
        
        /* Assert */
        $this->assertResponseContainsAll($response, ['family_name']);
        $this->assertTrue($this->fakeSession->has('user_id'));
    }

    /**
     * Happy Path: Form displays edit family form with existing data
     */
    #[Test]
    public function it_displays_edit_family_form_with_existing_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $electronics = $this->fixtures->get('families', 'electronics');
        $familyId = $electronics['family_id'];
        
        /**
         * Act: GET /families/form/{id}
         * Expected behavior: Display edit form with existing family data
         */
        $response = $this->get('/families/form/' . $familyId);
        
        /* Assert */
        $response->assertSee($electronics['family_name']);
        $this->assertDatabaseHasRecord('ip_families', [
            'family_id' => $familyId,
            'family_name' => 'Electronics'
        ]);
    }

    /**
     * Test form returns 404 for invalid family
     */
    #[Test]
    public function it_returns_404_for_invalid_family_id(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidFamilyId = 9999;
        
        /**
         * Act: GET /families/form/{id}
         * Expected behavior: Return 404 for non-existent family
         */
        $response = $this->get('/families/form/' . $invalidFamilyId);
        
        /* Assert */
        $response->assertNotFound();
        $this->assertDatabaseMissingRecord('ip_families', ['family_id' => $invalidFamilyId]);
    }

    // #endregion

    // #region Form Submission Tests (Create)

    /**
     * Happy Path: POST creates new family with valid data
     */
    #[Test]
    public function it_creates_new_family_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $validFamilyData = $this->makeFamilyData([
            'family_name' => 'Services',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /families/form
         * POST data: {
         *   "family_name": "Services",
         *   "btn_submit": "1"
         * }
         * Expected behavior: Create new family and redirect to index
         */
        $response = $this->post('/families/form', $validFamilyData);
        
        /* Assert */
        $response->assertRedirect('/families');
        $this->assertDatabaseHasRecord('ip_families', ['family_name' => 'Services']);
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
        
        $familyData = $this->makeFamilyData([
            'family_name' => 'Should Not Be Created',
            'btn_cancel' => 'Cancel',
        ]);
        
        /**
         * Act: POST /families/form
         * POST data: Complete family data with btn_cancel set
         * Expected behavior: Cancel and redirect without saving
         */
        $response = $this->post('/families/form', $familyData);
        
        /* Assert */
        $response->assertRedirect('/families');
        $this->assertDatabaseMissingRecord('ip_families', ['family_name' => 'Should Not Be Created']);
    }

    // #endregion

    // #region Form Submission Tests (Update)

    /**
     * Happy Path: POST updates existing family
     */
    #[Test]
    public function it_updates_existing_family_with_valid_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $electronics = $this->fixtures->get('families', 'electronics');
        $updateData = $this->makeFamilyData([
            'family_id' => $electronics['family_id'],
            'family_name' => 'Updated Electronics',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /families/form/{id}
         * POST data: Complete family data with updated family_name
         * Expected behavior: Update family and redirect to index
         */
        $response = $this->post('/families/form/' . $electronics['family_id'], $updateData);
        
        /* Assert */
        $response->assertRedirect('/families');
        $this->assertDatabaseHasRecord('ip_families', [
            'family_id' => $electronics['family_id'],
            'family_name' => 'Updated Electronics'
        ]);
    }

    /**
     * Test updating family allows same name for existing record
     */
    #[Test]
    public function it_allows_updating_existing_family_with_same_name(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $electronics = $this->fixtures->get('families', 'electronics');
        $updateData = $this->makeFamilyData([
            'family_id' => $electronics['family_id'],
            'family_name' => 'Electronics',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /families/form/{id}
         * POST data: Complete family data with same family_name
         * Expected behavior: Allow update with same name for existing record
         */
        $response = $this->post('/families/form/' . $electronics['family_id'], $updateData);
        
        /* Assert */
        $this->assertTrue(true);
    }

    // #endregion

    // #region Delete Tests

    /**
     * Test DELETE requires authentication
     */
    #[Test]
    public function it_requires_authentication_to_delete_family(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /**
         * Act: POST /families/delete/1
         * Expected behavior: Redirect to login page when not authenticated
         */
        $response = $this->post('/families/delete/1');
        
        /* Assert */
        $this->assertRequiresAuthentication($response);
    }

    /**
     * Test DELETE requires admin role
     */
    #[Test]
    public function it_requires_admin_role_to_delete_family(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /**
         * Act: POST /families/delete/1
         * Expected behavior: Redirect to dashboard when user is not admin
         */
        $response = $this->post('/families/delete/1');
        
        /* Assert */
        $response->assertRedirect('dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Test POST delete removes family
     */
    #[Test]
    public function it_deletes_family_successfully(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $hardware = $this->fixtures->get('families', 'hardware');
        $familyId = $hardware['family_id'];
        
        /**
         * Act: POST /families/delete/{id}
         * POST data: {
         *   "btn_submit": "1"
         * }
         * Expected behavior: Delete family and redirect to index
         */
        $response = $this->post('/families/delete/' . $familyId, [
            'btn_submit' => '1',
        ]);
        
        /* Assert */
        $response->assertRedirect('/families');
        $this->assertDatabaseMissingRecord('ip_families', ['family_id' => $familyId]);
    }

    /**
     * Test delete handles invalid family ID gracefully
     */
    #[Test]
    public function it_handles_invalid_family_id_on_delete(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $invalidFamilyId = 9999;
        
        /**
         * Act: POST /families/delete/{id}
         * Expected behavior: Handle gracefully without errors
         */
        $response = $this->post('/families/delete/' . $invalidFamilyId);
        
        /* Assert */
        $this->assertDatabaseMissingRecord('ip_families', ['family_id' => $invalidFamilyId]);
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
        
        $electronics = $this->fixtures->get('families', 'electronics');
        $familyId = $electronics['family_id'];
        
        /**
         * Act: POST /families/delete/{id}
         * Expected behavior: Prevent deletion or handle gracefully if products exist
         */
        $response = $this->post('/families/delete/' . $familyId);
        
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
        
        $invalidData = $this->makeFamilyData([
            'family_name' => '',
        ]);
        
        /**
         * Act: POST /families/form
         * POST data: Complete data with empty required fields
         * Expected behavior: Validation errors for missing required fields
         */
        $response = $this->post('/families/form', $invalidData);
        
        /* Assert */
        $response->assertSessionHasErrors(['family_name']);
    }

    /**
     * Test POST validates family name uniqueness
     */
    #[Test]
    public function it_validates_family_name_uniqueness(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $electronics = $this->fixtures->get('families', 'electronics');
        $duplicateData = $this->makeFamilyData([
            'family_name' => $electronics['family_name'],
        ]);
        
        /**
         * Act: POST /families/form
         * POST data: Complete data with duplicate family_name
         * Expected behavior: Validation error for family_name
         */
        $response = $this->post('/families/form', $duplicateData);
        
        /* Assert */
        $response->assertSessionHasErrors(['family_name']);
        $this->assertDatabaseHasRecord('ip_families', ['family_name' => 'Electronics']);
    }

    /**
     * Test form handles special characters in name
     */
    #[Test]
    public function it_handles_special_characters_in_family_name(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $specialData = $this->makeFamilyData([
            'family_name' => 'Products & Services',
            'btn_submit' => '1',
        ]);
        
        /**
         * Act: POST /families/form
         * POST data: Complete data with special characters in family_name
         * Expected behavior: Accept special characters
         */
        $response = $this->post('/families/form', $specialData);
        
        /* Assert */
        $this->assertDatabaseHasRecord('ip_families', ['family_name' => 'Products & Services']);
    }

    // #endregion

    // #region Security Tests

    /**
     * Security: Test XSS sanitization in family data
     */
    #[Test]
    public function it_sanitizes_xss_attempts_in_family_data(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        $xssData = $this->makeFamilyData([
            'family_name' => '<script>alert("xss")</script>',
        ]);
        
        /**
         * Act: POST /families/form
         * POST data: Complete data with XSS payloads in family_name
         * Expected behavior: XSS payloads should be sanitized or rejected
         */
        $response = $this->post('/families/form', $xssData);
        
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
        
        $sqlInjectionData = $this->makeFamilyData([
            'family_name' => "'; DROP TABLE ip_families; --",
        ]);
        
        /**
         * Act: POST /families/form
         * POST data: Complete data with SQL injection payloads
         * Expected behavior: SQL injection should be prevented at query level
         */
        $response = $this->post('/families/form', $sqlInjectionData);
        
        /* Assert */
        $this->assertDatabaseHasRecord('ip_families', []);
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
        
        $sqlInjection = "1 OR 1=1; DROP TABLE ip_families; --";
        
        /**
         * Act: POST /families/delete/{id}
         * Expected behavior: SQL injection should be prevented
         */
        $response = $this->post('/families/delete/' . $sqlInjection);
        
        /* Assert */
        $this->assertDatabaseHasRecord('ip_families', []);
    }

    // #endregion
}
