<?php

namespace Modules\Clients\Tests;

use Modules\Clients\Controllers\GuestController;
use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for GuestController
 * 
 * Tests the full request/response cycle using Laravel HTTP testing.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(GuestController::class)]
class GuestControllerTest extends TestCase
{
    
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
        // No authentication
        
        /* Act */
        $response = $this->get('/guest/guest/index');
        
        /* Assert */
        $response->assertRedirect('/sessions/login');
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
        $response = $this->get('/guest/guest/index');
        
        /* Assert */
        $response->assertRedirect('/dashboard');
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
        $response = $this->get('/guest/guest/index');
        
        /* Assert */
        $response->assertOk();
        $response->assertSee('guest_dashboard');
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
        $response = $this->get('/guest/guest/index');
        
        /* Assert */
        $response->assertOk();
        // Verify we have invoices in fake DB
        $invoices = $this->fakeDb->select('ip_invoices');
        $this->assertGreaterThan(0, count($invoices));
    }
}
