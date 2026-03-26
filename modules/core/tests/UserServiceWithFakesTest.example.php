<?php

namespace Modules\Core\Tests;

use Modules\Core\Services\UserService;
use Modules\Core\Testing\ServiceTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for UserService
 * 
 * Tests service methods, validation rules, scopes, and SQL queries.
 * Uses Fakes (not Mocks) and Fixtures for test data.
 */
#[CoversClass(UserService::class)]
class UserServiceWithFakesTest extends ServiceTestCase
{
    protected string $serviceClass = UserService::class;

    protected function loadFixtures(): void
    {
        // Load user fixtures
        $users = $this->fixtures->all('users');
        
        // Seed fake database with fixture data
        foreach (['admin', 'guest', 'inactive'] as $key) {
            $this->fakeDb->insert('ip_users', $users[$key]);
        }
    }

    protected function setUpService(): void
    {
        // Service-specific setup
        $this->testData = $this->fixtures->get('users', 'valid_new_user');
    }

    #[Test]
    public function it_has_correct_table_name(): void
    {
        /* Arrange & Act */
        
        /* Assert */
        $this->assertUsesTable('ip_users');
    }

    #[Test]
    public function it_has_correct_primary_key(): void
    {
        /* Arrange & Act */
        
        /* Assert */
        $this->assertHasPrimaryKey('ip_users.user_id');
    }

    #[Test]
    public function it_returns_user_types(): void
    {
        /* Arrange */
        $service = $this->getService();
        
        /* Act */
        $userTypes = $service->user_types();
        
        /* Assert */
        $this->assertIsArray($userTypes);
        $this->assertArrayHasKey('1', $userTypes); // Administrator
        $this->assertArrayHasKey('2', $userTypes); // Guest
        $this->assertEquals(2, count($userTypes));
    }

    #[Test]
    public function it_can_find_user_by_id_using_fake_database(): void
    {
        /* Arrange */
        $adminUser = $this->fixtures->get('users', 'admin');
        
        /* Act */
        $result = $this->fakeDb->select('ip_users', ['user_id' => 1]);
        
        /* Assert */
        $this->assertCount(1, $result);
        $this->assertEquals($adminUser['user_email'], $result[0]['user_email']);
        $this->assertEquals($adminUser['user_name'], $result[0]['user_name']);
    }

    #[Test]
    public function it_can_find_users_by_type(): void
    {
        /* Arrange */
        // Admin user has type 1
        
        /* Act */
        $adminUsers = $this->fakeDb->select('ip_users', ['user_type' => 1]);
        $guestUsers = $this->fakeDb->select('ip_users', ['user_type' => 2]);
        
        /* Assert */
        $this->assertCount(1, $adminUsers);
        $this->assertCount(2, $guestUsers); // guest and inactive
    }

    #[Test]
    public function it_can_find_active_users_only(): void
    {
        /* Arrange */
        
        /* Act */
        $activeUsers = $this->fakeDb->select('ip_users', ['user_active' => 1]);
        $inactiveUsers = $this->fakeDb->select('ip_users', ['user_active' => 0]);
        
        /* Assert */
        $this->assertCount(2, $activeUsers); // admin and guest
        $this->assertCount(1, $inactiveUsers); // inactive
    }

    #[Test]
    public function it_can_insert_new_user(): void
    {
        /* Arrange */
        $newUserData = [
            'user_name' => $this->testData['user_name'],
            'user_email' => $this->testData['user_email'],
            'user_type' => $this->testData['user_type'],
            'user_company' => $this->testData['user_company'],
            'user_active' => $this->testData['user_active'],
        ];
        
        /* Act */
        $this->fakeDb->insert('ip_users', $newUserData);
        $insertId = $this->fakeDb->insertId();
        
        /* Assert */
        $this->assertGreaterThan(0, $insertId);
        $insertedUser = $this->fakeDb->select('ip_users', ['id' => $insertId]);
        $this->assertCount(1, $insertedUser);
        $this->assertEquals($newUserData['user_email'], $insertedUser[0]['user_email']);
    }

    #[Test]
    public function it_can_update_existing_user(): void
    {
        /* Arrange */
        $userId = 2; // guest user
        $updatedData = ['user_name' => 'Updated Guest Name'];
        
        /* Act */
        $affectedRows = $this->fakeDb->update('ip_users', $updatedData, ['user_id' => $userId]);
        
        /* Assert */
        $this->assertEquals(1, $affectedRows);
        $updatedUser = $this->fakeDb->select('ip_users', ['user_id' => $userId]);
        $this->assertEquals('Updated Guest Name', $updatedUser[0]['user_name']);
    }

    #[Test]
    public function it_can_delete_user(): void
    {
        /* Arrange */
        $userId = 3; // inactive user
        
        /* Act */
        $deletedCount = $this->fakeDb->delete('ip_users', ['user_id' => $userId]);
        
        /* Assert */
        $this->assertEquals(1, $deletedCount);
        $remainingUser = $this->fakeDb->select('ip_users', ['user_id' => $userId]);
        $this->assertCount(0, $remainingUser);
        
        // Verify other users still exist
        $allUsers = $this->fakeDb->select('ip_users');
        $this->assertCount(2, $allUsers);
    }

    #[Test]
    public function it_counts_users_correctly(): void
    {
        /* Arrange & Act */
        $totalCount = $this->fakeDb->count('ip_users');
        $activeCount = $this->fakeDb->count('ip_users', ['user_active' => 1]);
        $adminCount = $this->fakeDb->count('ip_users', ['user_type' => 1]);
        
        /* Assert */
        $this->assertEquals(3, $totalCount);
        $this->assertEquals(2, $activeCount);
        $this->assertEquals(1, $adminCount);
    }

    #[Test]
    public function it_requires_user_type_field(): void
    {
        /* Arrange */
        $rules = $this->getValidationRules();
        
        /* Act & Assert */
        $this->assertFieldIsRequired($rules, 'user_type');
    }

    #[Test]
    public function it_requires_user_email_field(): void
    {
        /* Arrange */
        $rules = $this->getValidationRules();
        
        /* Act & Assert */
        $this->assertFieldIsRequired($rules, 'user_email');
    }

    #[Test]
    public function it_validates_email_format(): void
    {
        /* Arrange */
        $rules = $this->getValidationRules();
        
        /* Act */
        $emailRule = null;
        foreach ($rules as $rule) {
            if ($rule['field'] === 'user_email') {
                $emailRule = $rule;
                break;
            }
        }
        
        /* Assert */
        $this->assertNotNull($emailRule);
        $this->assertStringContainsString('valid_email', $emailRule['rules']);
    }

    #[Test]
    public function it_requires_unique_email(): void
    {
        /* Arrange */
        $rules = $this->getValidationRules();
        
        /* Act */
        $emailRule = null;
        foreach ($rules as $rule) {
            if ($rule['field'] === 'user_email') {
                $emailRule = $rule;
                break;
            }
        }
        
        /* Assert */
        $this->assertNotNull($emailRule);
        $this->assertStringContainsString('is_unique', $emailRule['rules']);
    }

    #[Test]
    public function it_enforces_minimum_password_length(): void
    {
        /* Arrange */
        $rules = $this->getValidationRules();
        
        /* Act */
        $passwordRule = null;
        foreach ($rules as $rule) {
            if ($rule['field'] === 'user_password') {
                $passwordRule = $rule;
                break;
            }
        }
        
        /* Assert */
        $this->assertNotNull($passwordRule);
        $this->assertStringContainsString('min_length[8]', $passwordRule['rules']);
    }

    #[Test]
    public function it_tracks_all_database_operations(): void
    {
        /* Arrange */
        
        /* Act */
        $this->fakeDb->insert('ip_users', ['user_name' => 'Test User']);
        $this->fakeDb->select('ip_users', ['user_type' => 1]);
        $this->fakeDb->update('ip_users', ['user_active' => 0], ['user_id' => 1]);
        $this->fakeDb->delete('ip_users', ['user_id' => 3]);
        
        /* Assert */
        $queries = $this->fakeDb->getQueries();
        $this->assertCount(4, $queries);
        
        // Verify query types
        $queryTypes = array_column($queries, 'type');
        $this->assertEquals(['INSERT', 'SELECT', 'UPDATE', 'DELETE'], $queryTypes);
    }

    #[Test]
    public function it_demonstrates_fixture_reusability(): void
    {
        /* Arrange */
        // Fixtures are loaded once and can be accessed multiple times
        $admin1 = $this->fixtures->get('users', 'admin');
        $admin2 = $this->fixtures->get('users', 'admin');
        
        /* Act & Assert */
        // Same data is returned without reloading
        $this->assertEquals($admin1, $admin2);
        $this->assertEquals('admin@example.com', $admin1['user_email']);
    }
}
