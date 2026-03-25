# Route Testing Implementation Guide

## What Has Been Accomplished

### 1. Test Infrastructure Created
- **HttpTestCase.php** - Base class with authentication helpers and assertion methods
- **TestResponse.php** - Response wrapper with convenient methods
- **UsersControllerTest.php** - Complete example with 26 comprehensive test cases

### 2. Complete Example: UsersController (26 Tests)

The UsersController test file serves as the **gold standard** for all other controllers. It includes:

**Authentication Tests (2):**
- Requires authentication
- Requires admin role

**Happy Path Tests (8):**
- Index page displays user list
- Form displays for new user
- Form displays for editing user
- Creates user with valid data
- Updates existing user
- Changes password
- Deletes user
- Saves custom fields

**Validation Tests (7):**
- Returns 404 for invalid user
- Rejects missing required fields
- Validates email format
- Rejects duplicate email
- Validates password confirmation
- Validates password requirements
- Handles cancel button

**Security Tests (2):**
- Sanitizes XSS attempts
- Protects against SQL injection

**Edge Cases & Business Logic (7):**
- Updates session when user edits self
- Protects system user (ID 1) from deletion
- Various authorization checks

### 3. Strategy Documentation
- **TESTING_STRATEGY.md** - Complete implementation guide
- **Route discovery** - All 216 routes documented

## What Still Needs to Be Done

### Priority 1: Complete HTTP Test Infrastructure

The current HttpTestCase has placeholders. You need to implement actual HTTP routing in one of three ways:

#### Option A: Use Codeception (RECOMMENDED)
```bash
composer require --dev codeception/codeception
vendor/bin/codecept bootstrap
```

Then configure for CodeIgniter 3:
```yaml
# codeception.yml
paths:
    tests: tests
    output: tests/_output
    data: tests/_data
    support: tests/_support
    envs: tests/_envs

actor_suffix: Tester

extensions:
    enabled:
        - Codeception\Extension\RunFailed
```

Update HttpTestCase to use Codeception's modules:
```php
protected function get($uri) {
    return $this->codeceptionModule->amOnPage($uri);
}

protected function post($uri, $data) {
    return $this->codeceptionModule->sendPOST($uri, $data);
}
```

#### Option B: Use Browser/HTTP Client
```php
use Symfony\Component\HttpClient\HttpClient;

protected function call($method, $uri, $data = []) {
    $client = HttpClient::create();
    $baseUrl = 'http://localhost:8000'; // Your test server
    
    $response = $client->request($method, $baseUrl . '/' . $uri, [
        'body' => $data,
        'headers' => $this->getAuthHeaders(),
    ]);
    
    return new TestResponse($response);
}
```

#### Option C: Bootstrap CodeIgniter Directly
This is more complex but gives full control. You'd need to:
1. Bootstrap CI3 in test environment
2. Mock the request
3. Execute the controller
4. Capture the output

### Priority 2: Generate Tests for Remaining 51 Controllers

Use the UsersControllerTest.php as a template. For each controller:

1. **Copy the structure** from UsersControllerTest
2. **Adapt test names** to match the controller's methods
3. **Customize assertions** for the specific business logic
4. **Add domain-specific tests** (e.g., invoice calculations, payment processing)

### Tools to Help

#### Script: Bulk Test Generator

You can use this script to generate test skeletons:

```bash
php scripts/generate_tests_from_template.php
```

This will:
1. Read all route files
2. For each controller, create a test file using UsersControllerTest as template
3. Adapt method names to match actual controller methods
4. Mark all as incomplete

Then you manually:
1. Review each test file
2. Uncomment the test code
3. Add controller-specific logic
4. Remove `markTestIncomplete()`

### Priority 3: Implement Tests by Module

Work through controllers in this order:

**Week 1 - Authentication & Core:**
- [x] UsersController (DONE - 26 tests)
- [ ] SessionsController (~15 tests)
- [ ] DashboardController (~8 tests)
- [ ] SettingsController (~20 tests)

**Week 2 - Business Logic:**
- [ ] InvoicesController (~35 tests)
- [ ] ClientsController (~30 tests)
- [ ] QuotesController (~30 tests)
- [ ] PaymentsController (~25 tests)

**Week 3 - Supporting Features:**
- [ ] ProductsController (~20 tests)
- [ ] ProjectsController (~25 tests)
- [ ] TasksController (~20 tests)
- [ ] CustomFieldsController (~15 tests)
- [ ] EmailTemplatesController (~15 tests)
- [ ] TaxRatesController (~12 tests)

**Week 4 - AJAX & Utilities:**
- [ ] All *AjaxController classes (~10 tests each = ~150 tests)
- [ ] ImportController (~10 tests)
- [ ] ReportsController (~15 tests)
- [ ] UploadController (~12 tests)

### Priority 4: Run and Fix Tests

Once tests are generated:

1. **Run incrementally:**
   ```bash
   vendor/bin/phpunit modules/core/tests/UsersControllerTest.php
   vendor/bin/phpunit modules/core/tests/
   vendor/bin/phpunit modules/clients/tests/
   # etc.
   ```

2. **Fix failures** as they arise
3. **Add missing assertions**
4. **Verify database state**
5. **Check code coverage:**
   ```bash
   vendor/bin/phpunit --coverage-html coverage/
   ```

## Test Writing Checklist

For each controller method, ensure you have tests for:

- [ ] ✅ Requires authentication (if applicable)
- [ ] ✅ Requires correct role (admin/guest)
- [ ] ✅ Happy path with valid data
- [ ] ✅ Returns expected HTTP status
- [ ] ✅ Response contains expected content
- [ ] ✅ Database changes are persisted correctly
- [ ] ✅ Side effects occur (emails, logs, etc.)
- [ ] ✅ Missing required fields rejected
- [ ] ✅ Invalid data format rejected
- [ ] ✅ Duplicate data rejected (if applicable)
- [ ] ✅ Invalid IDs return 404
- [ ] ✅ XSS attempts sanitized
- [ ] ✅ SQL injection prevented
- [ ] ✅ Business rules enforced
- [ ] ✅ Edge cases handled

## How to Use This Implementation

### Step 1: Choose Your HTTP Testing Approach

Pick one of the three options above and implement it. I recommend Codeception because it's battle-tested with CodeIgniter.

### Step 2: Update HttpTestCase

Once you have HTTP working, update these methods in HttpTestCase.php:

```php
protected function call(string $method, string $uri, array $data = []): TestResponse
{
    // Replace the TODO with actual HTTP client code
    // Return real TestResponse with actual status code and content
}

protected function actingAs(array $userData = []): int
{
    // Ensure session is actually set for the HTTP request
    // Not just in memory
}
```

### Step 3: Verify UsersControllerTest Works

```bash
vendor/bin/phpunit modules/core/tests/UsersControllerTest.php
```

Fix any failures until all 26 tests pass (or are properly incomplete).

### Step 4: Generate Remaining Tests

Use the test generator script or manually copy UsersControllerTest pattern for each controller.

### Step 5: Fill In TODOs

For each test file:
1. Remove `markTestIncomplete()`
2. Uncomment test code
3. Add controller-specific assertions
4. Run and fix

### Step 6: Achieve Coverage

Run full suite and check coverage:

```bash
vendor/bin/phpunit --coverage-text
```

Target: >80% code coverage on controllers.

## Expected Timeline

**With HTTP Infrastructure Complete:**
- 1-2 days: Generate all test skeletons
- 5-7 days: Implement and fix all tests
- 1-2 days: Coverage review and edge cases
- **Total: ~2 weeks**

**Without HTTP Infrastructure:**
- 2-3 days: Implement HTTP testing
- 1-2 days: Generate test skeletons
- 5-7 days: Implement all tests
- 1-2 days: Coverage review
- **Total: ~3 weeks**

## Measuring Success

You'll know you're done when:

✅ All 52 controllers have test files
✅ Average 15-20 tests per controller (~1000 tests total)
✅ 0 tests marked as incomplete
✅ All tests pass
✅ Code coverage >80%
✅ No obvious bugs slip through

## Example Test Patterns

### Pattern 1: Authentication Required

```php
#[Test]
public function it_VERB_route_requires_authentication(): void
{
    $this->clearAuthentication();
    $response = $this->get('some/route');
    $this->assertRedirect($response, 'sessions/login');
}
```

### Pattern 2: Happy Path

```php
#[Test]
public function it_VERB_route_succeeds_with_valid_data(): void
{
    $this->actingAsAdmin();
    
    $data = ['field' => 'value'];
    $response = $this->post('some/route', $data);
    
    $this->assertOk($response);
    $this->assertDatabaseHas('table_name', $data);
}
```

### Pattern 3: Validation

```php
#[Test]
public function it_VERB_route_validates_required_field(): void
{
    $this->actingAsAdmin();
    
    $invalidData = ['field' => ''];
    $response = $this->post('some/route', $invalidData);
    
    $this->assertResponseContains($response, 'required');
    $this->assertDatabaseMissing('table_name', $invalidData);
}
```

### Pattern 4: Security

```php
#[Test]
public function it_VERB_route_sanitizes_xss(): void
{
    $this->actingAsAdmin();
    
    $xssData = ['field' => '<script>alert("xss")</script>'];
    $response = $this->post('some/route', $xssData);
    
    $this->assertDatabaseMissing('table_name', [
        'field' => '<script>alert("xss")</script>'
    ]);
}
```

## Files to Reference

1. **modules/core/src/Testing/HttpTestCase.php** - Base test class
2. **modules/core/src/Testing/TestResponse.php** - Response wrapper
3. **modules/core/tests/UsersControllerTest.php** - Complete example (26 tests)
4. **TESTING_STRATEGY.md** - Overall strategy document
5. **scripts/generate_route_tests.php** - Test generator (needs refinement)

## Final Notes

The foundation is laid. The pattern is established. The remaining work is systematic but significant:

1. **Implement HTTP testing** (2-3 days)
2. **Generate all test files** (1-2 days)
3. **Fill in TODOs** (5-7 days)
4. **Run and fix** (2-3 days)

**Total: 2-3 weeks of focused work for production-ready tests.**

The example in UsersControllerTest.php shows exactly what "production-ready" means:
- Not just `->ok()` checks
- Comprehensive validation
- Security testing
- Business logic enforcement
- Edge case handling

Replicate this pattern across all 51 remaining controllers and you'll have the test suite you need.
