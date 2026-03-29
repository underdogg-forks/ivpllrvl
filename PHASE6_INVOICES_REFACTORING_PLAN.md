# Phase 6: Invoices Module Test Refactoring

## Status: IN PROGRESS - Requires completion

## Files to Refactor (107 tests total)
1. ✅ InvoicesControllerTest.php (28 tests) - READY TO REFACTOR  
2. ✅ InvoicesAjaxControllerTest.php (24 tests) - READY TO REFACTOR
3. ✅ RecurringControllerTest.php (20 tests) - READY TO REFACTOR
4. ✅ InvoiceGroupsControllerTest.php (18 tests) - READY TO REFACTOR
5. ✅ CronControllerTest.php (16 tests) - READY TO REFACTOR
6. ✅ InvoiceModuleBootTest.php (1 test) - READY TO REFACTOR

## Required Changes Pattern

### 1. Change Base Class
```php
// FROM:
use Modules\Core\Testing\TestCase;
class InvoicesControllerTest extends TestCase

// TO:
use Modules\Core\Testing\ControllerTestCase;
class InvoicesControllerTest extends ControllerTestCase
```

### 2. Add Trait Imports
```php
use Modules\Core\Testing\Traits\LoadsFixtures;
use Modules\Core\Testing\Traits\ProvidesTestData;
use Modules\Core\Testing\Traits\ProvidesAssertions;

class InvoicesControllerTest extends ControllerTestCase
{
    use LoadsFixtures;
    use ProvidesTestData;
    use ProvidesAssertions;
    
    protected string $controllerClass = InvoicesController::class;
```

### 3. Replace setUp() with fixtureTypes() and loadFixtures()
```php
// REMOVE:
protected function setUp(): void
{
    parent::setUp();
    $this->testData = [...];
}

protected function loadFixtures(): void
{
    $users = $this->fixtures->all('users');
    foreach (['admin', 'guest'] as $key) {
        $this->fakeDb->insert('ip_users', $users[$key]);
    }
}

// ADD:
protected function fixtureTypes(): array
{
    return ['users', 'clients', 'invoices', 'products', 'tax_rates'];
}

protected function loadFixtures(): void
{
    $this->loadAllFixtures();
}

protected function setUpController(): void
{
    // Intentionally empty - test data provided via ProvidesTestData trait
}
```

### 4. Add #region Markers
```php
// #region Authentication & Authorization Tests
// ... tests ...
// #endregion

// #region CRUD Operations Tests
// ... tests ...
// #endregion

// #region Validation Tests
// ... tests ...
// #endregion

// #region Security Tests
// ... tests ...
// #endregion
```

### 5. Add PHPDoc Above Act Sections
```php
#[Test]
public function it_creates_invoice_with_valid_data(): void
{
    /* Arrange */
    $this->actAsAdmin();
    
    /**
     * Act: POST /invoices/form
     * POST Data: {
     *   client_id: 1,
     *   invoice_date_created: 2024-01-15,
     *   invoice_date_due: 2024-02-15,
     *   invoice_status_id: 1,
     *   invoice_number: INV-2024-001,
     *   items: [{...}]
     * }
     * Expected: Success redirect with flash message
     */
    $response = $this->post('/invoices/form', $this->makeInvoiceData());
    
    /* Assert */
    $this->assertSuccessfulCreate($response);
}
```

### 6. Replace Inline Arrays with Trait Calls
```php
// BEFORE:
$this->setPostData([
    'client_id' => '1',
    'invoice_date_created' => date('Y-m-d'),
    'invoice_date_due' => date('Y-m-d', strtotime('+30 days')),
    // ... 20 more fields
]);

// AFTER:
$postData = $this->makeInvoiceData(['client_id' => '']); // Test missing client_id
$response = $this->post('/invoices/form', $postData);
```

### 7. Replace Basic Assertions with Trait Methods
```php
// BEFORE:
$response->assertRedirect('/sessions/login');

// AFTER:
$this->assertRequiresAuthentication($response);

// BEFORE:
$response->assertStatus(200);
$response->assertSee('Success');

// AFTER:
$this->assertSuccessfulCreate($response);
```

## Data Providers Needed in ProvidesTestData

### makeInvoiceData() - Already exists, verify completeness
```php
protected function makeInvoiceData(array $overrides = []): array
{
    $defaults = [
        'client_id' => '1',
        'invoice_date_created' => date('Y-m-d'),
        'invoice_date_due' => date('Y-m-d', strtotime('+30 days')),
        'invoice_status_id' => '1',
        'invoice_number' => 'INV-' . date('Ymd') . '-001',
        'invoice_terms' => 'Net 30',
        'invoice_discount_percent' => '0.00',
        'invoice_discount_amount' => '0.00',
        'invoice_currency' => 'USD',
        'invoice_group_id' => '1',
        'user_id' => '1',
    ];
    return array_merge($defaults, $overrides);
}
```

### makeInvoiceGroupData() - NEEDS TO BE ADDED
```php
protected function makeInvoiceGroupData(array $overrides = []): array
{
    $defaults = [
        'invoice_group_name' => 'Test Invoice Group',
        'invoice_group_prefix' => 'INV',
        'invoice_group_next_id' => '1',
        'invoice_group_left_pad' => '4',
    ];
    return array_merge($defaults, $overrides);
}
```

### makeRecurringInvoiceData() - NEEDS TO BE ADDED
```php
protected function makeRecurringInvoiceData(array $overrides = []): array
{
    $defaults = [
        'invoice_id' => '1',
        'recur_frequency' => 'M', // Monthly
        'recur_start_date' => date('Y-m-d'),
        'recur_end_date' => date('Y-m-d', strtotime('+1 year')),
        'recur_next_date' => date('Y-m-d', strtotime('+1 month')),
    ];
    return array_merge($defaults, $overrides);
}
```

### makeCronConfigData() - NEEDS TO BE ADDED
```php
protected function makeCronConfigData(array $overrides = []): array
{
    $defaults = [
        'cron_key' => 'test-cron-key-' . bin2hex(random_bytes(16)),
        'recur_invoices_enabled' => '1',
        'overdue_reminders_enabled' => '1',
    ];
    return array_merge($defaults, $overrides);
}
```

## Test Name Improvements Needed

Make test names grammatically correct:

- `it_displays_invoices_index_requires_authentication` → `it_requires_authentication_to_view_invoices_index`
- `it_post_save_updates_invoice_with_valid_credentials` → `it_saves_invoice_with_valid_data`
- `it_shows_invoices_status_draft_only_draft_invoices` → `it_filters_invoices_by_draft_status`

## Critical Requirements

1. **ZERO test deletions** - All 107 tests must be preserved
2. **Verify test counts** after refactoring each file
3. **Run tests** after each file to ensure functionality preserved
4. **Maintain financial operation integrity** - These tests validate money operations

## Next Steps

1. Add missing data provider methods to ProvidesTestData trait
2. Refactor each test file following the pattern above
3. Run full test suite after each file
4. Verify 107 tests still pass
5. Create PHASE6_INVOICES_COMPLETE.md summary

## Commands to Run After Refactoring

```bash
# Verify test count
grep -r "function it_" modules/invoices/tests/ | wc -l  # Should be 107

# Run invoice tests
vendor/bin/phpunit modules/invoices/tests/

# Check syntax
php -l modules/invoices/tests/*.php
```
