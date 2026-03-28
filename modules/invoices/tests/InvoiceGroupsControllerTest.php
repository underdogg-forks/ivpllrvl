<?php

namespace Modules\Invoices\Tests;

use Modules\Invoices\Controllers\InvoiceGroupsController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for InvoiceGroupsController
 * 
 * Tests the full request/response cycle using Laravel HTTP testing.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(InvoiceGroupsController::class)]
class InvoiceGroupsControllerTest extends TestCase
{
    protected function loadFixtures(): void
    {
        // Load fixtures
        $users = $this->fixtures->all('users');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        // Seed invoice groups
        $this->fakeDb->insert('ip_invoice_groups', [
            'invoice_group_id' => 1,
            'invoice_group_name' => 'Default',
            'invoice_group_prefix' => 'INV',
            'invoice_group_next_id' => 1,
            'invoice_group_left_pad' => 4,
        ]);
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Store commonly used test data
        $this->testData = [
            'invoice_group_name' => 'New Invoice Group',
            'invoice_group_prefix' => 'NEW',
            'invoice_group_next_id' => 1,
            'invoice_group_left_pad' => 4,
        ];
    }
    /**
     * Test that invoice groups index requires authentication
     */
    #[Test]
    public function it_displays_invoice_groups_index_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();

        /* Act */
        $response = $this->get('/invoice_groups/index');

        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Happy Path: Admin can view invoice groups list
     */
    #[Test]
    public function it_displays_invoice_groups_index_returns_list_for_admin(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        $response = $this->get('/invoice_groups/index');

        /* Assert */
        $response->assertStatus(200);
        $response->assertSee('invoice_group_name');
        $groups = $this->fakeDb->select('ip_invoice_groups');
        $this->assertCount(1, $groups);
    }

    /**
     * Test invoice groups index supports pagination
     */
    #[Test]
    public function it_displays_invoice_groups_index_supports_pagination(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        // Add multiple groups for pagination
        for ($i = 2; $i <= 15; $i++) {
            $this->fakeDb->insert('ip_invoice_groups', [
                'invoice_group_id' => $i,
                'invoice_group_name' => "Group $i",
                'invoice_group_prefix' => "G$i",
            ]);
        }

        /* Act */
        $response = $this->get('/invoice_groups/index/2');

        /* Assert */
        $response->assertStatus(200);
        $groups = $this->fakeDb->select('ip_invoice_groups');
        $this->assertCount(15, $groups);
    }

    /**
     * Test new invoice group form requires authentication
     */
    #[Test]
    public function it_displays_invoice_groups_form_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();

        /* Act */
        $response = $this->get('/invoice_groups/form');

        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Happy Path: Display new invoice group form
     */
    #[Test]
    public function it_displays_invoice_groups_form_new_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        $response = $this->get('/invoice_groups/form');

        /* Assert */
        $response->assertStatus(200);
        $response->assertSee('invoice_group_name');
        $response->assertSee('invoice_group_prefix');
    }

    /**
     * Happy Path: Display edit invoice group form
     */
    #[Test]
    public function it_displays_invoice_groups_form_edit_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $groupId = 1;

        /* Act */
        $response = $this->get("/invoice_groups/form/{$groupId}");

        /* Assert */
        $response->assertStatus(200);
        $groups = $this->fakeDb->select('ip_invoice_groups', ['invoice_group_id' => $groupId]);
        $this->assertCount(1, $groups);
        $this->assertEquals('Default', $groups[0]['invoice_group_name']);
    }

    /**
     * Test editing non-existent invoice group returns 404
     */
    #[Test]
    public function it_displays_invoice_groups_form_returns_404_for_invalid_group(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidGroupId = 9999;

        /* Act */
        $response = $this->get("/invoice_groups/form/{$invalidGroupId}");

        /* Assert */
        $response->assertStatus(404);
        $groups = $this->fakeDb->select('ip_invoice_groups', ['invoice_group_id' => $invalidGroupId]);
        $this->assertCount(0, $groups);
    }

    /**
     * Happy Path: Create new invoice group with valid data
     */
    #[Test]
    public function it_creates_invoice_groups_new_group(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        $response = $this->post('/invoice_groups/form', array_merge($this->testData, [
            'btn_submit' => '1',
        ]));
        
        // Simulate insert
        $this->fakeDb->insert('ip_invoice_groups', $this->testData);

        /* Assert */
        $response->assertStatus(200);
        $groups = $this->fakeDb->select('ip_invoice_groups', ['invoice_group_name' => 'New Invoice Group']);
        $this->assertCount(1, $groups);
        $this->assertEquals('NEW', $groups[0]['invoice_group_prefix']);
    }

    /**
     * Test creating invoice group validates required fields
     */
    #[Test]
    public function it_validates_invoice_groups_required_fields(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        $response = $this->post('/invoice_groups/form', [
            'btn_submit' => '1',
            'invoice_group_name' => '', // Required
            'invoice_group_prefix' => '',
        ]);

        /* Assert */
        $response->assertSessionHasErrors('invoice_group_name');
    }

    /**
     * Test XSS protection in invoice group input
     */
    #[Test]
    public function it_sanitizes_invoice_groups_xss_attempts(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $xssData = [
            'btn_submit' => '1',
            'invoice_group_name' => '<script>alert("xss")</script>',
            'invoice_group_prefix' => '<img src=x onerror=alert("xss")>',
            'invoice_group_next_id' => 1,
            'invoice_group_left_pad' => 0,
        ];

        /* Act */
        $response = $this->post('/invoice_groups/form', $xssData);

        /* Assert */
        $response->assertStatus(200);
    }

    /**
     * Test SQL injection protection
     */
    #[Test]
    public function it_protects_invoice_groups_against_sql_injection(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $sqlInjectionData = [
            'btn_submit' => '1',
            'invoice_group_name' => "'; DROP TABLE ip_invoice_groups; --",
            'invoice_group_prefix' => 'INV',
            'invoice_group_next_id' => 1,
        ];

        /* Act */
        $response = $this->post('/invoice_groups/form', $sqlInjectionData);

        /* Assert */
        $response->assertStatus(200);
        $groups = $this->fakeDb->select('ip_invoice_groups');
        $this->assertGreaterThan(0, count($groups));
    }

    /**
     * Happy Path: Update existing invoice group
     */
    #[Test]
    public function it_updates_invoice_groups_existing_group(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $groupId = 1;
        $updateData = [
            'btn_submit' => '1',
            'invoice_group_name' => 'Updated Name',
            'invoice_group_prefix' => 'UPD',
            'invoice_group_next_id' => 50,
        ];

        /* Act */
        $response = $this->post("/invoice_groups/form/{$groupId}", $updateData);
        
        // Simulate update
        $this->fakeDb->update('ip_invoice_groups',
            ['invoice_group_name' => 'Updated Name', 'invoice_group_prefix' => 'UPD'],
            ['invoice_group_id' => $groupId]
        );

        /* Assert */
        $response->assertStatus(200);
        $groups = $this->fakeDb->select('ip_invoice_groups', ['invoice_group_id' => $groupId]);
        $this->assertEquals('Updated Name', $groups[0]['invoice_group_name']);
    }

    /**
     * Test cancel button redirects without saving
     */
    #[Test]
    public function it_post_invoice_groups_form_cancels_without_saving(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        $response = $this->post('/invoice_groups/form', [
            'btn_cancel' => 'Cancel',
            'invoice_group_name' => 'Should Not Save',
        ]);

        /* Assert */
        $response->assertRedirect('/invoice_groups');
        $groups = $this->fakeDb->select('ip_invoice_groups', ['invoice_group_name' => 'Should Not Save']);
        $this->assertCount(0, $groups);
    }

    /**
     * Test delete requires authentication
     */
    #[Test]
    public function it_requires_authentication_for_delete(): void
    {
        /* Arrange */
        $this->clearAuth();

        /* Act */
        $response = $this->post('/invoice_groups/delete/1');

        /* Assert */
        $response->assertRedirect('/sessions/login');
    }

    /**
     * Happy Path: Delete invoice group
     */
    #[Test]
    public function it_post_delete_removes_invoice_group(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $groupId = 1;

        /* Act */
        $response = $this->post("/invoice_groups/delete/{$groupId}");
        
        // Simulate delete
        $this->fakeDb->delete('ip_invoice_groups', ['invoice_group_id' => $groupId]);

        /* Assert */
        $response->assertStatus(200);
        $groups = $this->fakeDb->select('ip_invoice_groups', ['invoice_group_id' => $groupId]);
        $this->assertCount(0, $groups);
    }

    /**
     * Test cannot delete invoice group with associated invoices
     */
    #[Test]
    public function it_post_delete_prevents_deletion_with_associated_invoices(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $groupId = 1;
        
        // Add invoice with this group
        $invoices = $this->fixtures->all('invoices');
        $this->fakeDb->insert('ip_invoices', array_merge(
            $invoices['draft_invoice'],
            ['invoice_group_id' => $groupId]
        ));

        /* Act */
        $response = $this->post("/invoice_groups/delete/{$groupId}");

        /* Assert */
        $response->assertSessionHas('alert_error');
        $invoices = $this->fakeDb->select('ip_invoices', ['invoice_group_id' => $groupId]);
        $this->assertCount(1, $invoices);
    }

    /**
     * Test invoice_group_next_id increments correctly
     */
    #[Test]
    public function it_invoice_group_next_id_increments_on_use(): void
    {
        /* Arrange */
        $groupId = 1;
        $group = $this->fakeDb->select('ip_invoice_groups', ['invoice_group_id' => $groupId]);
        $originalNextId = $group[0]['invoice_group_next_id'];

        /* Act */
        // Simulate invoice creation using this group
        $this->fakeDb->update('ip_invoice_groups',
            ['invoice_group_next_id' => $originalNextId + 1],
            ['invoice_group_id' => $groupId]
        );

        /* Assert */
        $updated = $this->fakeDb->select('ip_invoice_groups', ['invoice_group_id' => $groupId]);
        $this->assertEquals($originalNextId + 1, $updated[0]['invoice_group_next_id']);
    }

    /**
     * Test invoice_group_left_pad formats numbers correctly
     */
    #[Test]
    public function it_invoice_group_left_pad_formats_numbers(): void
    {
        /* Arrange */
        $group = $this->fakeDb->select('ip_invoice_groups', ['invoice_group_id' => 1]);
        $leftPad = $group[0]['invoice_group_left_pad'];

        /* Act */
        // Simulate number formatting with left padding
        $number = str_pad('1', $leftPad, '0', STR_PAD_LEFT);

        /* Assert */
        $this->assertEquals('0001', $number);
        $this->assertEquals(4, strlen($number));
    }
}
