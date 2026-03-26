# Testing Infrastructure - SOLID & DRY Approach

## Overview

This testing infrastructure provides a clean, maintainable approach to testing InvoicePlane's controllers and services following SOLID and DRY principles.

## Test Types

### 1. Controller Integration Tests

**Purpose:** Test controllers with full CodeIgniter context
**Base Class:** `Modules\Core\Testing\ControllerTestCase`
**Scope:** Full request/response cycle, authentication, database interactions

### 2. Service Unit Tests

**Purpose:** Test service/model methods in isolation
**Base Class:** `Modules\Core\Testing\ServiceTestCase`
**Scope:** Business logic, validation rules, scopes, SQL queries

## Architecture

### ControllerTestCase

Provides integration testing capabilities for controllers:

```php
use Modules\Core\Testing\ControllerTestCase;

class MyControllerTest extends ControllerTestCase
{
    protected string $controllerClass = MyController::class;
    
    protected function setUpController(): void
    {
        // Controller-specific setup
        $this->testData = [/* common test data */];
    }
    
    #[Test]
    public function it_performs_action(): void
    {
        /* Arrange */
        $this->actAsAdmin();
        $this->setPostData($data);
        
        /* Act */
        $controller = $this->getController();
        $controller->action();
        
        /* Assert */
        $this->assertRedirectedTo('expected/path');
        $this->assertDatabaseHas('table', $conditions);
    }
}
```

### ServiceTestCase

Provides unit testing capabilities for services:

```php
use Modules\Core\Testing\ServiceTestCase;

class MyServiceTest extends ServiceTestCase
{
    protected string $serviceClass = MyService::class;
    
    protected function setUpService(): void
    {
        // Service-specific setup
    }
    
    #[Test]
    public function it_has_correct_table(): void
    {
        $this->assertUsesTable('ip_my_table');
    }
    
    #[Test]
    public function it_validates_required_field(): void
    {
        $rules = $this->getValidationRules();
        $this->assertFieldIsRequired($rules, 'field_name');
    }
}
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
