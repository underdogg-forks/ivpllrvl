# Phase 3: Test Implementation Guide

## Overview

This guide documents the approach for refactoring all 800+ controller tests to use the new ControllerTestCase infrastructure with Fakes and Fixtures.

## Completed Work

### ✅ Comprehensive Fixtures Library

Created comprehensive fixtures for all major entities:

- `users.php` - User test data (admin, guest, inactive, new user)
- `clients.php` - Client test data (active, inactive, new client)
- `invoices.php` - Invoice test data (draft, sent, paid)
- `products.php` - Product test data (standard, service, new product)
- `quotes.php` - Quote test data (draft, sent, approved)
- `payments.php` - Payment test data (cash, bank transfer, credit card)
- `projects.php` - Project test data (active, completed, new project)
- `tasks.php` - Task test data (pending, in progress, completed)
- `tax_rates.php` - Tax rate test data (standard, reduced, zero)

All fixtures follow a consistent pattern with common scenarios:
- Active/standard entities for happy path testing
- Inactive/completed entities for state testing
- `valid_new_*` templates for creation testing

### ✅ Proof of Concept: UsersControllerTest

Refactored `modules/core/tests/UsersControllerTest.php` to demonstrate the pattern:

**Key Changes:**
1. Extended `ControllerTestCase` instead of `TestCase`
2. Implemented `loadFixtures()` to seed fake database
3. Implemented `setUpController()` for DRY test data
4. Updated all test methods to:
   - Use fixtures via `$this->fixtures->get()` and `$this->fixtures->all()`
   - Use fake database via `$this->fakeDb->select()`, `insert()`, etc.
   - Use fake session via `$this->fakeSession->has()`, `get()`, etc.
   - Use authentication helpers via `$this->actAsAdmin()`, `actAsGuest()`, `clearAuth()`
   - Keep `markTestIncomplete()` with proper message about CI bootstrap
   - Include commented-out code showing what will be enabled when CI bootstrap is ready

## Refactoring Pattern

### Step 1: Update Class Declaration

```php
// Before
use Modules\Core\Testing\TestCase;
class MyControllerTest extends TestCase

// After
use Modules\Core\Testing\ControllerTestCase;
class MyControllerTest extends ControllerTestCase
{
    protected string $controllerClass = MyController::class;
```

### Step 2: Implement Fixtures Loading

```php
protected function loadFixtures(): void
{
    // Load relevant fixtures
    $users = $this->fixtures->all('users');
    $clients = $this->fixtures->all('clients');
    
    // Seed fake database
    foreach (['admin', 'guest'] as $key) {
        $this->fakeDb->insert('ip_users', $users[$key]);
    }
    
    foreach (['active_client', 'inactive_client'] as $key) {
        $this->fakeDb->insert('ip_clients', $clients[$key]);
    }
}
```

### Step 3: Implement Test Data Setup

```php
protected function setUpController(): void
{
    // Store common test data for reuse
    $this->testData = $this->fixtures->get('products', 'valid_new_product');
    
    // Or combine multiple fixtures
    $this->testData = [
        'user' => $this->fixtures->get('users', 'valid_new_user'),
        'client' => $this->fixtures->get('clients', 'valid_new_client'),
    ];
}
```

### Step 4: Update Test Methods

```php
#[Test]
public function it_action_with_valid_data(): void
{
    /* Arrange */
    $admin = $this->fixtures->get('users', 'admin');
    $this->actAsAdmin($admin);
    $this->setPostData(array_merge($this->testData, [
        'btn_submit' => '1',
    ]));
    
    /* Act */
    // When CI bootstrap is ready:
    // $controller = $this->getController();
    // $controller->action();
    
    // Simulate database operations with fakes
    $this->fakeDb->insert('ip_table', $this->testData);
    
    /* Assert */
    // $this->assertRedirectedTo('expected/path');
    
    // Verify with fake database
    $records = $this->fakeDb->select('ip_table', ['field' => 'value']);
    $this->assertCount(1, $records);
    $this->assertEquals('expected', $records[0]['field']);
    
    $this->markTestIncomplete('Requires CI bootstrap for integration testing');
}
```

## Fixture Usage Patterns

### Loading All Items from a Fixture

```php
$users = $this->fixtures->all('users');
foreach (['admin', 'guest', 'inactive'] as $key) {
    $this->fakeDb->insert('ip_users', $users[$key]);
}
```

### Loading Specific Items

```php
$adminUser = $this->fixtures->get('users', 'admin');
$this->actAsAdmin($adminUser);
```

### Using Template Data for Creation

```php
$newUserData = $this->fixtures->get('users', 'valid_new_user');
$this->setPostData(array_merge($newUserData, [
    'btn_submit' => '1',
    // Override specific fields if needed
    'user_email' => 'custom@example.com',
]));
```

## Fake Database Operations

### Insert

```php
$this->fakeDb->insert('ip_users', [
    'user_name' => 'Test User',
    'user_email' => 'test@example.com',
]);
$insertId = $this->fakeDb->insertId();
```

### Select

```php
// Select all
$all = $this->fakeDb->select('ip_users');

// Select with conditions
$admins = $this->fakeDb->select('ip_users', ['user_type' => 1]);
```

### Update

```php
$affected = $this->fakeDb->update('ip_users', 
    ['user_name' => 'Updated Name'],
    ['user_id' => 1]
);
```

### Delete

```php
$deleted = $this->fakeDb->delete('ip_users', ['user_id' => 3]);
```

### Count

```php
$count = $this->fakeDb->count('ip_users');
$activeCount = $this->fakeDb->count('ip_users', ['user_active' => 1]);
```

## Fake Session Operations

### Set and Get

```php
$this->fakeSession->set('key', 'value');
$value = $this->fakeSession->get('key', 'default');
```

### Check Existence

```php
if ($this->fakeSession->has('user_id')) {
    // Session key exists
}
```

### Multiple Values

```php
$this->fakeSession->setMultiple([
    'user_id' => 1,
    'user_type' => 1,
    'user_email' => 'admin@example.com',
]);
```

### Clear

```php
$this->fakeSession->clear(); // Clear all
$this->fakeSession->remove('key'); // Remove specific key
```

## Authentication Helpers

### Act as Admin

```php
$admin = $this->fixtures->get('users', 'admin');
$this->actAsAdmin($admin);

// Or with defaults
$this->actAsAdmin();
```

### Act as Guest

```php
$guest = $this->fixtures->get('users', 'guest');
$this->actAsGuest($guest);
```

### Clear Authentication

```php
$this->clearAuth();
$this->assertFalse($this->fakeSession->has('user_id'));
```

## Remaining Work

### Controllers to Refactor (44 remaining)

**Core Module (18 remaining):**
- SessionsControllerTest
- DashboardControllerTest
- SettingsControllerTest
- CustomFieldsControllerTest
- CustomValuesControllerTest
- EmailTemplatesControllerTest
- EmailTemplatesAjaxControllerTest
- FilterAjaxControllerTest
- ImportControllerTest
- LayoutControllerTest
- MailerControllerTest
- ReportsControllerTest
- SettingsAjaxControllerTest
- SetupControllerTest
- TaxRatesControllerTest
- UploadControllerTest
- UsersAjaxControllerTest
- VersionsControllerTest
- WelcomeControllerTest

**Clients Module (10):**
- ClientsControllerTest
- ClientsAjaxControllerTest
- GetControllerTest
- GuestControllerTest
- InvoicesControllerTest
- PaymentInformationControllerTest
- PaymentsControllerTest
- QuotesControllerTest
- UserClientsControllerTest
- ViewControllerTest

**Invoices Module (5):**
- InvoicesControllerTest
- InvoicesAjaxControllerTest
- CronControllerTest
- InvoiceGroupsControllerTest
- RecurringControllerTest

**Payments Module (3):**
- PaymentsControllerTest
- PaymentsAjaxControllerTest
- PaymentMethodsControllerTest

**Products Module (4):**
- ProductsControllerTest
- ProductsAjaxControllerTest
- FamiliesControllerTest
- UnitsControllerTest

**Projects Module (3):**
- ProjectsControllerTest
- TasksControllerTest
- TasksAjaxControllerTest

**Quotes Module (2):**
- QuotesControllerTest
- QuotesAjaxControllerTest

### Service Tests (To Be Created)

Service unit tests should be created for all services/models using `ServiceTestCase`:

**Example Service Test Structure:**
```php
<?php

namespace Modules\Core\Tests;

use Modules\Core\Services\MyService;
use Modules\Core\Testing\ServiceTestCase;
use PHPUnit\Framework\Attributes\Test;

class MyServiceTest extends ServiceTestCase
{
    protected string $serviceClass = MyService::class;
    
    protected function loadFixtures(): void
    {
        $items = $this->fixtures->all('items');
        foreach ($items as $item) {
            $this->fakeDb->insert('ip_items', $item);
        }
    }
    
    #[Test]
    public function it_has_correct_table_name(): void
    {
        $this->assertUsesTable('ip_items');
    }
    
    #[Test]
    public function it_can_find_item_by_id(): void
    {
        $item = $this->fixtures->get('items', 'standard');
        
        $result = $this->fakeDb->select('ip_items', ['id' => $item['id']]);
        
        $this->assertCount(1, $result);
        $this->assertEquals($item['name'], $result[0]['name']);
    }
}
```

## Next Steps

1. **Refactor Remaining Controller Tests** - Apply the pattern demonstrated in UsersControllerTest to the remaining 44 controller tests
2. **Create Service Tests** - Create comprehensive service unit tests for all models/services
3. **Implement CI Bootstrap** - Complete CodeIgniter bootstrap integration to enable actual controller execution
4. **Remove markTestIncomplete()** - Once CI bootstrap is ready, uncomment controller calls and remove incomplete markers
5. **Continuous Integration** - Ensure all tests run in CI pipeline

## Benefits of This Approach

✅ **DRY** - Fixtures defined once, used across all tests
✅ **SOLID** - Clean separation of concerns with base classes
✅ **Realistic** - Fakes provide actual in-memory implementations
✅ **Maintainable** - Consistent pattern across all tests
✅ **Fast** - No external dependencies, all in-memory
✅ **Future-Proof** - Easy to migrate to Laravel later

## Questions or Issues?

Refer to:
- `TESTING_INFRASTRUCTURE.md` - Complete infrastructure documentation
- `UsersControllerWithFakesTest.example.php` - Additional example with 15+ tests
- `UserServiceWithFakesTest.example.php` - Service test example with 20+ tests
