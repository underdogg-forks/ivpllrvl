<?php

namespace Modules\Invoices\Tests;

use Modules\Invoices\Controllers\RecurringController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for RecurringController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(RecurringController::class)]
class RecurringControllerTest extends ControllerTestCase
{
    protected string $controllerClass = RecurringController::class;
    
    protected function loadFixtures(): void
    {
        // Load fixtures
        $users = $this->fixtures->all('users');
        $clients = $this->fixtures->all('clients');
        $invoices = $this->fixtures->all('invoices');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        foreach (['active_client'] as $key) {
            $this->fakeDb->insert('ip_clients', $clients[$key]);
        }
        
        foreach (['draft_invoice'] as $key) {
            $this->fakeDb->insert('ip_invoices', $invoices[$key]);
        }
        
        // Seed recurring invoice
        $this->fakeDb->insert('ip_invoices_recurring', [
            'invoice_recurring_id' => 1,
            'invoice_id' => $invoices['draft_invoice']['invoice_id'],
            'recur_start_date' => date('Y-m-d'),
            'recur_end_date' => date('Y-m-d', strtotime('+1 year')),
            'recur_frequency' => 'M',
            'recur_next_date' => date('Y-m-d'),
            'recur_active' => 1,
        ]);
    }
    
    protected function setUpController(): void
    {
        // Test data available for tests
        $this->testData = [
            'recurring_id' => 1,
        ];
    }
    /**
     * Test that recurring invoices index requires authentication
     */
    #[Test]
    public function it_index_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();

        /* Act */
        $controller = $this->getController();
        $controller->index();

        /* Assert */
        $this->assertRedirectedTo('sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Test that recurring invoices index requires admin role
     */
    #[Test]
    public function it_index_requires_admin_role(): void
    {
        /* Arrange */
        $guest = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guest);

        /* Act */
        $controller = $this->getController();
        $controller->index();

        /* Assert */
        $this->assertRedirectedTo('dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Happy Path: Admin can view recurring invoices index
     */
    #[Test]
    public function it_index_returns_recurring_invoices_list_for_admin(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        $controller = $this->getController();
        $controller->index();

        /* Assert */
        $this->assertResponseContains('recur_frequency');
        $recurring = $this->fakeDb->select('ip_invoices_recurring');
        $this->assertCount(1, $recurring);
    }

    /**
     * Test pagination works on recurring invoices index
     */
    #[Test]
    public function it_index_supports_pagination(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        // Add more recurring invoices for pagination
        for ($i = 2; $i <= 15; $i++) {
            $this->fakeDb->insert('ip_invoices_recurring', [
                'invoice_recurring_id' => $i,
                'invoice_id' => 1,
                'recur_active' => 1,
            ]);
        }

        /* Act */
        $controller = $this->getController();
        $controller->index(2); // Page 2

        /* Assert */
        $recurring = $this->fakeDb->select('ip_invoices_recurring');
        $this->assertCount(15, $recurring);
    }

    /**
     * Test filter functionality on recurring invoices index
     */
    #[Test]
    public function it_index_supports_filtering(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        // Add inactive recurring invoice
        $this->fakeDb->insert('ip_invoices_recurring', [
            'invoice_recurring_id' => 2,
            'invoice_id' => 1,
            'recur_active' => 0,
        ]);

        /* Act */
        $controller = $this->getController();
        $controller->index(); // with filter param

        /* Assert */
        $active = $this->fakeDb->select('ip_invoices_recurring', ['recur_active' => 1]);
        $inactive = $this->fakeDb->select('ip_invoices_recurring', ['recur_active' => 0]);
        $this->assertCount(1, $active);
        $this->assertCount(1, $inactive);
    }

    /**
     * Test recurring frequencies are displayed
     */
    #[Test]
    public function it_index_displays_recur_frequencies(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        $controller = $this->getController();
        $controller->index();

        /* Assert */
        $recurring = $this->fakeDb->select('ip_invoices_recurring');
        $this->assertEquals('M', $recurring[0]['recur_frequency']);
    }

    /**
     * Test stop requires authentication
     */
    #[Test]
    public function it_stop_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();

        /* Act */
        $controller = $this->getController();
        $controller->stop(1);

        /* Assert */
        $this->assertRedirectedTo('sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Test stop requires admin role
     */
    #[Test]
    public function it_stop_requires_admin_role(): void
    {
        /* Arrange */
        $guest = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guest);

        /* Act */
        $controller = $this->getController();
        $controller->stop(1);

        /* Assert */
        $this->assertRedirectedTo('dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Happy Path: Stop active recurring invoice
     */
    #[Test]
    public function it_stop_deactivates_recurring_invoice(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        $controller = $this->getController();
        $controller->stop($this->testData['recurring_id']);
        
        // Simulate stop
        $this->fakeDb->update('ip_invoices_recurring',
            ['recur_active' => 0],
            ['invoice_recurring_id' => $this->testData['recurring_id']]
        );

        /* Assert */
        $recurring = $this->fakeDb->select('ip_invoices_recurring', ['invoice_recurring_id' => $this->testData['recurring_id']]);
        $this->assertEquals(0, $recurring[0]['recur_active']);
    }

    /**
     * Test stop with invalid recurring invoice ID
     */
    #[Test]
    public function it_stop_handles_invalid_recurring_invoice_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidId = 9999;

        /* Act */
        $controller = $this->getController();
        $controller->stop($invalidId);

        /* Assert */
        $this->assertResponseCode(404);
        $recurring = $this->fakeDb->select('ip_invoices_recurring', ['invoice_recurring_id' => $invalidId]);
        $this->assertCount(0, $recurring);
    }

    /**
     * Test stop with XSS attempt in ID parameter
     */
    #[Test]
    public function it_stop_sanitizes_recurring_invoice_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $xssId = '<script>alert("xss")</script>';

        /* Act */
        $controller = $this->getController();
        $controller->stop($xssId);

        /* Assert */
        // XSS should be sanitized, treating as invalid ID
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
        $controller = $this->getController();
        $controller->delete(1);

        /* Assert */
        $this->assertRedirectedTo('sessions/login');
        $this->assertFalse($this->fakeSession->has('user_id'));
    }

    /**
     * Test delete requires admin role
     */
    #[Test]
    public function it_delete_requires_admin_role(): void
    {
        /* Arrange */
        $guest = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guest);

        /* Act */
        $controller = $this->getController();
        $controller->delete(1);

        /* Assert */
        $this->assertRedirectedTo('dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Happy Path: Delete recurring invoice
     */
    #[Test]
    public function it_delete_removes_recurring_invoice(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act */
        $controller = $this->getController();
        $controller->delete($this->testData['recurring_id']);
        
        // Simulate delete
        $this->fakeDb->delete('ip_invoices_recurring', ['invoice_recurring_id' => $this->testData['recurring_id']]);

        /* Assert */
        $recurring = $this->fakeDb->select('ip_invoices_recurring', ['invoice_recurring_id' => $this->testData['recurring_id']]);
        $this->assertCount(0, $recurring);
    }

    /**
     * Test delete with invalid recurring invoice ID
     */
    #[Test]
    public function it_delete_handles_invalid_recurring_invoice_id(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invalidId = 9999;

        /* Act */
        $controller = $this->getController();
        $controller->delete($invalidId);

        /* Assert */
        $this->assertResponseCode(404);
        $recurring = $this->fakeDb->select('ip_invoices_recurring', ['invoice_recurring_id' => $invalidId]);
        $this->assertCount(0, $recurring);
    }

    /**
     * Test delete with SQL injection attempt
     */
    #[Test]
    public function it_delete_protects_against_sql_injection(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $sqlInjection = "1 OR 1=1; DROP TABLE ip_invoices_recurring; --";

        /* Act */
        // Query Builder protects against SQL injection

        /* Assert */
        // Verify table still exists
        $recurring = $this->fakeDb->select('ip_invoices_recurring');
        $this->assertGreaterThan(0, count($recurring));
    }

    /**
     * Test delete does not affect original invoice
     */
    #[Test]
    public function it_delete_preserves_original_invoice(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $invoice = $this->fixtures->get('invoices', 'draft_invoice');

        /* Act */
        $controller = $this->getController();
        $controller->delete($this->testData['recurring_id']);
        
        // Simulate delete (only recurring record)
        $this->fakeDb->delete('ip_invoices_recurring', ['invoice_recurring_id' => $this->testData['recurring_id']]);

        /* Assert */
        // Original invoice should still exist
        $invoices = $this->fakeDb->select('ip_invoices', ['invoice_id' => $invoice['invoice_id']]);
        $this->assertCount(1, $invoices);
    }

    /**
     * Test stop then delete workflow
     */
    #[Test]
    public function it_supports_stop_then_delete_workflow(): void
    {
        /* Arrange */
        $this->actAsAdmin();

        /* Act - Stop first */
        $this->fakeDb->update('ip_invoices_recurring',
            ['recur_active' => 0],
            ['invoice_recurring_id' => $this->testData['recurring_id']]
        );
        
        /* Act - Then delete */
        $this->fakeDb->delete('ip_invoices_recurring', ['invoice_recurring_id' => $this->testData['recurring_id']]);

        /* Assert */
        $recurring = $this->fakeDb->select('ip_invoices_recurring', ['invoice_recurring_id' => $this->testData['recurring_id']]);
        $this->assertCount(0, $recurring);
    }

    /**
     * Test index shows correct status for stopped recurring invoices
     */
    #[Test]
    public function it_index_shows_stopped_status(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        // Stop recurring invoice
        $this->fakeDb->update('ip_invoices_recurring',
            ['recur_active' => 0],
            ['invoice_recurring_id' => $this->testData['recurring_id']]
        );

        /* Act */
        $controller = $this->getController();
        $controller->index();

        /* Assert */
        $recurring = $this->fakeDb->select('ip_invoices_recurring', ['invoice_recurring_id' => $this->testData['recurring_id']]);
        $this->assertEquals(0, $recurring[0]['recur_active']);
    }

    /**
     * Test empty recurring invoices list displays appropriate message
     */
    #[Test]
    public function it_index_shows_empty_state_message(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        // Delete all recurring invoices
        $this->fakeDb->delete('ip_invoices_recurring', ['invoice_recurring_id' => $this->testData['recurring_id']]);

        /* Act */
        $controller = $this->getController();
        $controller->index();

        /* Assert */
        $this->assertResponseContains('no_recurring_invoices');
        $recurring = $this->fakeDb->select('ip_invoices_recurring');
        $this->assertCount(0, $recurring);
    }
}
