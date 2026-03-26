# Testing Infrastructure - SOLID & DRY with Fakes and Fixtures

## Overview

This testing infrastructure provides a clean, maintainable approach to testing InvoicePlane's controllers and services following SOLID and DRY principles. **We prefer Fakes over Mocks** and use **Fixtures** for reusable test data.

## Test Types

### 1. Controller Integration Tests

**Purpose:** Test controllers with full CodeIgniter context
**Base Class:** `Modules\Core\Testing\ControllerTestCase`
**Scope:** Full request/response cycle, authentication, database interactions
**Test Doubles:** Uses Fakes (FakeDatabase, FakeSession)
**Test Data:** Uses Fixtures from `modules/*/tests/fixtures/`

### 2. Service Unit Tests

**Purpose:** Test service/model methods in isolation
**Base Class:** `Modules\Core\Testing\ServiceTestCase`
**Scope:** Business logic, validation rules, scopes, SQL queries
**Test Doubles:** Uses Fakes (FakeDatabase)
**Test Data:** Uses Fixtures from `modules/*/tests/fixtures/`

## Why Fakes Over Mocks?

**Fakes** are preferred because they:
- Provide more realistic behavior (actual in-memory implementations)
- Are easier to understand and maintain
- Don't require complex mock setup and expectations
- Make tests more resilient to refactoring
- Better simulate real-world scenarios

**Example:**
```php
// ❌ Mock (fragile, complex)
$mockDb = $this->createMock(Database::class);
$mockDb->expects($this->once())
    ->method('insert')
    ->with('users', $this->anything())
    ->willReturn(true);

// ✅ Fake (simple, realistic)
$this->fakeDb->insert('users', $data);
$result = $this->fakeDb->select('users', ['id' => 1]);
```

## Fixtures

Fixtures are reusable test data defined in PHP files. They promote DRY principles and consistency across tests.

### Creating Fixtures

Create fixture files in `modules/*/tests/fixtures/`:

```php
// modules/core/tests/fixtures/users.php
return [
    'admin' => [
        'user_id' => 1,
        'user_name' => 'Admin User',
        'user_email' => 'admin@example.com',
        // ... more fields
    ],
    'guest' => [
        'user_id' => 2,
        'user_name' => 'Guest User',
        'user_email' => 'guest@example.com',
        // ... more fields
    ],
];
```

### Using Fixtures

Load and use fixtures in tests:

```php
protected function loadFixtures(): void
{
    // Load all users
    $users = $this->fixtures->all('users');
    
    // Seed fake database
    foreach (['admin', 'guest'] as $key) {
        $this->fakeDb->insert('ip_users', $users[$key]);
    }
}

#[Test]
public function it_finds_user_by_email(): void
{
    $admin = $this->fixtures->get('users', 'admin');
    $result = $this->fakeDb->select('ip_users', [
        'user_email' => $admin['user_email']
    ]);
    $this->assertCount(1, $result);
}
```

## Architecture

### ControllerTestCase

Provides integration testing capabilities for controllers with Fakes and Fixtures:

```php
use Modules\Core\Testing\ControllerTestCase;

class MyControllerTest extends ControllerTestCase
{
    protected string $controllerClass = MyController::class;
    
    protected function loadFixtures(): void
    {
        // Load fixtures and seed fake database
        $users = $this->fixtures->all('users');
        foreach ($users as $user) {
            $this->fakeDb->insert('ip_users', $user);
        }
    }
    
    protected function setUpController(): void
    {
        // Controller-specific setup
        $this->testData = $this->fixtures->get('users', 'valid_new_user');
    }
    
    #[Test]
    public function it_performs_action(): void
    {
        /* Arrange */
        $admin = $this->fixtures->get('users', 'admin');
        $this->actAsAdmin($admin);
        $this->setPostData($this->testData);
        
        /* Act */
        $controller = $this->getController();
        $controller->action();
        
        /* Assert */
        $this->assertRedirectedTo('expected/path');
        $this->assertDatabaseHas('table', $conditions);
    }
}
```
        
        /* Assert */
        $this->assertRedirectedTo('expected/path');
        $this->assertDatabaseHas('table', $conditions);
    }
}
```

### ServiceTestCase

Provides unit testing capabilities for services with Fakes and Fixtures:

```php
use Modules\Core\Testing\ServiceTestCase;

class MyServiceTest extends ServiceTestCase
{
    protected string $serviceClass = MyService::class;
    
    protected function loadFixtures(): void
    {
        // Load fixtures and seed fake database
        $clients = $this->fixtures->all('clients');
        foreach ($clients as $client) {
            $this->fakeDb->insert('ip_clients', $client);
        }
    }
    
    protected function setUpService(): void
    {
        // Service-specific setup
        $this->testData = $this->fixtures->get('clients', 'valid_new_client');
    }
    
    #[Test]
    public function it_has_correct_table(): void
    {
        $this->assertUsesTable('ip_my_table');
    }
    
    #[Test]
    public function it_can_insert_record(): void
    {
        $this->fakeDb->insert('ip_clients', $this->testData);
        $result = $this->fakeDb->select('ip_clients');
        $this->assertCount(1, $result);
    }
}
```

## Available Fakes

### FakeDatabase

In-memory database implementation for testing database operations:

```php
// Insert
$this->fakeDb->insert('table_name', ['field' => 'value']);
$id = $this->fakeDb->insertId(); // Get last insert ID

// Select
$results = $this->fakeDb->select('table_name');
$filtered = $this->fakeDb->select('table_name', ['status' => 'active']);

// Update
$affected = $this->fakeDb->update('table_name', 
    ['field' => 'new_value'], 
    ['id' => 1]
);

// Delete
$deleted = $this->fakeDb->delete('table_name', ['id' => 1]);

// Count
$count = $this->fakeDb->count('table_name');
$activeCount = $this->fakeDb->count('table_name', ['status' => 'active']);

// Query tracking
$queries = $this->fakeDb->getQueries(); // All queries
$lastQuery = $this->fakeDb->getLastQuery(); // Most recent query

// Utility
$this->fakeDb->truncate('table_name');
$this->fakeDb->clear(); // Clear all data
```

### FakeSession

In-memory session implementation for testing authentication and session data:

```php
// Set/Get data
$this->fakeSession->set('key', 'value');
$value = $this->fakeSession->get('key', 'default');
$this->fakeSession->setMultiple(['key1' => 'val1', 'key2' => 'val2']);

// Check existence
if ($this->fakeSession->has('key')) {
    // Key exists
}

// Flash data (one-time use)
$this->fakeSession->setFlash('message', 'Success!');
$message = $this->fakeSession->getFlash('message');

// Temporary data (with TTL)
$this->fakeSession->setTemp('key', 'value', 300); // 5 minutes
$value = $this->fakeSession->getTemp('key');

// Clear/Destroy
$this->fakeSession->remove('key');
$this->fakeSession->clear(); // Clear all
$this->fakeSession->destroy(); // Destroy session
```

## Fixture Loader

Load and manage test fixtures:

```php
// Load all items from a fixture
$users = $this->fixtures->all('users');

// Load specific item
$admin = $this->fixtures->get('users', 'admin');
$guest = $this->fixtures->get('users', 'guest');

// Set custom fixtures path
$this->fixtures->setFixturesPath('/custom/path/to/fixtures');

// Clear loaded fixtures
$this->fixtures->clear();
```

## Key Principles

### SOLID

**Single Responsibility:**
- `ControllerTestCase` handles controller integration testing
- `ServiceTestCase` handles service unit testing
- Each test method tests one specific behavior

**Open/Closed:**
- Base classes are open for extension via hooks (`setUpController`, `setUpService`)
- Closed for modification - child classes don't need to override core behavior

**Liskov Substitution:**
- All controller tests can use `ControllerTestCase` methods consistently
- All service tests can use `ServiceTestCase` methods consistently

**Interface Segregation:**
- Controller tests only have controller-testing methods
- Service tests only have service-testing methods

**Dependency Inversion:**
- Tests depend on abstractions (base classes) not concrete implementations

### DRY (Don't Repeat Yourself)

**Avoid Duplication Through:**

1. **setUp() Methods:** Common initialization in base classes
2. **Helper Methods:** Reusable assertions and actions
3. **Test Data in setUp:** Shared data defined once in `setUpController()`/`setUpService()`
4. **Base Class Utilities:** Common operations (auth, database, assertions)

### Dynamic & Early Returns

**Dynamic:**
- Test data configured in `setUp()` methods
- Controller/Service instances created on-demand
- Flexible assertion methods that adapt to context

**Early Returns:**
- Tests fail fast with clear assertions
- `markTestIncomplete()` for pending implementation
- Clear error messages on assertion failures

## Common Patterns

### Controller Test Pattern

```php
#[Test]
public function it_describes_behavior(): void
{
    /* Arrange */
    $this->actAsAdmin(); // or actAsGuest() or clearAuth()
    $this->setPostData($data);
    
    /* Act */
    $controller = $this->getController();
    $controller->method();
    
    /* Assert */
    $this->assertRedirectedTo('path');
    $this->assertDatabaseHas('table', $conditions);
    $this->assertHasValidationErrors();
}
```

### Service Test Pattern

```php
#[Test]
public function it_describes_behavior(): void
{
    /* Arrange */
    $service = $this->getService();
    
    /* Act */
    $result = $service->method();
    
    /* Assert */
    $this->assertIsArray($result);
    $this->assertCount(2, $result);
}
```

## Available Assertions

### Controller Assertions

- `assertRedirectedTo(string $location)`
- `assertResponseContains(string $expected)`
- `assertHasValidationErrors()`
- `assertHasValidationError(string $field)`
- `assertDatabaseHas(string $table, array $conditions)`
- `assertDatabaseMissing(string $table, array $conditions)`

### Service Assertions

- `assertUsesTable(string $expectedTable)`
- `assertHasPrimaryKey(string $expectedKey)`
- `assertHasValidationRule(array $rules, string $field)`
- `assertFieldIsRequired(array $rules, string $field)`
- `assertServiceHasMethod(string $method)`
- `assertServiceHasProperty(string $property)`
- `assertQueryContains(string $query, string $expected)`
- `assertIsSelectQuery(string $query)`
- `assertIsInsertQuery(string $query)`
- `assertIsUpdateQuery(string $query)`
- `assertIsDeleteQuery(string $query)`

## Helper Methods

### Controller Helpers

- `actAsAdmin(array $userData = []): array` - Authenticate as admin
- `actAsGuest(array $userData = []): array` - Authenticate as guest
- `clearAuth(): void` - Clear authentication
- `setPostData(array $data): void` - Set POST data for request
- `getController(): mixed` - Get controller instance
- `createTestRecord(string $table, array $data): int` - Create test data
- `deleteTestRecord(string $table, array $conditions): void` - Clean up test data

### Service Helpers

- `getService(): mixed` - Get service instance
- `getValidationRules(string $rulesMethod = 'validation_rules'): array` - Get validation rules
- `invokeMethod(string $methodName, array $parameters = []): mixed` - Call protected method
- `getProperty(string $propertyName): mixed` - Get protected property
- `setProperty(string $propertyName, mixed $value): void` - Set protected property

## Migration Path

### Phase 1: Infrastructure (Current)

✅ Created `ControllerTestCase` base class
✅ Created `ServiceTestCase` base class
✅ Created example tests demonstrating patterns
✅ Documented approach in this README

### Phase 2: CodeIgniter Bootstrap

- [ ] Implement full CI bootstrap for integration tests
- [ ] Enable controller instantiation with CI context
- [ ] Enable database operations
- [ ] Enable session/auth management

### Phase 3: Test Implementation

- [ ] Refactor all 800+ controller tests to use `ControllerTestCase`
- [ ] Create service unit tests using `ServiceTestCase`
- [ ] Remove `markTestIncomplete()` as CI bootstrap is ready
- [ ] Add database seeders for test data

### Phase 4: Laravel Migration

- [ ] Update `ControllerTestCase` to support Laravel
- [ ] Update `ServiceTestCase` for Laravel models
- [ ] Migrate tests incrementally as controllers move to Laravel
- [ ] Maintain backwards compatibility during transition

## Examples

See:
- `modules/core/tests/UsersControllerTest.example.php` - Controller integration test
- `modules/core/tests/UserServiceTest.example.php` - Service unit test

## Best Practices

1. **One Behavior Per Test:** Each test method tests one specific behavior
2. **Descriptive Names:** Use `it_action_behavior` naming convention
3. **AAA Pattern:** Always use Arrange, Act, Assert with block comments
4. **setUp() for DRY:** Common initialization in setUp methods
5. **Clean Up:** Use `cleanupTestData()` or `tearDown()` to remove test data
6. **Early Returns:** Fail fast with clear assertions
7. **No Magic:** Use explicit, readable test code
8. **Document Intent:** Use clear variable names and comments when needed

## Anti-Patterns to Avoid

❌ Duplicate test setup across multiple methods
❌ Hard-coded values instead of using test data
❌ Testing multiple behaviors in one method
❌ Custom test infrastructure (like `HttpTestCase::routeRequest()`)
❌ Mass-produced tests with copy-paste code
❌ Unclear test names
❌ Missing assertions
❌ Commented out code

## Questions?

For questions about the testing infrastructure, see:
- Base classes: `modules/core/src/Testing/`
- Example tests: `modules/core/tests/*.example.php`
- Project documentation: `TESTING_STRATEGY.md`
