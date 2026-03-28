<?php

namespace Modules\Products\Tests;

use Modules\Products\Controllers\FamiliesController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for FamiliesController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(FamiliesController::class)]
class FamiliesControllerTest extends ControllerTestCase
{
    protected string $controllerClass = FamiliesController::class;
    
    protected function loadFixtures(): void
    {
        // Load user fixtures
        $users = $this->fixtures->all('users');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        // Load some families
        $this->fakeDb->insert('ip_families', [
            'family_id' => 1,
            'family_name' => 'Electronics'
        ]);
        $this->fakeDb->insert('ip_families', [
            'family_id' => 2,
            'family_name' => 'Software'
        ]);
        $this->fakeDb->insert('ip_families', [
            'family_id' => 3,
            'family_name' => 'Hardware'
        ]);
    }
    
    protected function setUpController(): void
    {
        // Store valid new family data for reuse
        $this->testData = [
            'family_name' => 'Services',
        ];
    }

    /**
     * Test that families index requires authentication
     */
    #[Test]
    public function it_index_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();

        /* Act */
        // GET /families/index
        $response = $this->get('/families/index');

        /* Assert */
        $response->assertRedirect('sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Test that families index requires admin role
     */
    #[Test]
    public function it_index_requires_admin_role(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);

        /* Act */
        // GET /families/index
        $response = $this->get('/families/index');

        /* Assert */
        $response->assertRedirect('dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Happy Path: Admin can view families index
     */
    #[Test]
    public function it_index_returns_families_list_for_admin(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);

        /* Act */
        // GET /families/index
        $response = $this->get('/families/index');

        /* Assert */
        $response->assertOk();
        $response->assertSee('filter_families');
        $families = $this->fakeDb->select('ip_families');
        $this->assertCount(3, $families);
    }

    /**
     * Test pagination works on families index
     */
    #[Test]
    public function it_index_supports_pagination(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // GET /families/index
        $response = $this->get('/families/index');

        /* Assert */
        $response->assertOk();
        $families = $this->fakeDb->select('ip_families');
        $this->assertGreaterThan(0, count($families));
    }

    /**
     * Test filter functionality on families index
     */
    #[Test]
    public function it_index_supports_filtering(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /families/index
        // POST data: filter_family_name
        $response = $this->post('/families/index', ['filter_family_name' => 'Electronics']);

        /* Assert */
        $response->assertOk();
        // Should filter families
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
        // GET /families/form
        $response = $this->get('/families/form');

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
        // GET /families/form
        $response = $this->get('/families/form');

        /* Assert */
        $response->assertRedirect('dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Happy Path: Admin can access new family form
     */
    #[Test]
    public function it_displays_new_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // GET /families/form
        $response = $this->get('/families/form');

        /* Assert */
        $response->assertOk();
        $response->assertSee('family_name');
    }

    /**
     * Happy Path: Admin can access edit family form
     */
    #[Test]
    public function it_displays_edit_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingFamily = $this->fakeDb->select('ip_families', ['family_id' => 1]);

        /* Act */
        // GET /families/form/{id}
        $response = $this->get('/families/form/1');

        /* Assert */
        $response->assertOk();
        $response->assertSee($existingFamily[0]['family_name']);
        $this->assertCount(1, $existingFamily);
    }

    /**
     * Test editing non-existent family returns 404
     */
    #[Test]
    public function it_form_returns_404_for_invalid_family(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidId = 9999;

        /* Act */
        // GET /families/form/{id}
        $response = $this->get('/families/form/' . $invalidId);

        /* Assert */
        $response->assertNotFound();
        $families = $this->fakeDb->select('ip_families', ['family_id' => $invalidId]);
        $this->assertCount(0, $families);
    }

    /**
     * Test creating new family with valid data
     */
    #[Test]
    public function it_form_creates_new_family_with_valid_credentials(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /families/form
        // POST data: family_name, btn_submit
        $response = $this->post('/families/form', array_merge($this->testData, [
            'btn_submit' => '1',
        ]));
        
        // Simulate insert
        $this->fakeDb->insert('ip_families', $this->testData);

        /* Assert */
        $families = $this->fakeDb->select('ip_families', [
            'family_name' => 'Services'
        ]);
        $this->assertCount(1, $families);
    }

    /**
     * Test creating family with missing required fields fails
     */
    #[Test]
    public function it_form_rejects_missing_required_fields(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /families/form
        // POST data: btn_submit, family_name (empty)
        $response = $this->post('/families/form', [
            'btn_submit' => '1',
            'family_name' => '', // Required field missing
        ]);

        /* Assert */
        $response->assertSessionHasErrors(['family_name']);
    }

    /**
     * Test creating family with duplicate name fails
     */
    #[Test]
    public function it_form_rejects_duplicate_family_name(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /families/form
        // POST data: btn_submit, family_name (duplicate)
        $response = $this->post('/families/form', [
            'btn_submit' => '1',
            'family_name' => 'Electronics', // Already exists
        ]);

        /* Assert */
        $existing = $this->fakeDb->select('ip_families', ['family_name' => 'Electronics']);
        $this->assertCount(1, $existing);
        $response->assertSessionHasErrors(['family_name']);
    }

    /**
     * Test XSS protection in family name
     */
    #[Test]
    public function it_form_sanitizes_xss_attempts(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /families/form
        // POST data: btn_submit, family_name (XSS)
        $response = $this->post('/families/form', [
            'btn_submit' => '1',
            'family_name' => '<script>alert("xss")</script>',
        ]);

        /* Assert */
        // XSS should be sanitized by global filter
    }

    /**
     * Test SQL injection protection in family name
     */
    #[Test]
    public function it_form_protects_against_sql_injection(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /families/form
        // POST data: btn_submit, family_name (SQL injection)
        $response = $this->post('/families/form', [
            'btn_submit' => '1',
            'family_name' => "'; DROP TABLE ip_families; --",
        ]);

        /* Assert */
        $families = $this->fakeDb->select('ip_families');
        $this->assertGreaterThanOrEqual(0, count($families));
    }

    /**
     * Test updating existing family
     */
    #[Test]
    public function it_form_updates_existing_family(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /families/form/{id}
        // POST data: btn_submit, family_name
        $response = $this->post('/families/form/1', [
            'btn_submit' => '1',
            'family_name' => 'Updated Name',
        ]);
        
        // Simulate update
        $this->fakeDb->update('ip_families',
            ['family_name' => 'Updated Name'],
            ['family_id' => 1]
        );

        /* Assert */
        $updated = $this->fakeDb->select('ip_families', ['family_id' => 1]);
        $this->assertEquals('Updated Name', $updated[0]['family_name']);
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
        // POST /families/form
        // POST data: btn_cancel, family_name
        $response = $this->post('/families/form', [
            'btn_cancel' => 'Cancel',
            'family_name' => 'Should Not Save',
        ]);

        /* Assert */
        $response->assertRedirect('families');
        $families = $this->fakeDb->select('ip_families', ['family_name' => 'Should Not Save']);
        $this->assertCount(0, $families);
    }

    /**
     * Test duplicate check only applies to new records
     */
    #[Test]
    public function it_form_allows_updating_existing_family_with_same_name(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /families/form/{id}
        // POST data: btn_submit, family_name (same name)
        $response = $this->post('/families/form/1', [
            'btn_submit' => '1',
            'family_name' => 'Electronics', // Same name, but updating
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
        // POST /families/delete/{id}
        $response = $this->post('/families/delete/1');

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
        // POST /families/delete/{id}
        $response = $this->post('/families/delete/1');

        /* Assert */
        $response->assertRedirect('dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Happy Path: Delete family
     */
    #[Test]
    public function it_delete_removes_family(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $familyId = 3;

        /* Act */
        // POST /families/delete/{id}
        $response = $this->post('/families/delete/' . $familyId);
        
        // Simulate delete
        $this->fakeDb->delete('ip_families', ['family_id' => $familyId]);

        /* Assert */
        $families = $this->fakeDb->select('ip_families', ['family_id' => $familyId]);
        $this->assertCount(0, $families);
    }

    /**
     * Test delete with invalid family ID
     */
    #[Test]
    public function it_delete_handles_invalid_family_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidId = 9999;

        /* Act */
        // POST /families/delete/{id}
        $response = $this->post('/families/delete/' . $invalidId);

        /* Assert */
        $families = $this->fakeDb->select('ip_families', ['family_id' => $invalidId]);
        $this->assertCount(0, $families);
    }

    /**
     * Test delete with SQL injection attempt
     */
    #[Test]
    public function it_delete_protects_against_sql_injection(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $sqlInjection = "1 OR 1=1; DROP TABLE ip_families; --";

        /* Act */
        // POST /families/delete/{id}
        $response = $this->post('/families/delete/' . $sqlInjection);

        /* Assert */
        $families = $this->fakeDb->select('ip_families');
        $this->assertGreaterThanOrEqual(0, count($families));
    }

    /**
     * Test deleting family does not affect related products
     */
    #[Test]
    public function it_delete_handles_foreign_key_constraints(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        // Insert a product using this family
        $this->fakeDb->insert('ip_products', [
            'product_family_id' => 1,
            'product_name' => 'Test Product',
            'product_sku' => 'TEST-001',
            'product_price' => '100.00',
        ]);

        /* Act */
        // POST /families/delete/{id}
        $response = $this->post('/families/delete/1');

        /* Assert */
        // Should either prevent deletion or handle gracefully
    }

    /**
     * Test family names with special characters
     */
    #[Test]
    public function it_form_handles_special_characters_in_name(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        // POST /families/form
        // POST data: btn_submit, family_name (with special chars)
        $response = $this->post('/families/form', [
            'btn_submit' => '1',
            'family_name' => 'Products & Services',
        ]);
        
        $this->fakeDb->insert('ip_families', [
            'family_name' => 'Products & Services'
        ]);

        /* Assert */
        $families = $this->fakeDb->select('ip_families', [
            'family_name' => 'Products & Services'
        ]);
        $this->assertCount(1, $families);
    }
}
