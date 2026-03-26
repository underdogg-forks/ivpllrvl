<?php

namespace Modules\Core\Tests;

use Modules\Core\Controllers\UsersController;
use Modules\Core\Testing\ControllerTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Integration tests for UsersController
 * 
 * Tests the full request/response cycle with CodeIgniter context.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(UsersController::class)]
class UsersControllerWithFakesTest extends ControllerTestCase
{
    protected string $controllerClass = UsersController::class;
    
    protected function loadFixtures(): void
    {
        // Load user fixtures
        $users = $this->fixtures->all('users');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest', 'inactive'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
    }
    
    protected function setUpController(): void
    {
        // Store valid new user data from fixtures for reuse
        $this->testData = $this->fixtures->get('users', 'valid_new_user');
    }

    #[Test]
    public function it_index_requires_authentication(): void
    {
        /* Arrange */
        $this->clearAuth();
        
        /* Act */
        // When CI bootstrap is ready, this will call the controller
        // $controller = $this->getController();
        // $controller->index();
        
        /* Assert */
        // $this->assertRedirectedTo('sessions/login');
        // Verify no session data exists
        $this->assertFalse($this->fakeSession->has('user_id'));
        
        $this->markTestIncomplete('Requires CI bootstrap for integration testing');
    }

    #[Test]
    public function it_index_requires_admin_role(): void
    {
        /* Arrange */
        $guestUser = $this->fixtures->get('users', 'guest');
        $this->actAsGuest($guestUser);
        
        /* Act */
        // $controller = $this->getController();
        // $controller->index();
        
        /* Assert */
        // $this->assertRedirectedTo('dashboard');
        // Verify session has guest user type
        $this->assertEquals(2, $this->fakeSession->get('user_type'));
        
        $this->markTestIncomplete('Requires CI bootstrap for integration testing');
    }

    #[Test]
    public function it_index_returns_user_list_for_admin(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($adminUser);
        
        /* Act */
        // $controller = $this->getController();
        // ob_start();
        // $controller->index();
        // $output = ob_get_clean();
        
        /* Assert */
        // $this->assertResponseContains('filter_users');
        // Verify we have seeded users in fake DB
        $users = $this->fakeDb->select('ip_users');
        $this->assertCount(3, $users);
        
        $this->markTestIncomplete('Requires CI bootstrap for integration testing');
    }

    #[Test]
    public function it_form_displays_new_user_form(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // $controller = $this->getController();
        // ob_start();
        // $controller->form();
        // $output = ob_get_clean();
        
        /* Assert */
        // $this->assertResponseContains('user_name');
        // $this->assertResponseContains('user_email');
        
        $this->markTestIncomplete('Requires CI bootstrap for integration testing');
    }

    #[Test]
    public function it_form_creates_new_user_with_valid_data(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData(array_merge($this->testData, [
            'btn_submit' => '1',
        ]));
        
        /* Act */
        // Insert user using fake database
        $this->fakeDb->insert('ip_users', [
            'user_name' => $this->testData['user_name'],
            'user_email' => $this->testData['user_email'],
            'user_type' => $this->testData['user_type'],
            'user_company' => $this->testData['user_company'],
            'user_active' => $this->testData['user_active'],
        ]);
        
        /* Assert */
        // Verify user was inserted
        $users = $this->fakeDb->select('ip_users', [
            'user_email' => 'newuser@example.com'
        ]);
        $this->assertCount(1, $users);
        $this->assertEquals('New Test User', $users[0]['user_name']);
        
        // Verify last insert ID
        $this->assertGreaterThan(0, $this->fakeDb->insertId());
        
        $this->markTestIncomplete('Requires CI bootstrap for integration testing');
    }

    #[Test]
    public function it_form_rejects_missing_required_fields(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData([
            'btn_submit' => '1',
            // Missing required fields
        ]);
        
        /* Act */
        
        /* Assert */
        // $this->assertHasValidationErrors();
        // $this->assertHasValidationError('user_email');
        // $this->assertHasValidationError('user_name');
        
        $this->markTestIncomplete('Requires CI bootstrap for integration testing');
    }

    #[Test]
    public function it_form_rejects_duplicate_email(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingUser = $this->fixtures->get('users', 'admin');
        
        $this->setPostData(array_merge($this->testData, [
            'btn_submit' => '1',
            'user_email' => $existingUser['user_email'], // Duplicate
        ]));
        
        /* Act */
        // Attempt to insert duplicate
        $existingUsers = $this->fakeDb->select('ip_users', [
            'user_email' => $existingUser['user_email']
        ]);
        
        /* Assert */
        // Verify user already exists
        $this->assertCount(1, $existingUsers);
        // $this->assertHasValidationError('user_email');
        
        $this->markTestIncomplete('Requires CI bootstrap for integration testing');
    }

    #[Test]
    public function it_form_updates_existing_user(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $existingUser = $this->fixtures->get('users', 'guest');
        $userId = $existingUser['user_id'];
        
        $this->setPostData([
            'btn_submit' => '1',
            'user_name' => 'Updated Name',
        ]);
        
        /* Act */
        // Update user using fake database
        $this->fakeDb->update('ip_users', 
            ['user_name' => 'Updated Name'],
            ['user_id' => $userId]
        );
        
        /* Assert */
        $updatedUsers = $this->fakeDb->select('ip_users', ['user_id' => $userId]);
        $this->assertCount(1, $updatedUsers);
        $this->assertEquals('Updated Name', $updatedUsers[0]['user_name']);
        $this->assertEquals(1, $this->fakeDb->affectedRows());
        
        $this->markTestIncomplete('Requires CI bootstrap for integration testing');
    }

    #[Test]
    public function it_delete_removes_user_record(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $userToDelete = $this->fixtures->get('users', 'inactive');
        $userId = $userToDelete['user_id'];
        
        /* Act */
        // Delete user using fake database
        $deletedCount = $this->fakeDb->delete('ip_users', ['user_id' => $userId]);
        
        /* Assert */
        $this->assertEquals(1, $deletedCount);
        $remainingUsers = $this->fakeDb->select('ip_users', ['user_id' => $userId]);
        $this->assertCount(0, $remainingUsers);
        
        $this->markTestIncomplete('Requires CI bootstrap for integration testing');
    }

    #[Test]
    public function it_tracks_database_queries(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        
        /* Act */
        // Perform various database operations
        $this->fakeDb->insert('ip_users', ['user_name' => 'Test']);
        $this->fakeDb->select('ip_users');
        $this->fakeDb->update('ip_users', ['user_name' => 'Updated'], ['user_id' => 1]);
        
        /* Assert */
        $queries = $this->fakeDb->getQueries();
        $this->assertCount(3, $queries);
        $this->assertEquals('INSERT', $queries[0]['type']);
        $this->assertEquals('SELECT', $queries[1]['type']);
        $this->assertEquals('UPDATE', $queries[2]['type']);
        
        $lastQuery = $this->fakeDb->getLastQuery();
        $this->assertEquals('UPDATE', $lastQuery['type']);
        
        $this->markTestIncomplete('Demonstrates fake database query tracking');
    }

    protected function cleanupTestData(): void
    {
        // Fake database is automatically cleared in tearDown
        // No manual cleanup needed
    }
}
