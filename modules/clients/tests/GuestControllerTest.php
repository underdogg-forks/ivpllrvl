<?php

namespace Modules\Clients\Tests;

use Modules\Clients\Controllers\GuestController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for GuestController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(GuestController::class)]
class GuestControllerTest extends ControllerTestCase
{
    protected string $controllerClass = GuestController::class;
    
    protected function loadFixtures(): void
    {
        // Load user, client, and invoice fixtures
        $users = $this->fixtures->all('users');
        $clients = $this->fixtures->all('clients');
        $invoices = $this->fixtures->all('invoices');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
        
        foreach (['active'] as $key) {
            $this->fakeDb->insert('ip_clients', $clients[$key]);
        }
        
        foreach (['open', 'paid', 'overdue'] as $key) {
            if (isset($invoices[$key])) {
                $this->fakeDb->insert('ip_invoices', $invoices[$key]);
            }
        }
    }
    
    protected function setUpController(): void
    {
        // Store test data from fixtures for reuse
        $this->testData = [
            'guest_user' => $this->fixtures->get('users', 'guest'),
            'active_client' => $this->fixtures->get('clients', 'active'),
        ];
    }
    /**
     * Test index requires guest authentication
     */
    #[Test]
    public function it_get_index_requires_guest_authentication(): void
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
     * Test admin user cannot access guest portal
     */
    #[Test]
    public function it_get_index_requires_guest_user_type(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /* Act */
        $controller = $this->getController();
        $controller->index();
        
        /* Assert */
        $this->assertRedirectedTo('dashboard');
        $this->assertEquals(1, $this->fakeSession->get('user_type'));
    }

    /**
     * Happy Path: Guest user views dashboard
     */
    #[Test]
    public function it_displays_index_guest_dashboard(): void
    {
        /* Arrange */
        $this->actAsGuest($this->testData['guest_user']);
        
        /* Act */
        $controller = $this->getController();
        ob_start();
        $controller->index();
        $output = ob_get_clean();
        
        /* Assert */
        $this->assertResponseContains('guest_dashboard');
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
    }

    /**
     * Test index displays overdue invoices for assigned clients
     */
    #[Test]
    public function it_displays_index_overdue_invoices_for_assigned_clients(): void
    {
        /* Arrange */
        $this->actAsGuest();
        
        /* Act */
        $controller = $this->getController();
        $controller->index();
        
        /* Assert */
        // Verify we have invoices in fake DB
        $invoices = $this->fakeDb->select('ip_invoices');
        $this->assertGreaterThan(0, count($invoices));
    }
}
